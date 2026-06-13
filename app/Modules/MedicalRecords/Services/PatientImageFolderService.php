<?php

namespace App\Modules\MedicalRecords\Services;

use App\Modules\Authentication\Models\Staff;
use App\Modules\Imaging\Models\ImagingFile;
use App\Modules\Imaging\Models\ImagingRequest;
use App\Modules\MedicalRecords\Helpers\MedicalRecordImagesHelper;
use App\Modules\MedicalRecords\Repositories\MedicalRecordImagingRepository;
use App\Modules\Patients\Models\ClinicPatient;
use App\Modules\Patients\Repositories\ClinicPatientRepository;
use App\Modules\RolesPermissions\Constants\PermissionList;
use Illuminate\Http\Response;
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

        $paginator->getCollection()->transform(fn (ImagingRequest $folder): array => MedicalRecordImagesHelper::formatFolderItem($folder));

        return MedicalRecordImagesHelper::formatPaginated($paginator);
    }

    public function folderFiles(int $folderId, array $filters, Staff $actor): array
    {
        MedicalRecordImagesHelper::authorize($actor, PermissionList::VIEW_IMAGING_TIMELINE, 'medical_record.errors.not_allowed_view_image_folders');

        $folder = $this->findFolderOrFail($folderId, array_merge($filters, ['doctor_id' => $actor->id]));

        MedicalRecordImagesHelper::ensurePatientAccessible($actor, (int) $folder->patient_id);

        $files = $folder->files->map(fn (ImagingFile $file): array => MedicalRecordImagesHelper::formatFile($file));

        $byType = [];
        foreach ($files as $file) {
            $type = $file['image_type'] ?? 'unknown';
            $byType[$type][] = $file;
        }

        return [
            'folder' => MedicalRecordImagesHelper::formatFolderHeader($folder),
            'files_by_type' => $byType,
            'files' => $files->all(),
        ];
    }

    public function imagingFile(int $fileId, Staff $actor): array
    {
        MedicalRecordImagesHelper::authorize($actor, PermissionList::VIEW_IMAGING_TIMELINE, 'medical_record.errors.not_allowed_view_image_folders');

        $file = $this->imaging->findFile($fileId, $actor->id);

        if (! $file) {
            throw new HttpException(Response::HTTP_NOT_FOUND, __('medical_record.errors.file_not_found'));
        }

        $folder = $file->imagingRequest;

        MedicalRecordImagesHelper::ensurePatientAccessible($actor, (int) ($folder->patient_id ?? 0));

        return array_merge(MedicalRecordImagesHelper::formatFile($file), [
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
            'left' => MedicalRecordImagesHelper::formatComparisonSide($left, $leftIsHistorical ? 'Historical' : 'Current'),
            'right' => MedicalRecordImagesHelper::formatComparisonSide($right, $leftIsHistorical ? 'Current' : 'Historical'),
        ];
    }

    public function fileComparison(int $patientId, array $data, Staff $actor): array
    {
        $patient = $this->prepareAccess($actor, $patientId, PermissionList::COMPARE_IMAGES, 'medical_record.errors.not_allowed_compare_images');

        $left = $this->findFileForPatientOrFail((int) $data['left_file_id'], $patient->id, $actor->id);
        $right = $this->findFileForPatientOrFail((int) $data['right_file_id'], $patient->id, $actor->id);

        $leftType = MedicalRecordImagesHelper::resolvedType($left);
        $rightType = MedicalRecordImagesHelper::resolvedType($right);

        if ($leftType === null || $rightType === null || $leftType !== $rightType) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('medical_record.errors.files_type_mismatch')
            );
        }

        return [
            'image_type' => $leftType,
            'same_eye' => MedicalRecordImagesHelper::nullableMatch($left->eye, $right->eye),
            'same_region' => MedicalRecordImagesHelper::nullableMatch($left->region, $right->region),
            'left' => MedicalRecordImagesHelper::formatComparisonFileWithFolder($left),
            'right' => MedicalRecordImagesHelper::formatComparisonFileWithFolder($right),
        ];
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

    private function findPatientOrFail(int $patientId): ClinicPatient
    {
        $patient = $this->patients->findPatientById($patientId);

        if (! $patient) {
            throw new HttpException(Response::HTTP_NOT_FOUND, __('medical_record.errors.patient_not_found'));
        }

        return $patient;
    }

    private function prepareAccess(Staff $actor, int $patientId, string $permission, string $messageKey): ClinicPatient
    {
        MedicalRecordImagesHelper::authorize($actor, $permission, $messageKey);

        $patient = $this->findPatientOrFail($patientId);

        MedicalRecordImagesHelper::ensurePatientAccessible($actor, $patient->id);

        return $patient;
    }
}
