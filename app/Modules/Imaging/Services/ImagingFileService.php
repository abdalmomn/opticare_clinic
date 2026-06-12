<?php

namespace App\Modules\Imaging\Services;

use App\Modules\Authentication\Models\Staff;
use App\Modules\Clinic\Models\ClinicDevice;
use App\Modules\Imaging\Models\ImagingActivityLog;
use App\Modules\Imaging\Models\ImagingFile;
use App\Modules\Imaging\Models\ImagingRequest;
use App\Modules\Imaging\Models\ImagingRequestItem;
use App\Modules\Imaging\Repositories\ImagingFileRepository;
use App\Modules\Imaging\Repositories\ImagingRequestRepository;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ImagingFileService
{
    public const STORAGE_DISK = 'public';

    public function __construct(
        protected ImagingFileRepository $fileRepository,
        protected ImagingRequestRepository $requestRepository,
        protected ImagingRequestService $requestService,
        protected ImagingActivityLogService $activityLog
    ) {}

    public function uploadToRequest($request, array $data, Staff $actor): array
    {
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($request, $data, $actor, &$storedPaths) {
                $imagingRequest = $this->requestRepository->lockForUpdate(
                    $request instanceof ImagingRequest ? (int) $request->id : (int) $request
                );

                if (! $imagingRequest) {
                    throw new HttpException(
                        Response::HTTP_NOT_FOUND,
                        __('imaging.errors.not_found')
                    );
                }

                Gate::forUser($actor)->authorize('uploadFiles', $imagingRequest);

                $status = ImagingRequest::normalizeStatus($imagingRequest->status ?? '');

                if ($status !== ImagingRequest::STATUS_IN_PROGRESS) {
                    throw new HttpException(
                        Response::HTTP_UNPROCESSABLE_ENTITY,
                        __('imaging.errors.cannot_upload')
                    );
                }

                $device = $this->resolveActiveDevice((int) $data['device_id']);

                $createdFiles = $this->persistFiles(
                    $imagingRequest,
                    $data['files'],
                    $data['metadata'] ?? [],
                    [
                        'source' => ImagingFile::SOURCE_TECHNICIAN_UPLOAD,
                        'device_id' => $device->id,
                        'device_name' => $device->name,
                    ],
                    $actor,
                    $storedPaths
                );

                $imagingRequest->update(['updated_by' => $actor->id]);

                foreach ($createdFiles as $createdFile) {
                    $this->activityLog->record(
                        ImagingActivityLog::ACTION_FILE_UPLOADED,
                        imagingRequestId: $imagingRequest->id,
                        imagingFileId: $createdFile->id,
                        actorId: $actor->id,
                        metadata: ['image_type' => $createdFile->image_type]
                    );
                }

                return [
                    'request' => $this->requestService->formatRequest(
                        $this->requestRepository->findDetailed($imagingRequest->id),
                        $actor
                    ),
                    'files' => array_map(
                        fn (ImagingFile $file) => $this->requestService->formatFile($file),
                        $createdFiles
                    ),
                ];
            });
        } catch (\Throwable $exception) {
            $this->cleanupStoredFiles($storedPaths);

            throw $exception;
        }
    }

    public function deleteFile($file, Staff $actor): array
    {
        $imagingFile = $this->fileRepository->findWithRelations(
            $file instanceof ImagingFile ? (int) $file->id : (int) $file
        );

        if (! $imagingFile) {
            throw new HttpException(
                Response::HTTP_NOT_FOUND,
                __('imaging.errors.file_not_found')
            );
        }

        Gate::forUser($actor)->authorize('deleteFile', $imagingFile);

        $imagingFile->delete();

        $this->activityLog->record(
            ImagingActivityLog::ACTION_FILE_DELETED,
            imagingRequestId: $imagingFile->imaging_request_id,
            imagingFileId: $imagingFile->id,
            actorId: $actor->id
        );

        return [
            'file' => [
                'id' => $imagingFile->id,
                'deleted' => true,
            ],
        ];
    }

    /**
     * Shared storage pipeline used by technician uploads (Phase 5) and
     * direct/external uploads (Phase 7). Stores each uploaded file on the
     * public disk and persists its metadata row. Collected $storedPaths let
     * callers remove orphaned files when the surrounding transaction fails.
     *
     * @param  UploadedFile[]  $files
     * @return ImagingFile[]
     */
    public function persistFiles(
        ImagingRequest $imagingRequest,
        array $files,
        array $metadata,
        array $defaults,
        Staff $actor,
        array &$storedPaths = []
    ): array {
        $files = array_values($files);
        $metadata = array_values($metadata);

        if ($metadata !== [] && count($metadata) !== count($files)) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('imaging.errors.metadata_mismatch')
            );
        }

        $batchId = $this->fileRepository->nextBatchId();
        $directory = sprintf('imaging/%d/%d', $imagingRequest->patient_id, $imagingRequest->id);
        $createdFiles = [];

        foreach ($files as $index => $file) {
            $meta = $metadata[$index] ?? [];

            $item = $this->resolveRequestItem($imagingRequest, $meta['imaging_request_item_id'] ?? null);

            $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());
            $storedName = Str::uuid()->toString().($extension !== '' ? '.'.$extension : '');

            $path = $file->storeAs($directory, $storedName, self::STORAGE_DISK);

            if ($path === false) {
                throw new HttpException(
                    Response::HTTP_INTERNAL_SERVER_ERROR,
                    __('imaging.errors.file_store_failed')
                );
            }

            $storedPaths[] = $path;

            $createdFile = $this->fileRepository->create([
                'imaging_request_id' => $imagingRequest->id,
                'imaging_request_item_id' => $item?->id,
                'upload_batch_id' => $batchId,
                'patient_id' => $imagingRequest->patient_id,
                'visit_record_id' => $imagingRequest->visit_record_id,
                'appointment_id' => $imagingRequest->appointment_id,
                'uploaded_by' => $actor->id,
                'device_id' => $meta['device_id'] ?? $defaults['device_id'] ?? null,
                'device_name' => $meta['device_name'] ?? $defaults['device_name'] ?? null,
                'source' => $defaults['source'],
                'file_path' => $path,
                'thumbnail_path' => null,
                'file_name' => $file->getClientOriginalName(),
                'file_type' => $extension !== '' ? $extension : 'bin',
                'file_size' => (int) $file->getSize(),
                'mime_type' => (string) $file->getMimeType(),
                'modality' => $meta['modality'] ?? null,
                'image_type' => $meta['image_type'] ?? null,
                'eye' => $meta['eye'] ?? null,
                'region' => $meta['region'] ?? null,
                'image_label' => $meta['image_label'] ?? null,
                'captured_at' => $meta['captured_at'] ?? null,
                'uploaded_at' => now(),
                'is_primary' => (bool) ($meta['is_primary'] ?? false),
            ]);

            if ($item && $item->status !== ImagingRequestItem::STATUS_CAPTURED) {
                $item->update(['status' => ImagingRequestItem::STATUS_CAPTURED]);
            }

            $createdFiles[] = $createdFile;
        }

        return $createdFiles;
    }

    public function resolveActiveDevice(int $deviceId): ClinicDevice
    {
        $device = ClinicDevice::find($deviceId);

        if (! $device) {
            throw new HttpException(
                Response::HTTP_NOT_FOUND,
                __('imaging.errors.device_not_found')
            );
        }

        if ($device->status !== 'active') {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('imaging.errors.device_not_active')
            );
        }

        return $device;
    }

    private function resolveRequestItem(ImagingRequest $imagingRequest, $itemId): ?ImagingRequestItem
    {
        if (empty($itemId)) {
            return null;
        }

        $item = ImagingRequestItem::query()
            ->whereKey((int) $itemId)
            ->where('imaging_request_id', $imagingRequest->id)
            ->first();

        if (! $item) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('imaging.errors.item_mismatch')
            );
        }

        return $item;
    }

    private function cleanupStoredFiles(array $storedPaths): void
    {
        if ($storedPaths === []) {
            return;
        }

        Storage::disk(self::STORAGE_DISK)->delete($storedPaths);
    }
}
