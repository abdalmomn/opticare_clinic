<?php

namespace App\Modules\RolesPermissions\Policies;

use App\Modules\Authentication\Models\Staff;
use App\Modules\Imaging\Models\ImagingFile;
use App\Modules\Imaging\Models\ImagingRequest;
use App\Modules\RolesPermissions\Constants\PermissionList;
use App\Modules\RolesPermissions\Helpers\AccessControlHelper;

class ImagingPolicy
{
    public function viewAny(Staff $staff): bool
    {
        return $this->hasAnyPermission($staff, [
            PermissionList::VIEW_ALL_IMAGING_REQUESTS,
            PermissionList::VIEW_IMAGING_REQUESTS,
            PermissionList::VIEW_OWN_IMAGING_REQUESTS,
            PermissionList::VIEW_IMAGING_QUEUE,
        ]);
    }

    public function view(Staff $staff, ImagingRequest $request): bool
    {
        if ($this->hasAnyPermission($staff, [
            PermissionList::VIEW_ALL_IMAGING_REQUESTS,
            PermissionList::VIEW_IMAGING_REQUESTS,
        ])) {
            return true;
        }

        if (AccessControlHelper::staffHasPermission($staff, PermissionList::VIEW_OWN_IMAGING_REQUESTS)) {
            return (int) $request->requested_by === (int) $staff->id;
        }

        if (AccessControlHelper::staffHasPermission($staff, PermissionList::VIEW_IMAGING_QUEUE)) {
            return $request->technician_id === null
                || (int) $request->technician_id === (int) $staff->id;
        }

        return false;
    }

    public function create(Staff $staff): bool
    {
        return $this->hasAnyPermission($staff, [
            PermissionList::CREATE_IMAGING_REQUEST,
            PermissionList::CREATE_IMAGING_REQUEST_FOR_PATIENT,
        ]);
    }

    public function cancel(Staff $staff, ImagingRequest $request): bool
    {
        if (! AccessControlHelper::staffHasPermission($staff, PermissionList::CANCEL_IMAGING_REQUEST)) {
            return false;
        }

        if ($this->hasAnyPermission($staff, [
            PermissionList::VIEW_ALL_IMAGING_REQUESTS,
            PermissionList::VIEW_IMAGING_REQUESTS,
        ])) {
            return true;
        }

        if (AccessControlHelper::staffHasPermission($staff, PermissionList::VIEW_OWN_IMAGING_REQUESTS)) {
            return (int) $request->requested_by === (int) $staff->id;
        }

        return false;
    }

    public function confirm(Staff $staff, ImagingRequest $request): bool
    {
        return $this->confirmPayment($staff, $request);
    }

    public function confirmPayment(Staff $staff, ImagingRequest $request): bool
    {
        return $this->hasAnyPermission($staff, [
            PermissionList::CONFIRM_IMAGING_PAYMENT,
            PermissionList::CONFIRM_IMAGING_REQUEST,
        ]);
    }

    public function sendToTechnician(Staff $staff, ImagingRequest $request): bool
    {
        return AccessControlHelper::staffHasPermission(
            $staff,
            PermissionList::SEND_IMAGING_REQUEST_TO_TECHNICIAN
        );
    }

    public function start(Staff $staff, ImagingRequest $request): bool
    {
        if (! AccessControlHelper::staffHasPermission($staff, PermissionList::START_IMAGING_REQUEST)) {
            return false;
        }

        return $request->status === ImagingRequest::STATUS_READY_FOR_IMAGING
            && ($request->technician_id === null || (int) $request->technician_id === (int) $staff->id);
    }

    public function uploadFiles(Staff $staff, ImagingRequest $request): bool
    {
        if (! AccessControlHelper::staffHasPermission($staff, PermissionList::UPLOAD_IMAGING_FILES)) {
            return false;
        }

        return $request->status === ImagingRequest::STATUS_IN_PROGRESS
            && ($request->technician_id === null || (int) $request->technician_id === (int) $staff->id);
    }

    public function complete(Staff $staff, ImagingRequest $request): bool
    {
        if (! AccessControlHelper::staffHasPermission($staff, PermissionList::COMPLETE_IMAGING_REQUEST)) {
            return false;
        }

        return $request->status === ImagingRequest::STATUS_IN_PROGRESS
            && ($request->technician_id === null || (int) $request->technician_id === (int) $staff->id);
    }

    public function deleteFile(Staff $staff, ImagingFile $file): bool
    {
        if ($this->hasAnyPermission($staff, [
            PermissionList::DELETE_ANY_IMAGING_FILE,
            PermissionList::DELETE_IMAGING_FILE,
        ])) {
            return true;
        }

        return AccessControlHelper::staffHasPermission($staff, PermissionList::DELETE_OWN_IMAGING_FILE)
            && (int) $file->uploaded_by === (int) $staff->id;
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

    public function manageDevices(Staff $staff): bool
    {
        return $this->hasAnyPermission($staff, [
            PermissionList::CREATE_DEVICE,
            PermissionList::EDIT_DEVICE,
            PermissionList::DELETE_DEVICE,
            PermissionList::TOGGLE_DEVICE_STATUS,
        ]);
    }

    public function viewStatistics(Staff $staff): bool
    {
        return AccessControlHelper::staffHasPermission($staff, PermissionList::VIEW_STATISTICS);
    }

    private function hasAnyPermission(Staff $staff, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (AccessControlHelper::staffHasPermission($staff, $permission)) {
                return true;
            }
        }

        return false;
    }
}
