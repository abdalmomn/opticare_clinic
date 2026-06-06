<?php

namespace App\Modules\RolesPermissions\Helpers;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Spatie\Permission\PermissionRegistrar;
use App\Modules\Authentication\Models\Staff;
use App\Modules\RolesPermissions\Enums\RoleEnum;
use App\Modules\RolesPermissions\Models\StaffPermissionOverride;

class AccessControlHelper
{
    public static function actorCanManageRoles(Staff $actor): bool
    {
        return $actor->getRoleNames()
            ->intersect(RoleEnum::canAssignRoles())
            ->isNotEmpty();
    }

    public static function resolveClinicId(?int $clinicId): int
    {
        return $clinicId ?? (int) config('opticare.clinic_id', 1);
    }

    public static function clearPermissionCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public static function ensureRoleCanBeAssigned(Staff $actor, string $roleName): void
    {
        if (! in_array($roleName, RoleEnum::clinicStaffRoles(), true)) {
            throw new HttpException(
                422,
                __('role_permission.errors.role_cannot_be_assigned_inside_clinic_system')
            );
        }

        if (! self::actorCanManageRoles($actor)) {
            throw new HttpException(
                403,
                __('role_permission.errors.not_allowed_assign_roles')
            );
        }
    }

    public static function ensureRoleCanBeRevoked(Staff $actor, Staff $targetStaff, string $roleName): void
    {
        if (! self::actorCanManageRoles($actor)) {
            throw new HttpException(
                403,
                __('role_permission.errors.not_allowed_revoke_roles')
            );
        }

        if (
            (int) $actor->id === (int) $targetStaff->id
            && in_array($roleName, RoleEnum::canAssignRoles(), true)
        ) {
            throw new HttpException(
                403,
                __('role_permission.errors.cannot_revoke_own_admin_role')
            );
        }

        if (! $targetStaff->hasRole($roleName, 'api')) {
            throw new HttpException(
                422,
                __('role_permission.errors.staff_does_not_have_role')
            );
        }
    }

    public static function staffHasPermission(Staff $staff, string $permissionName): bool
{
    $override = StaffPermissionOverride::query()
        ->where('staff_id', $staff->id)
        ->where('permission_name', $permissionName)
        ->active()
        ->first();

    if ($override) {
        return $override->effect === 'grant';
    }

    return $staff->hasPermissionTo($permissionName, 'api');
    }

    public static function actorCanOverridePermissions(Staff $actor): bool
    {
        if (! self::actorCanManageRoles($actor)) {
            return false;
        }

        return $actor->hasPermissionTo(
            \App\Modules\RolesPermissions\Constants\PermissionList::OVERRIDE_PERMISSIONS,
            'api'
        );
    }
}
