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
            message: 'Roles fetched successfully.'
        );
    }

    public function permissions(): JsonResponse
    {
        $result = $this->accessControlService->permissions();

        return ApiResponse::success(
            data: $result,
            message: 'Permissions fetched successfully.'
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
            message: 'Role assigned successfully.'
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
            message: 'Role revoked successfully.'
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
            message: 'Permission granted successfully.'
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
            message: 'Permission revoked successfully.'
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
            message: 'Permission override cleared successfully.'
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
            message: 'All permission overrides cleared successfully.'
        );
    }
}
