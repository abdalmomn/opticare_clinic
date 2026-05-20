<?php

namespace App\Modules\RolesPermissions\Policies;

use App\Modules\Authentication\Models\Staff;
use App\Modules\RolesPermissions\Constants\PermissionList;

class StaffPolicy
{
    public function viewAny(Staff $staff): bool
    {
        return $staff->hasPermissionTo(PermissionList::VIEW_STAFF, 'api');
    }

    public function create(Staff $staff): bool
    {
        return $staff->hasPermissionTo(PermissionList::CREATE_STAFF, 'api');
    }

    public function edit(Staff $staff, Staff $target): bool
    {
        if ((int) $staff->id === (int) $target->id) {
            return true;
        }

        return $staff->hasPermissionTo(PermissionList::EDIT_STAFF, 'api');
    }

    public function toggleStatus(Staff $staff, Staff $target): bool
    {
        if ((int) $staff->id === (int) $target->id) {
            return false;
        }

        return $staff->hasPermissionTo(PermissionList::TOGGLE_STAFF_STATUS, 'api');
    }

    public function assignRole(Staff $staff): bool
    {
        return $staff->hasPermissionTo(PermissionList::ASSIGN_ROLES, 'api');
    }

    public function revokeRole(Staff $staff): bool
    {
        return $staff->hasPermissionTo(PermissionList::REVOKE_ROLES, 'api');
    }

    public function overridePermissions(Staff $staff): bool
    {
        return $staff->hasPermissionTo(PermissionList::OVERRIDE_PERMISSIONS, 'api');
    }
}
