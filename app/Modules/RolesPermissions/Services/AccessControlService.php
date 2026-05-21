<?php

namespace App\Modules\RolesPermissions\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Modules\Authentication\Models\Staff;
use App\Modules\RolesPermissions\Enums\RoleEnum;
use App\Modules\RolesPermissions\Helpers\AccessControlHelper;
use App\Modules\RolesPermissions\Repositories\AccessControlRepository;
use App\Modules\RolesPermissions\Repositories\StaffClinicRoleRepository;
use App\Modules\RolesPermissions\Repositories\StaffPermissionOverrideRepository;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AccessControlService
{
    public function __construct(
        protected AccessControlRepository $accessControlRepository,
        protected StaffClinicRoleRepository $staffClinicRoleRepository,
        protected StaffPermissionOverrideRepository $staffPermissionOverrideRepository
    ) {}

    public function roles(): Collection
    {
        return $this->accessControlRepository
            ->getRolesWithPermissions('api')
            ->map(function (Role $role) {
                return [
                    'id'          => $role->id,
                    'name'        => $role->name,
                    'label'       => RoleEnum::tryFrom($role->name)?->label() ?? $role->name,
                    'permissions' => $role->permissions
                        ->pluck('name')
                        ->values(),
                ];
            });
    }

    public function permissions(): Collection
    {
        return $this->accessControlRepository
            ->getPermissions('api')
            ->map(function (Permission $permission) {
                return [
                    'id'   => $permission->id,
                    'name' => $permission->name,
                ];
            });
    }

    public function assignRole(Staff $actor, array $data): array
    {
        $targetStaff = $this->accessControlRepository
            ->findStaffOrFail((int) $data['staff_id']);

        $roleName = $data['role'];

        $clinicId = AccessControlHelper::resolveClinicId(
            $data['clinic_id'] ?? null
        );

        AccessControlHelper::ensureRoleCanBeAssigned(
            $actor,
            $roleName
        );

        return DB::transaction(function () use ($actor, $targetStaff, $roleName, $clinicId, $data) {
            $targetStaff->assignRole($roleName);

            $staffClinicRole = $this->staffClinicRoleRepository->assignOrUpdateRole(
                staffId: $targetStaff->id,
                clinicId: $clinicId,
                roleName: $roleName,
                data: [
                    'is_temporary' => (bool) ($data['is_temporary'] ?? false),
                    'expires_at'   => $data['expires_at'] ?? null,
                    'notes'        => $data['notes'] ?? null,
                    'assigned_by'  => $actor->id,
                ]
            );

            AccessControlHelper::clearPermissionCache();

            return [
                'staff_id'   => $targetStaff->id,
                'staff_name' => $targetStaff->name,
                'role'       => $roleName,
                'clinic_id'  => $clinicId,
                'record_id'  => $staffClinicRole->id,
            ];
        });
    }

    public function revokeRole(Staff $actor, array $data): array
    {
        $targetStaff = $this->accessControlRepository
            ->findStaffOrFail((int) $data['staff_id']);

        $roleName = $data['role'];

        $clinicId = AccessControlHelper::resolveClinicId(
            $data['clinic_id'] ?? null
        );

        AccessControlHelper::ensureRoleCanBeRevoked(
            $actor,
            $targetStaff,
            $roleName
        );

        return DB::transaction(function () use ($targetStaff, $roleName, $clinicId) {
            $targetStaff->removeRole($roleName);

            $this->staffClinicRoleRepository->deleteStaffRole(
                staffId: $targetStaff->id,
                clinicId: $clinicId,
                roleName: $roleName
            );

            AccessControlHelper::clearPermissionCache();

            return [
                'staff_id'   => $targetStaff->id,
                'staff_name' => $targetStaff->name,
                'role'       => $roleName,
                'clinic_id'  => $clinicId,
            ];
        });
    }

    public function grantPermission(Staff $actor, array $data): array
{
    $targetStaff = $this->accessControlRepository
        ->findStaffOrFail((int) $data['staff_id']);

    $permissionName = $data['permission'];

    if (! AccessControlHelper::actorCanOverridePermissions($actor)) {
        throw new HttpException(
            403,
            'You are not allowed to override permissions.'
        );
    }

    $override = $this->staffPermissionOverrideRepository->setOverride(
        staffId: $targetStaff->id,
        permissionName: $permissionName,
        effect: 'grant',
        data: [
            'is_temporary' => (bool) ($data['is_temporary'] ?? false),
            'expires_at'   => $data['expires_at'] ?? null,
            'assigned_by'  => $actor->id,
            'notes'        => $data['notes'] ?? null,
        ]
    );

    AccessControlHelper::clearPermissionCache();

    return [
        'staff_id'    => $targetStaff->id,
        'staff_name'  => $targetStaff->name,
        'permission'  => $permissionName,
        'effect'      => 'grant',
        'override_id' => $override->id,
    ];
}

public function revokePermission(Staff $actor, array $data): array
{
    $targetStaff = $this->accessControlRepository
        ->findStaffOrFail((int) $data['staff_id']);

    $permissionName = $data['permission'];

    if (! AccessControlHelper::actorCanOverridePermissions($actor)) {
        throw new HttpException(
            403,
            'You are not allowed to override permissions.'
        );
    }

    $override = $this->staffPermissionOverrideRepository->setOverride(
        staffId: $targetStaff->id,
        permissionName: $permissionName,
        effect: 'deny',
        data: [
            'is_temporary' => (bool) ($data['is_temporary'] ?? false),
            'expires_at'   => $data['expires_at'] ?? null,
            'assigned_by'  => $actor->id,
            'notes'        => $data['notes'] ?? null,
        ]
    );

    AccessControlHelper::clearPermissionCache();

    return [
        'staff_id'    => $targetStaff->id,
        'staff_name'  => $targetStaff->name,
        'permission'  => $permissionName,
        'effect'      => 'deny',
        'override_id' => $override->id,
    ];
}

    public function clearPermissionOverride(Staff $actor, array $data): array
    {
        $targetStaff = $this->accessControlRepository
            ->findStaffOrFail((int) $data['staff_id']);

        $permissionName = $data['permission'];

        if (! AccessControlHelper::actorCanOverridePermissions($actor)) {
            throw new HttpException(
                403,
                'You are not allowed to override permissions.'
            );
        }

        $this->staffPermissionOverrideRepository->deleteOverride(
            staffId: $targetStaff->id,
            permissionName: $permissionName
        );

        AccessControlHelper::clearPermissionCache();

        return [
            'staff_id'   => $targetStaff->id,
            'staff_name' => $targetStaff->name,
            'permission' => $permissionName,
            'effect'     => 'cleared',
        ];
    }

    public function clearAllPermissionOverride(Staff $actor, array $data): array
    {
        $targetStaff = $this->accessControlRepository
            ->findStaffOrFail((int) $data['staff_id']);

        if (! AccessControlHelper::actorCanOverridePermissions($actor)) {
            throw new HttpException(
                403,
                'You are not allowed to override permissions.'
            );
        }

        if ($this->staffPermissionOverrideRepository->query()->where('staff_id', $targetStaff->id)->count() === 0) {
            throw new HttpException(
                422,
                'The selected staff member does not have any permission overrides.'
            );
        }
        
        $deletedCount = $this->staffPermissionOverrideRepository
            ->query()
            ->where('staff_id', $targetStaff->id)
            ->delete();

        AccessControlHelper::clearPermissionCache();

        return [
            'staff_id'      => $targetStaff->id,
            'staff_name'    => $targetStaff->name,
            'effect'        => 'cleared',
            'deleted_count' => $deletedCount,
        ];
    }
}
