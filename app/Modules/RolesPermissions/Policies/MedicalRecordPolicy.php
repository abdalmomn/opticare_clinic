<?php

namespace App\Modules\RolesPermissions\Policies;

use App\Modules\Authentication\Models\Staff;
use App\Modules\MedicalRecords\Models\MedicalRecord;
use App\Modules\MedicalRecords\Models\DoctorPrivateNote;
use App\Modules\Appointments\Models\Appointment;
use App\Modules\RolesPermissions\Constants\PermissionList;
use App\Modules\RolesPermissions\Enums\RoleEnum;
use App\Modules\RolesPermissions\Helpers\AccessControlHelper;

class MedicalRecordPolicy
{
    public function view(Staff $staff, MedicalRecord $record): bool
    {
        if (! AccessControlHelper::staffHasPermission($staff, PermissionList::VIEW_MEDICAL_RECORDS)) {
            return false;
        }

        if ($staff->hasRole(RoleEnum::MEDICAL_CENTER_ADMIN->value, 'api')) {
            return true;
        }

        if ($staff->hasRole(RoleEnum::DOCTOR->value, 'api')) {
            if (! config('opticare.is_medical_center', false)) {
                return true;
            }

            return Appointment::query()
                ->where('patient_id', $record->patient_id)
                ->where('doctor_id', $staff->id)
                ->exists();
        }

        return false;
    }

    public function edit(Staff $staff, MedicalRecord $record): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::EDIT_MEDICAL_RECORDS);
    }

    public function createVisit(Staff $staff): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::CREATE_VISIT_RECORD);
    }

    public function viewTimeline(Staff $staff): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::VIEW_VISIT_TIMELINE);
    }

    public function viewImagingTimeline(Staff $staff): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::VIEW_IMAGING_TIMELINE);
    }

    public function compareImages(Staff $staff): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::COMPARE_IMAGES);
    }

    public function annotateImage(Staff $staff): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::ANNOTATE_IMAGES);
    }

    public function viewOwnNotes(Staff $staff, DoctorPrivateNote $note): bool
    {
        if (! AccessControlHelper::staffHasPermission($staff, PermissionList::VIEW_OWN_NOTES)) {
            return false;
        }

        return (int) $note->doctor_id === (int) $staff->id;
    }

    public function createNote(Staff $staff): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::CREATE_NOTE);
    }

    public function createReport(Staff $staff): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::CREATE_REPORT);
    }

    public function printReport(Staff $staff): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::PRINT_REPORT);
    }

    public function createPrescription(Staff $staff): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::CREATE_PRESCRIPTION);
    }

    public function createMeasurement(Staff $staff): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::CREATE_MEASUREMENT);
    }

    public function createDiagnosis(Staff $staff): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::CREATE_DIAGNOSIS);
    }

    public function addDiseaseClassification(Staff $staff): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::ADD_DISEASE_CLASSIFICATION);
    }
}
