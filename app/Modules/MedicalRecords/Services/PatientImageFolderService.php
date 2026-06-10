<?php

namespace App\Modules\MedicalRecords\Services;

use App\Modules\Appointments\Models\Appointment;
use App\Modules\Authentication\Models\Staff;
use App\Modules\Imaging\Models\ImagingFile;
use App\Modules\Imaging\Models\ImagingRequest;
use App\Modules\MedicalRecords\Repositories\MedicalRecordImagingRepository;
use App\Modules\Patients\Models\ClinicPatient;
use App\Modules\Patients\Repositories\ClinicPatientRepository;
use App\Modules\RolesPermissions\Constants\PermissionList;
use App\Modules\RolesPermissions\Enums\RoleEnum;
use App\Modules\RolesPermissions\Helpers\AccessControlHelper;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PatientImageFolderService
{
    public function __construct(
        protected MedicalRecordImagingRepository $imaging,
        protected ClinicPatientRepository $patients,
    ) {}

    public function imageTypes(int $patientId, array $filters, Staff $actor): array
    {
        $patient = $this->prepareAccess($actor, $patientId, PermissionList::VIEW_IMAGING_TIMELINE, 'medical_record.errors.not_allowed_view_image_folders');

        $rows = $this->imaging->imageTypesForPatient($patient->id, $filters);

        $items = $rows->map(fn ($row): array => [
            'image_type' => $row->resolved_type,
            'label' => $row->resolved_type,
            'files_count' => (int) $row->files_count,
            'folders_count' => (int) $row->folders_count,
            'latest_captured_at' => $row->latest_captured_at
                ? \Illuminate\Support\Carbon::parse($row->latest_captured_at)->toISOString()
                : null,
        ])->all();

        return ['items' => $items];
    }

    public function imageFolders(int $patientId, array $filters, Staff $actor): array
    {
        $patient = $this->prepareAccess($actor, $patientId, PermissionList::VIEW_IMAGING_TIMELINE, 'medical_record.errors.not_allowed_view_image_folders');

        $paginator = $this->imaging->foldersForPatient($patient->id, $filters);

        $paginator->getCollection()->transform(fn (ImagingRequest $folder): array => $this->formatFolderItem($folder));

        return $this->formatPaginated($paginator);
    }

    public function folderFiles(int $folderId, array $filters, Staff $actor): array
    {
        $this->authorize($actor, PermissionList::VIEW_IMAGING_TIMELINE, 'medical_record.errors.not_allowed_view_image_folders');

        $folder = $this->findFolderOrFail($folderId, array_merge($filters, ['doctor_id' => $actor->id]));

        $this->ensurePatientAccessible($actor, (int) $folder->patient_id);

        $files = $folder->files->map(fn (ImagingFile $file): array => $this->formatFile($file));

        $byType = [];
        foreach ($files as $file) {
            $type = $file['image_type'] ?? 'unknown';
            $byType[$type][] = $file;
        }

        return [
            'folder' => $this->formatFolderHeader($folder),
            'files_by_type' => $byType,
            'files' => $files->all(),
        ];
    }

    public function imagingFile(int $fileId, Staff $actor): array
    {
        $this->authorize($actor, PermissionList::VIEW_IMAGING_TIMELINE, 'medical_record.errors.not_allowed_view_image_folders');

        $file = $this->imaging->findFile($fileId, $actor->id);

        if (! $file) {
            throw new HttpException(Response::HTTP_NOT_FOUND, __('medical_record.errors.file_not_found'));
        }

        $folder = $file->imagingRequest;

        $this->ensurePatientAccessible($actor, (int) ($folder->patient_id ?? 0));

        return array_merge($this->formatFile($file), [
            'folder' => $folder ? [
                'id' => $folder->id,
                'label' => optional($folder->created_at)->format('M Y'),
                'request_type' => $folder->request_type,
            ] : null,
        ]);
    }

    public function imageComparison(int $patientId, array $data, Staff $actor): array
    {
        $patient = $this->prepareAccess($actor, $patientId, PermissionList::COMPARE_IMAGES, 'medical_record.errors.not_allowed_compare_images');

        $imageType = $data['image_type'];

        $left = $this->findTypedFolderForPatientOrFail((int) $data['left_folder_id'], $patient->id, $imageType, $actor->id);
        $right = $this->findTypedFolderForPatientOrFail((int) $data['right_folder_id'], $patient->id, $imageType, $actor->id);

        $leftIsHistorical = ! $left->created_at
            || ! $right->created_at
            || $left->created_at->lessThanOrEqualTo($right->created_at);

        return [
            'image_type' => $imageType,
            'left' => $this->formatComparisonSide($left, $leftIsHistorical ? 'Historical' : 'Current'),
            'right' => $this->formatComparisonSide($right, $leftIsHistorical ? 'Current' : 'Historical'),
        ];
    }

    public function fileComparison(int $patientId, array $data, Staff $actor): array
    {
        $patient = $this->prepareAccess($actor, $patientId, PermissionList::COMPARE_IMAGES, 'medical_record.errors.not_allowed_compare_images');

        $left = $this->findFileForPatientOrFail((int) $data['left_file_id'], $patient->id, $actor->id);
        $right = $this->findFileForPatientOrFail((int) $data['right_file_id'], $patient->id, $actor->id);

        $leftType = $this->resolvedType($left);
        $rightType = $this->resolvedType($right);

        if ($leftType === null || $rightType === null || $leftType !== $rightType) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('medical_record.errors.files_type_mismatch')
            );
        }

        return [
            'image_type' => $leftType,
            'same_eye' => $this->nullableMatch($left->eye, $right->eye),
            'same_region' => $this->nullableMatch($left->region, $right->region),
            'left' => $this->formatComparisonFileWithFolder($left),
            'right' => $this->formatComparisonFileWithFolder($right),
        ];
    }

    private function formatFolderItem(ImagingRequest $folder): array
    {
        $imagesCount = (int) ($folder->files_count ?? 0);

        $countsByType = [];
        foreach ($folder->files as $file) {
            $type = $this->resolvedType($file);
            if ($type !== null) {
                $countsByType[$type] = ($countsByType[$type] ?? 0) + 1;
            }
        }

        return [
            'id' => $folder->id,
            'timeline_type' => 'imaging',
            'folder_label' => optional($folder->created_at)->format('M Y'),
            'date' => optional($folder->created_at)->toISOString(),
            'request_type' => $folder->request_type,
            'status' => $folder->status,
            'images_count' => $imagesCount,
            'available_types' => array_keys($countsByType),
            'files_count_by_type' => $countsByType,
            'doctor' => $this->formatDoctor($folder->requestedBy),
            'is_selectable_for_view' => $imagesCount > 0,
            'is_selectable_for_report' => $imagesCount > 0,
            'is_selectable_for_compare' => $imagesCount > 0,
        ];
    }

    private function formatFolderHeader(ImagingRequest $folder): array
    {
        return [
            'id' => $folder->id,
            'label' => optional($folder->created_at)->format('M Y'),
            'request_type' => $folder->request_type,
            'date' => optional($folder->created_at)->toISOString(),
            'doctor' => $this->formatDoctor($folder->requestedBy),
        ];
    }

    private function formatFile(ImagingFile $file): array
    {
        return [
            'id' => $file->id,
            'label' => $this->fileLabel($file),
            'image_type' => $this->resolvedType($file),
            'modality' => $file->modality,
            'eye' => $file->eye,
            'region' => $file->region,
            'file_name' => $file->file_name,
            'file_url' => $this->fileUrl($file->file_path),
            'thumbnail_url' => $this->thumbnailUrl($file),
            'captured_at' => optional($file->captured_at)->toISOString(),
            'notes' => $this->noteFor($file),
            'is_selectable_for_view' => true,
            'is_selectable_for_report' => true,
            'is_selectable_for_compare' => true,
        ];
    }

    private function formatComparisonSide(ImagingRequest $folder, string $title): array
    {
        return [
            'folder' => [
                'id' => $folder->id,
                'label' => optional($folder->created_at)->format('M Y'),
                'date' => optional($folder->created_at)->toISOString(),
                'title' => $title,
            ],
            'files' => $folder->files->map(fn (ImagingFile $file): array => $this->formatComparisonFile($file))->all(),
        ];
    }

    private function formatComparisonFile(ImagingFile $file): array
    {
        return [
            'id' => $file->id,
            'label' => $this->fileLabel($file),
            'image_type' => $this->resolvedType($file),
            'modality' => $file->modality,
            'eye' => $file->eye,
            'region' => $file->region,
            'file_url' => $this->fileUrl($file->file_path),
            'thumbnail_url' => $this->thumbnailUrl($file),
            'notes' => $this->noteFor($file),
        ];
    }

    private function formatComparisonFileWithFolder(ImagingFile $file): array
    {
        $folder = $file->imagingRequest;

        return array_merge($this->formatComparisonFile($file), [
            'folder' => $folder ? [
                'id' => $folder->id,
                'label' => optional($folder->created_at)->format('M Y'),
                'date' => optional($folder->created_at)->toISOString(),
            ] : null,
        ]);
    }

    private function formatDoctor(?Staff $staff): ?array
    {
        if (! $staff) {
            return null;
        }

        return ['id' => $staff->id, 'name' => $staff->name];
    }

    private function resolvedType(ImagingFile $file): ?string
    {
        if ($file->image_type !== null && $file->image_type !== '') {
            return $file->image_type;
        }

        return ($file->modality !== null && $file->modality !== '') ? $file->modality : null;
    }

    private function fileLabel(ImagingFile $file): ?string
    {
        if (! empty($file->image_label)) {
            return $file->image_label;
        }

        if (! empty($file->region) && ! empty($file->eye)) {
            return $file->region . ' ' . $file->eye;
        }

        if (! empty($file->modality)) {
            return $file->modality;
        }

        return $file->file_name ? pathinfo($file->file_name, PATHINFO_FILENAME) : null;
    }

    private function thumbnailUrl(ImagingFile $file): ?string
    {
        if (! empty($file->thumbnail_path)) {
            return $this->fileUrl($file->thumbnail_path);
        }

        return $this->fileUrl($file->file_path);
    }

    private function fileUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return asset('storage/' . ltrim($path, '/'));
    }

    private function noteFor(ImagingFile $file): ?string
    {
        if (! $file->relationLoaded('doctorNotes')) {
            return null;
        }

        return optional($file->doctorNotes->first())->note;
    }

    private function nullableMatch($a, $b): ?bool
    {
        if ($a === null || $b === null || $a === '' || $b === '') {
            return null;
        }

        return $a === $b;
    }

    private function findFolderOrFail(int $folderId, array $options = []): ImagingRequest
    {
        $folder = $this->imaging->findFolder($folderId, $options);

        if (! $folder) {
            throw new HttpException(Response::HTTP_NOT_FOUND, __('medical_record.errors.folder_not_found'));
        }

        return $folder;
    }

    private function findTypedFolderForPatientOrFail(int $folderId, int $patientId, string $imageType, int $doctorId): ImagingRequest
    {
        $folder = $this->findFolderOrFail($folderId, [
            'image_type' => $imageType,
            'doctor_id' => $doctorId,
        ]);

        if ((int) $folder->patient_id !== $patientId) {
            throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, __('medical_record.errors.folder_not_for_patient'));
        }

        if ($folder->files->isEmpty()) {
            throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, __('medical_record.errors.folder_missing_type'));
        }

        return $folder;
    }

    private function findFileForPatientOrFail(int $fileId, int $patientId, int $doctorId): ImagingFile
    {
        $file = $this->imaging->findFile($fileId, $doctorId);

        if (! $file) {
            throw new HttpException(Response::HTTP_NOT_FOUND, __('medical_record.errors.file_not_found'));
        }

        if ((int) ($file->imagingRequest->patient_id ?? 0) !== $patientId) {
            throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, __('medical_record.errors.file_not_for_patient'));
        }

        return $file;
    }

    private function prepareAccess(Staff $actor, int $patientId, string $permission, string $messageKey): ClinicPatient
    {
        $this->authorize($actor, $permission, $messageKey);

        $patient = $this->findPatientOrFail($patientId);

        $this->ensurePatientAccessible($actor, $patient->id);

        return $patient;
    }

    private function findPatientOrFail(int $patientId): ClinicPatient
    {
        $patient = $this->patients->findPatientById($patientId);

        if (! $patient) {
            throw new HttpException(Response::HTTP_NOT_FOUND, __('medical_record.errors.patient_not_found'));
        }

        return $patient;
    }

    private function ensurePatientAccessible(Staff $actor, int $patientId): void
    {
        if ($actor->hasRole(RoleEnum::MEDICAL_CENTER_ADMIN->value, 'api')) {
            return;
        }

        if ($actor->hasRole(RoleEnum::DOCTOR->value, 'api')) {
            if (! config('opticare.is_medical_center', false)) {
                return;
            }

            $owns = Appointment::query()
                ->where('patient_id', $patientId)
                ->where('doctor_id', $actor->id)
                ->exists();

            if (! $owns) {
                throw new HttpException(Response::HTTP_FORBIDDEN, __('medical_record.errors.not_allowed_view_record'));
            }
        }
    }

    private function authorize(Staff $actor, string $permission, string $messageKey): void
    {
        if (! AccessControlHelper::staffHasPermission($actor, $permission)) {
            throw new HttpException(Response::HTTP_FORBIDDEN, __($messageKey));
        }
    }

    private function formatPaginated($paginator): array
    {
        return [
            'items' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ];
    }
}
