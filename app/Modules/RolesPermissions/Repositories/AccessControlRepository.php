<?php

namespace App\Modules\RolesPermissions\Repositories;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Modules\Authentication\Models\Staff;

class AccessControlRepository
{
    public function getRolesWithPermissions(string $guardName = 'api'): Collection
    {
        return Role::query()
            ->where('guard_name', $guardName)
            ->with('permissions:id,name,guard_name')
            ->orderBy('name')
            ->get();
    }

    public function getPermissions(string $guardName = 'api'): Collection
    {
        return Permission::query()
            ->where('guard_name', $guardName)
            ->orderBy('name')
            ->get();
    }

    public function findStaffOrFail(int $staffId): Staff
    {
        return Staff::findOrFail($staffId);
    }
}
