<?php

namespace App\Modules\RolesPermissions\Policies;

use App\Modules\Authentication\Models\Staff;
use App\Modules\Imaging\Models\ImagingRequest;
use App\Modules\RolesPermissions\Constants\PermissionList;

class ImagingPolicy
{
    public function viewAny(Staff $staff): bool
    {
        return $staff->hasPermissionTo(PermissionList::VIEW_IMAGING_REQUESTS, 'api');
    }

    public function create(Staff $staff): bool
    {
        return $staff->hasPermissionTo(PermissionList::CREATE_IMAGING_REQUEST, 'api');
    }

    public function confirm(Staff $staff, ImagingRequest $request): bool
    {
        return $staff->hasPermissionTo(PermissionList::CONFIRM_IMAGING_REQUEST, 'api');
    }

    public function uploadFiles(Staff $staff, ImagingRequest $request): bool
    {
        return $staff->hasPermissionTo(PermissionList::UPLOAD_IMAGING_FILES, 'api');
    }

    public function deleteFile(Staff $staff): bool
    {
        return $staff->hasPermissionTo(PermissionList::DELETE_IMAGING_FILE, 'api');
    }

    public function viewQueue(Staff $staff): bool
    {
        return $staff->hasPermissionTo(PermissionList::VIEW_IMAGING_QUEUE, 'api');
    }

    public function manageQueue(Staff $staff): bool
    {
        return $staff->hasPermissionTo(PermissionList::MANAGE_IMAGING_QUEUE, 'api');
    }

    public function updateStatus(Staff $staff, ImagingRequest $request): bool
    {
        if (! $staff->hasPermissionTo(PermissionList::UPDATE_IMAGING_STATUS, 'api')) {
            return false;
        }

        return $request->status === 'in_progress';
    }
}
