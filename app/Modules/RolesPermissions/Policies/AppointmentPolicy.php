<?php

namespace App\Modules\RolesPermissions\Policies;

use App\Modules\Authentication\Models\Staff;
use App\Modules\Appointments\Models\Appointment;
use App\Modules\RolesPermissions\Constants\PermissionList;
use App\Modules\RolesPermissions\Enums\RoleEnum;

class AppointmentPolicy
{
    public function viewAny(Staff $staff): bool
    {
        return $staff->hasPermissionTo(PermissionList::VIEW_APPOINTMENTS, 'api');
    }

    public function view(Staff $staff, Appointment $appointment): bool
    {
        if (! $staff->hasPermissionTo(PermissionList::VIEW_APPOINTMENTS, 'api')) {
            return false;
        }

        if ($staff->hasAnyRole([
                RoleEnum::MEDICAL_CENTER_ADMIN->value,
                RoleEnum::CLINIC_ADMIN->value,
                RoleEnum::SECRETARY->value,
                ], 'api')) {
            return true;
        }

        if ($staff->hasRole(RoleEnum::DOCTOR->value, 'api')) {
            if (config('opticare.is_medical_center', false)) {
                return (int) $appointment->doctor_id === (int) $staff->id;
            }

            return true;
        }

        return false;
    }

    public function create(Staff $staff): bool
    {
        return $staff->hasPermissionTo(PermissionList::CREATE_APPOINTMENT, 'api');
    }

    public function cancel(Staff $staff): bool
    {
        return $staff->hasPermissionTo(PermissionList::CANCEL_APPOINTMENT, 'api');
    }

    public function confirm(Staff $staff, Appointment $appointment): bool
    {
        return $staff->hasPermissionTo(PermissionList::CONFIRM_APPOINTMENT, 'api');
    }

    public function managePatientStatus(Staff $staff): bool
    {
        return $staff->hasPermissionTo(PermissionList::MANAGE_PATIENT_STATUS, 'api');
    }

    public function assignToDoctor(Staff $staff): bool
    {
        return $staff->hasPermissionTo(PermissionList::ASSIGN_PATIENT_TO_DOCTOR, 'api');
    }

    public function bookFollowUp(Staff $staff): bool
    {
        return $staff->hasPermissionTo(PermissionList::BOOK_FOLLOW_UP, 'api');
    }

    public function bookSurgery(Staff $staff): bool
    {
        return $staff->hasPermissionTo(PermissionList::BOOK_SURGERY, 'api');
    }
}
