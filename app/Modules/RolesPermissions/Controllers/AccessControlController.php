<?php

namespace App\Modules\RolesPermissions\Controllers;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Modules\Authentication\Models\Staff;
use App\Modules\Core\Responses\ApiResponse;
use App\Modules\RolesPermissions\Requests\AssignRoleRequest;
use App\Modules\RolesPermissions\Requests\RevokeRoleRequest;
use App\Modules\RolesPermissions\Services\AccessControlService;
use App\Modules\RolesPermissions\Requests\GrantPermissionRequest;
use App\Modules\RolesPermissions\Requests\RevokePermissionRequest;
use App\Modules\RolesPermissions\Requests\ClearPermissionOverrideRequest;
use App\Modules\RolesPermissions\Requests\ClearAllPermissionOverrideRequest;
use Illuminate\Support\Facades\Auth;

class AccessControlController extends Controller
{
    protected AccessControlService $accessControlService;

    public function __construct(AccessControlService $accessControlService)
    {
        $this->accessControlService = $accessControlService;
    }

    public function index(): JsonResponse
    {
        $result = $this->accessControlService->roles();

        return ApiResponse::success(
            data: $result,
            message: __('role_permission.messages.roles_fetched')
        );
    }

    public function permissions(): JsonResponse
    {
        $result = $this->accessControlService->permissions();

        return ApiResponse::success(
            data: $result,
            message: __('role_permission.messages.permissions_fetched')
        );
    }

    public function assignRole(AssignRoleRequest $request): JsonResponse
    {
        $user = Auth::user();

        $result = $this->accessControlService->assignRole(
            $user,
            $request->validated()
        );

        return ApiResponse::success(
            data: $result,
            message: __('role_permission.messages.role_assigned')
        );
    }

    public function revokeRole(RevokeRoleRequest $request): JsonResponse
    {
        $user = Auth::user();

        $result = $this->accessControlService->revokeRole(
            $user,
            $request->validated()
        );

        return ApiResponse::success(
            data: $result,
            message: __('role_permission.messages.role_revoked')
        );
    }

    public function grantPermission(GrantPermissionRequest $request): JsonResponse
    {
        $user = Auth::user();

        $result = $this->accessControlService->grantPermission(
            $user,
            $request->validated()
        );

        return ApiResponse::success(
            data: $result,
            message: __('role_permission.messages.permission_granted')
        );
    }

    public function revokePermission(RevokePermissionRequest $request): JsonResponse
    {
        $user = Auth::user();

        $result = $this->accessControlService->revokePermission(
            $user,
            $request->validated()
        );

        return ApiResponse::success(
            data: $result,
            message: __('role_permission.messages.permission_revoked')
        );
    }

    public function clearPermissionOverride(ClearPermissionOverrideRequest $request): JsonResponse
    {
        $user = Auth::user();

        $result = $this->accessControlService->clearPermissionOverride(
            $user,
            $request->validated()
        );

        return ApiResponse::success(
            data: $result,
            message: __('role_permission.messages.permission_override_cleared')
            );
    }

    public function clearAllPermissionOverride(ClearAllPermissionOverrideRequest $request): JsonResponse
    {
        $user = Auth::user();

        $result = $this->accessControlService->clearAllPermissionOverride(
            $user,
            $request->validated()
        );

        return ApiResponse::success(
            data: $result,
            message: __('role_permission.messages.all_permission_overrides_cleared')
            );
    }
}
