<?php

namespace App\Modules\RolesPermissions\Repositories;

use App\Modules\Core\Repositories\BaseRepository;
use App\Modules\RolesPermissions\Models\StaffClinicRole;

class StaffClinicRoleRepository extends BaseRepository
{
    public function __construct(StaffClinicRole $model)
    {
        parent::__construct($model);
    }

    public function assignOrUpdateRole(int $staffId,int $clinicId,string $roleName,array $data): StaffClinicRole
    {
        return $this->updateOrCreate(
            [
                'staff_id'  => $staffId,
                'clinic_id' => $clinicId,
                'role_name' => $roleName,
            ],
            $data
        );
    }

    public function deleteStaffRole(int $staffId,int $clinicId,string $roleName): int {
        return $this->deleteWhere([
            'staff_id'  => $staffId,
            'clinic_id' => $clinicId,
            'role_name' => $roleName,
        ]);
    }

    public function staffHasActiveRole(int $staffId,int $clinicId,string $roleName): bool {
        return $this->query()
            ->where('staff_id', $staffId)
            ->where('clinic_id', $clinicId)
            ->where('role_name', $roleName)
            ->active()
            ->exists();
    }
}
