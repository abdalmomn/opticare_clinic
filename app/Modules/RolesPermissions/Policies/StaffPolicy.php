<?php

namespace App\Modules\RolesPermissions\Policies;

use App\Modules\Authentication\Models\Staff;
use App\Modules\RolesPermissions\Constants\PermissionList;
use App\Modules\RolesPermissions\Helpers\AccessControlHelper;

class StaffPolicy
{
    public function viewAny(Staff $staff): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::VIEW_STAFF);
    }

    public function create(Staff $staff): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::CREATE_STAFF);
    }

    public function edit(Staff $staff, Staff $target): bool
    {
        if ((int) $staff->id === (int) $target->id) {
            return true;
        }

        return AccessControlHelper::staffHasPermission($staff, PermissionList::EDIT_STAFF);
    }

    public function toggleStatus(Staff $staff, Staff $target): bool
    {
        if ((int) $staff->id === (int) $target->id) {
            return false;
        }

        return AccessControlHelper::staffHasPermission($staff, PermissionList::TOGGLE_STAFF_STATUS);
    }

    public function assignRole(Staff $staff): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::ASSIGN_ROLES);
    }

    public function revokeRole(Staff $staff): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::REVOKE_ROLES);
    }

    public function overridePermissions(Staff $staff): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::OVERRIDE_PERMISSIONS);
    }
}
