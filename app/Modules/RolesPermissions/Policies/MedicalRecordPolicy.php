<?php

namespace App\Modules\RolesPermissions\Policies;

use App\Modules\Authentication\Models\Staff;
use App\Modules\MedicalRecords\Models\MedicalRecord;
use App\Modules\MedicalRecords\Models\DoctorPrivateNote;
use App\Modules\Appointments\Models\Appointment;
use App\Modules\RolesPermissions\Constants\PermissionList;
use App\Modules\RolesPermissions\Enums\RoleEnum;

class MedicalRecordPolicy
{
    public function view(Staff $staff, MedicalRecord $record): bool
    {
        if (! $staff->hasPermissionTo(PermissionList::VIEW_MEDICAL_RECORDS, 'api')) {
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
        return $staff->hasPermissionTo(PermissionList::EDIT_MEDICAL_RECORDS, 'api');
    }

    public function createVisit(Staff $staff): bool
    {
        return $staff->hasPermissionTo(PermissionList::CREATE_VISIT_RECORD, 'api');
    }

    public function viewTimeline(Staff $staff): bool
    {
        return $staff->hasPermissionTo(PermissionList::VIEW_VISIT_TIMELINE, 'api');
    }

    public function viewImagingTimeline(Staff $staff): bool
    {
        return $staff->hasPermissionTo(PermissionList::VIEW_IMAGING_TIMELINE, 'api');
    }

    public function compareImages(Staff $staff): bool
    {
        return $staff->hasPermissionTo(PermissionList::COMPARE_IMAGES, 'api');
    }

    public function annotateImage(Staff $staff): bool
    {
        return $staff->hasPermissionTo(PermissionList::ANNOTATE_IMAGES, 'api');
    }

    public function viewOwnNotes(Staff $staff, DoctorPrivateNote $note): bool
    {
        if (! $staff->hasPermissionTo(PermissionList::VIEW_OWN_NOTES, 'api')) {
            return false;
        }

        return (int) $note->doctor_id === (int) $staff->id;
    }

    public function createNote(Staff $staff): bool
    {
        return $staff->hasPermissionTo(PermissionList::CREATE_NOTE, 'api');
    }

    public function createReport(Staff $staff): bool
    {
        return $staff->hasPermissionTo(PermissionList::CREATE_REPORT, 'api');
    }

    public function printReport(Staff $staff): bool
    {
        return $staff->hasPermissionTo(PermissionList::PRINT_REPORT, 'api');
    }

    public function createPrescription(Staff $staff): bool
    {
        return $staff->hasPermissionTo(PermissionList::CREATE_PRESCRIPTION, 'api');
    }

    public function createMeasurement(Staff $staff): bool
    {
        return $staff->hasPermissionTo(PermissionList::CREATE_MEASUREMENT, 'api');
    }

    public function createDiagnosis(Staff $staff): bool
    {
        return $staff->hasPermissionTo(PermissionList::CREATE_DIAGNOSIS, 'api');
    }

    public function addDiseaseClassification(Staff $staff): bool
    {
        return $staff->hasPermissionTo(PermissionList::ADD_DISEASE_CLASSIFICATION, 'api');
    }
}
