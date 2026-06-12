<?php

use App\Modules\Appointments\Models\Appointment;
use App\Modules\Authentication\Models\Staff;
use App\Modules\RolesPermissions\Constants\PermissionList;
use App\Modules\RolesPermissions\Helpers\AccessControlHelper;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('appointments', function (Staff $staff) {
    return AccessControlHelper::staffHasPermission(
        $staff,
        PermissionList::VIEW_APPOINTMENTS
    );
}, ['guards' => ['sanctum']]);

Broadcast::channel('appointments.{appointmentId}', function (Staff $staff, int $appointmentId) {
    return AccessControlHelper::staffHasPermission(
        $staff,
        PermissionList::VIEW_APPOINTMENTS
    ) && Appointment::query()->whereKey($appointmentId)->exists();
}, ['guards' => ['sanctum']]);

Broadcast::channel('staff.{staffId}.appointments', function (Staff $staff, int $staffId) {
    return (int) $staff->id === (int) $staffId
        && AccessControlHelper::staffHasPermission(
            $staff,
            PermissionList::VIEW_APPOINTMENTS
        );
}, ['guards' => ['sanctum']]);
