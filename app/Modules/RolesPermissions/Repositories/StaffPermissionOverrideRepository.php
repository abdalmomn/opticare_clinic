<?php

namespace App\Modules\RolesPermissions\Repositories;

use App\Modules\Core\Repositories\BaseRepository;
use App\Modules\RolesPermissions\Models\StaffPermissionOverride;

class StaffPermissionOverrideRepository extends BaseRepository
{
    public function __construct(StaffPermissionOverride $model)
    {
        parent::__construct($model);
    }

    public function setOverride(
        int $staffId,
        string $permissionName,
        string $effect,
        array $data = []
    ): StaffPermissionOverride {
        return $this->updateOrCreate(
            [
                'staff_id'         => $staffId,
                'permission_name'  => $permissionName,
            ],
            array_merge($data, [
                'effect' => $effect,
            ])
        );
    }

    public function deleteOverride(int $staffId, string $permissionName): int
    {
        return $this->deleteWhere([
            'staff_id'        => $staffId,
            'permission_name' => $permissionName,
        ]);
    }

    public function activeOverrideFor(int $staffId, string $permissionName): ?StaffPermissionOverride
    {
        return $this->query()
            ->where('staff_id', $staffId)
            ->where('permission_name', $permissionName)
            ->active()
            ->first();
    }
}
