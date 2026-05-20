<?php

namespace App\Modules\RolesPermissions\Policies;

use App\Modules\Authentication\Models\Staff;
use App\Modules\RolesPermissions\Constants\PermissionList;

class ReportPolicy
{
    public function assignToSecretary(Staff $staff): bool
    {
        return $staff->hasPermissionTo(PermissionList::ASSIGN_REPORT_TO_SECRETARY, 'api');
    }

    public function viewAssigned(Staff $staff): bool
    {
        return $staff->hasPermissionTo(PermissionList::VIEW_ASSIGNED_REPORTS, 'api');
    }

    public function submitDraft(Staff $staff): bool
    {
        return $staff->hasPermissionTo(PermissionList::SUBMIT_REPORT_DRAFT, 'api');
    }

    public function approveDraft(Staff $staff): bool
    {
        return $staff->hasPermissionTo(PermissionList::APPROVE_REPORT_DRAFT, 'api');
    }
}
