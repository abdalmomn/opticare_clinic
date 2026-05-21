<?php

namespace App\Modules\RolesPermissions\Policies;

use App\Modules\Authentication\Models\Staff;
use App\Modules\Imaging\Models\ImagingRequest;
use App\Modules\RolesPermissions\Constants\PermissionList;
use App\Modules\RolesPermissions\Helpers\AccessControlHelper;

class ImagingPolicy
{
    public function viewAny(Staff $staff): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::VIEW_IMAGING_REQUESTS);
    }

    public function create(Staff $staff): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::CREATE_IMAGING_REQUEST);
    }

    public function confirm(Staff $staff, ImagingRequest $request): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::CONFIRM_IMAGING_REQUEST);
    }

    public function uploadFiles(Staff $staff, ImagingRequest $request): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::UPLOAD_IMAGING_FILES);
    }

    public function deleteFile(Staff $staff): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::DELETE_IMAGING_FILE);
    }

    public function viewQueue(Staff $staff): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::VIEW_IMAGING_QUEUE);
    }

    public function manageQueue(Staff $staff): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::MANAGE_IMAGING_QUEUE);
    }

    public function updateStatus(Staff $staff, ImagingRequest $request): bool
    {
        if (! AccessControlHelper::staffHasPermission($staff, PermissionList::UPDATE_IMAGING_STATUS)) {
            return false;
        }

        return $request->status === 'in_progress';
    }
}
