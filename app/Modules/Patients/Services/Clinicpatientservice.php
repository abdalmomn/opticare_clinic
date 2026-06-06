<?php

namespace App\Modules\Patients\Services;

use App\Modules\Authentication\Models\Staff;
use App\Modules\Patients\Helpers\ClinicPatientHelper;
use App\Modules\Patients\Repositories\ClinicPatientRepository;
use App\Modules\RolesPermissions\Constants\PermissionList;
use App\Modules\RolesPermissions\Helpers\AccessControlHelper;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Http\Response;

class ClinicPatientService
{
    public function __construct(
        protected ClinicPatientRepository $repository
    ) {}

    private function authorize(Staff $actor, string $permission, string $messageKey): void
    {
        if (! AccessControlHelper::staffHasPermission($actor, $permission)) {
            throw new HttpException(
                Response::HTTP_FORBIDDEN,
                __($messageKey)
            );
        }
    }

    public function listPatients(array $filters, Staff $actor): array
    {
        $this->authorize($actor, PermissionList::VIEW_PATIENTS, 'patient.errors.not_allowed_view');

        $paginator = $this->repository->search($filters);

        return ClinicPatientHelper::formatPaginated($paginator);
    }

    public function searchPatients(array $filters, Staff $actor): array
    {
        $this->authorize($actor, PermissionList::SEARCH_PATIENT, 'patient.errors.not_allowed_search');

        $paginator = $this->repository->search($filters);

        return ClinicPatientHelper::formatPaginated($paginator);
    }

    public function showPatient(int $patientId, Staff $actor): array
    {
        $this->authorize($actor, PermissionList::VIEW_PATIENTS, 'patient.errors.not_allowed_view');

        $patient = $this->repository->findPatientById($patientId);

        if (! $patient) {
            throw new HttpException(Response::HTTP_NOT_FOUND, __('patient.errors.patient_not_found'));
        }

        return [
            'patient' => $patient,
        ];
    }

    public function createPatient(array $data, Staff $actor): array
    {
        $this->authorize($actor, PermissionList::CREATE_PATIENT, 'patient.errors.not_allowed_create');

        if ($this->repository->identityExists($data['identity_number'], $data['identity_type'])) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('patient.errors.identity_exists')
            );
        }

        $fileNumber = $data['medical_file_number'] ?? $this->repository->nextFileNumber();

        $payload = ClinicPatientHelper::prepareCreateData($data, $fileNumber, $actor->id);

        $patient = $this->repository->createPatient($payload);

        return [
            'patient' => $patient,
        ];
    }

    public function updatePatient(int $patientId, array $data, Staff $actor): array
    {
        $this->authorize($actor, PermissionList::EDIT_PATIENT, 'patient.errors.not_allowed_edit');

        $patient = $this->repository->findPatientById($patientId);

        if (! $patient) {
            throw new HttpException(Response::HTTP_NOT_FOUND, __('patient.errors.patient_not_found'));
        }

        if (isset($data['identity_number'])) {
            $identityType = $data['identity_type'] ?? $patient->identity_type ?? 'national_id';

            if ($this->repository->identityExists($data['identity_number'], $identityType, $patient->id)) {
                throw new HttpException(
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                    __('patient.errors.identity_exists')
                );
            }
        }

        $payload = ClinicPatientHelper::prepareUpdateData(
            $data,
            $patient->toArray(),
            $actor->id
        );

        $updated = $this->repository->updatePatient($patient, $payload);

        return [
            'patient' => $updated,
        ];
    }

    public function toggleStatus(int $patientId, Staff $actor): array
    {
        $this->authorize($actor, PermissionList::EDIT_PATIENT, 'patient.errors.not_allowed_edit');

        $patient = $this->repository->findPatientById($patientId);

        if (! $patient) {
            throw new HttpException(Response::HTTP_NOT_FOUND, __('patient.errors.patient_not_found'));
        }

        if (in_array($patient->status, ['archived', 'deceased'], true)) {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('patient.errors.patient_status_cannot_be_toggled')
            );
        }

        $newStatus = ! $patient->is_active;

        $updated = $this->repository->updatePatient($patient, [
            'is_active'  => $newStatus,
            'status'     => $newStatus ? 'active' : 'inactive',
            'updated_by' => $actor->id,
        ]);

        return [
            'patient' => $updated,
        ];
    }

    public function archivePatient(int $patientId, array $data, Staff $actor): array
    {
        $this->authorize($actor, PermissionList::ARCHIVE_PATIENT, 'patient.errors.not_allowed_archive');

        $patient = $this->repository->findPatientById($patientId);

        if (! $patient) {
            throw new HttpException(Response::HTTP_NOT_FOUND, __('patient.errors.patient_not_found'));
        }

        if ($patient->status === 'deceased') {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('patient.errors.deceased_patient_cannot_be_archived')
            );
        }

        if ($patient->status === 'archived') {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('patient.errors.patient_already_archived')
            );
        }

        $updated = $this->repository->updatePatient($patient, [
            'status' => 'archived',
            'is_active' => false,
            'archived_at' => now(),
            'archived_by' => $actor->id,
            'archive_reason' => $data['archive_reason'],
            'archive_notes' => $data['archive_notes'] ?? null,
            'deceased_at' => null,
            'updated_by' => $actor->id,
        ]);

        return [
            'patient' => $updated,
        ];
    }

    public function restorePatient(int $patientId, Staff $actor): array
    {
        $this->authorize($actor, PermissionList::RESTORE_PATIENT, 'patient.errors.not_allowed_restore');

        $patient = $this->repository->findPatientById($patientId);

        if (! $patient) {
            throw new HttpException(Response::HTTP_NOT_FOUND, __('patient.errors.patient_not_found'));
        }

        if ($patient->status === 'deceased') {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('patient.errors.deceased_patient_cannot_be_restored')
            );
        }

        if ($patient->status !== 'archived' && $patient->status !== 'inactive') {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('patient.errors.patient_is_not_archived')
            );
        }

        $updated = $this->repository->updatePatient($patient, [
            'status' => 'active',
            'is_active' => true,
            'archived_at' => null,
            'archived_by' => null,
            'archive_reason' => null,
            'archive_notes' => null,
            'deceased_at' => null,
            'updated_by' => $actor->id,
        ]);

        return [
            'patient' => $updated,
        ];
    }

    public function markPatientDeceased(int $patientId, array $data, Staff $actor): array
    {
        $this->authorize($actor, PermissionList::ARCHIVE_PATIENT, 'patient.errors.not_allowed_archive');

        $patient = $this->repository->findPatientById($patientId);

        if (! $patient) {
            throw new HttpException(Response::HTTP_NOT_FOUND, __('patient.errors.patient_not_found'));
        }

        if ($patient->status === 'deceased') {
            throw new HttpException(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                __('patient.errors.patient_already_deceased')
            );
        }

        $updated = $this->repository->updatePatient($patient, [
            'status' => 'deceased',
            'is_active' => false,
            'archived_at' => now(),
            'archived_by' => $actor->id,
            'archive_reason' => 'deceased',
            'archive_notes' => $data['archive_notes'] ?? null,
            'deceased_at' => $data['deceased_at'],
            'updated_by' => $actor->id,
        ]);

        return [
            'patient' => $updated,
        ];
    }
}
