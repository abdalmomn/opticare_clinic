<?php

namespace App\Modules\RolesPermissions\Policies;

use App\Modules\Authentication\Models\Staff;
use App\Modules\Appointments\Models\Appointment;
use App\Modules\RolesPermissions\Constants\PermissionList;
use App\Modules\RolesPermissions\Enums\RoleEnum;
use App\Modules\RolesPermissions\Helpers\AccessControlHelper;

class AppointmentPolicy
{
    public function viewAny(Staff $staff): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::VIEW_APPOINTMENTS);
    }

    public function view(Staff $staff, Appointment $appointment): bool
    {
        if (! AccessControlHelper::staffHasPermission($staff, PermissionList::VIEW_APPOINTMENTS)) {
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
        return AccessControlHelper::staffHasPermission($staff, PermissionList::CREATE_APPOINTMENT);
    }

    public function cancel(Staff $staff): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::CANCEL_APPOINTMENT);
    }

    public function confirm(Staff $staff, Appointment $appointment): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::CONFIRM_APPOINTMENT);
    }

    public function managePatientStatus(Staff $staff): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::MANAGE_PATIENT_STATUS);
    }

    public function assignToDoctor(Staff $staff): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::ASSIGN_PATIENT_TO_DOCTOR);
    }

    public function bookFollowUp(Staff $staff): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::BOOK_FOLLOW_UP);
    }

    public function bookSurgery(Staff $staff): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::BOOK_SURGERY);
    }
}
