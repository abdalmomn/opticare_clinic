<?php

namespace App\Modules\Imaging\Services;

use App\Modules\Appointments\Repositories\AppointmentRepository;
use App\Modules\Authentication\Models\Staff;
use App\Modules\Imaging\Helpers\ImagingHelper;
use App\Modules\Imaging\Helpers\ImagingRequestHelper;
use App\Modules\Imaging\Models\ImagingFile;
use App\Modules\Imaging\Models\ImagingRequest;
use App\Modules\Imaging\Repositories\ImagingRequestRepository;
use App\Modules\MedicalRecords\Repositories\VisitRecordRepository;
use App\Modules\Patients\Repositories\ClinicPatientRepository;
use App\Modules\RolesPermissions\Constants\PermissionList;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\DB;
use App\Modules\Imaging\Models\ImagingActivityLog;
use Illuminate\Http\Response;

class DirectImagingUploadService
{
    public function __construct(
        protected ImagingRequestRepository $requestRepository,
        protected ImagingRequestService $requestService,
        protected ImagingFileService $fileService,
        protected ClinicPatientRepository $patientRepository,
        protected VisitRecordRepository $visitRecordRepository,
        protected AppointmentRepository $appointmentRepository,
        protected ImagingActivityLogService $activityLog
    ) {}

    public function directUpload(array $data, Staff $actor): array
    {
        ImagingHelper::ensurePermission($actor, PermissionList::UPLOAD_DOCTOR_IMAGING_FILES);

        return $this->handleUpload(
            $data,
            $actor,
            ImagingRequest::SOURCE_DOCTOR_UPLOAD,
            ImagingFile::SOURCE_DOCTOR_UPLOAD,
            requireActiveDevices: true
        );
    }

    public function externalUpload(array $data, Staff $actor): array
    {
        ImagingHelper::ensurePermission($actor, PermissionList::UPLOAD_EXTERNAL_IMAGING_FILES);

        return $this->handleUpload(
            $data,
            $actor,
            ImagingRequest::SOURCE_EXTERNAL,
            ImagingFile::SOURCE_EXTERNAL,
            requireActiveDevices: false
        );
    }

    private function handleUpload(
        array $data,
        Staff $actor,
        string $requestSource,
        string $fileSource,
        bool $requireActiveDevices
    ): array {
        $patient = $this->patientRepository->findPatientById($data['patient_id']);

        if (! $patient) {
            throw new HttpException(
                Response::HTTP_NOT_FOUND,
                __('imaging.errors.patient_not_found')
            );
        }

        $visitRecord = null;
        if (! empty($data['visit_record_id'])) {
            $visitRecord = $this->visitRecordRepository->findSession((int) $data['visit_record_id']);

            if (! $visitRecord) {
                throw new HttpException(
                    Response::HTTP_NOT_FOUND,
                    __('imaging.errors.visit_record_not_found')
                );
            }
        }

        $appointment = null;
        if (! empty($data['appointment_id'])) {
            $appointment = $this->appointmentRepository->findById((int) $data['appointment_id']);

            if (! $appointment) {
                throw new HttpException(
                    Response::HTTP_NOT_FOUND,
                    __('imaging.errors.appointment_not_found')
                );
            }
        }

        ImagingHelper::ensureConsistency($patient->id, $visitRecord, $appointment);

        $files = array_values($data['files']);
        $metadata = array_values($data['metadata'] ?? []);

        if (count($metadata) !== count($files)) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('imaging.errors.metadata_mismatch')
            );
        }

        if ($requireActiveDevices) {
            foreach (array_unique(array_filter(array_column($metadata, 'device_id'))) as $deviceId) {
                $this->fileService->resolveActiveDevice((int) $deviceId);
            }
        }

        $storedPaths = [];

        try {
            return DB::transaction(function () use (
                $data,
                $actor,
                $patient,
                $visitRecord,
                $appointment,
                $requestSource,
                $fileSource,
                $files,
                $metadata,
                &$storedPaths
            ) {
                $imagingRequest = $this->requestRepository->create([
                    'patient_id' => $patient->id,
                    'visit_record_id' => $visitRecord?->id,
                    'appointment_id' => $appointment?->id,
                    'requested_by' => $actor->id,
                    'request_type' => ImagingHelper::buildRequestTypeSummary($metadata),
                    'source' => $requestSource,
                    'notes' => $data['notes'] ?? null,
                    'status' => ImagingRequest::STATUS_COMPLETED,
                    'payment_status' => ImagingRequest::PAYMENT_STATUS_WAIVED,
                    'priority' => 'normal',
                    'created_by' => $actor->id,
                    'completed_at' => now(),
                ]);

                $metadata = ImagingHelper::attachItemsToMetadata($imagingRequest, $metadata);

                $createdFiles = $this->fileService->persistFiles(
                    $imagingRequest,
                    $files,
                    $metadata,
                    ['source' => $fileSource],
                    $actor,
                    $storedPaths
                );

                $this->activityLog->record(
                    $requestSource === ImagingRequest::SOURCE_EXTERNAL
                        ? ImagingActivityLog::ACTION_EXTERNAL_UPLOAD_CREATED
                        : ImagingActivityLog::ACTION_DIRECT_UPLOAD_CREATED,
                    imagingRequestId: $imagingRequest->id,
                    actorId: $actor->id,
                    toStatus: ImagingRequest::STATUS_COMPLETED,
                    metadata: ['files_count' => count($createdFiles)]
                );

                return [
                    'request' => ImagingRequestHelper::formatRequest(
                        $this->requestRepository->findDetailed($imagingRequest->id),
                        $actor
                    ),
                    'files' => array_map(
                        fn (ImagingFile $file) => ImagingRequestHelper::formatFile($file),
                        $createdFiles
                    ),
                ];
            });
        } catch (\Throwable $exception) {
            ImagingHelper::cleanupStoredFiles($storedPaths);

            throw $exception;
        }
    }

}
