<?php

namespace App\Modules\RolesPermissions\Policies;

use App\Modules\Authentication\Models\Staff;
use App\Modules\RolesPermissions\Constants\PermissionList;
use App\Modules\RolesPermissions\Helpers\AccessControlHelper;

class ReportPolicy
{
    public function assignToSecretary(Staff $staff): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::ASSIGN_REPORT_TO_SECRETARY);
    }

    public function viewAssigned(Staff $staff): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::VIEW_ASSIGNED_REPORTS);
    }

    public function submitDraft(Staff $staff): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::SUBMIT_REPORT_DRAFT);
    }

    public function approveDraft(Staff $staff): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::APPROVE_REPORT_DRAFT);
    }
}
