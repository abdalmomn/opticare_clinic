<?php

namespace App\Modules\Authentication\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Authentication\Models\Staff;
use App\Modules\Authentication\Helpers\AuthHelper;
use App\Modules\Authentication\Repositories\StaffRepository;
use App\Modules\RolesPermissions\Enums\RoleEnum;
use App\Modules\RolesPermissions\Helpers\AccessControlHelper;
use App\Modules\RolesPermissions\Services\AccessControlService;

class StaffAccountService
{
    public function __construct(
        protected StaffRepository $staffRepository,
        protected AccessControlService $accessControlService
    ) {}


    public function createStaff(Staff $actor, array $data): array
    {
        if (! AccessControlHelper::actorCanManageRoles($actor)) {
            throw new HttpException(403, 'You are not authorized to create staff accounts.');
        }

        $role     = $data['role'];
        $clinicId = AccessControlHelper::resolveClinicId($data['clinic_id'] ?? null);

        AccessControlHelper::ensureRoleCanBeAssigned($actor, $role);

        if ($role === RoleEnum::CLINIC_ADMIN->value&& ! $actor->hasRole(RoleEnum::MEDICAL_CENTER_ADMIN->value, 'api')) {
            throw new HttpException(403, 'Only a Medical Center Admin can create a Clinic Admin account.');
        }

        if ($this->staffRepository->emailExists($data['email'])) {
            throw new HttpException(422, 'A staff member with this email already exists.');
        }

        $isTemporaryPassword = empty($data['password']);
        $plainPassword       = $isTemporaryPassword
            ? AuthHelper::generateTemporaryPassword()
            : $data['password'];

        return DB::transaction(function () use ($actor, $data, $role, $clinicId, $plainPassword, $isTemporaryPassword) {

            $newStaff = $this->staffRepository->create([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'phone'     => $data['phone'] ?? null,
                'password'  => Hash::make($plainPassword),
                'is_active' => true,
            ]);

            $this->accessControlService->assignRole($actor, [
                'staff_id'  => $newStaff->id,
                'role'      => $role,
                'clinic_id' => $clinicId,
            ]);

            if (! empty($data['permission_overrides']) && AccessControlHelper::actorCanOverridePermissions($actor)) {
                foreach ($data['permission_overrides'] as $override) {
                    $effect = $override['effect'] ?? 'grant';

                    if ($effect === 'grant') {
                        $this->accessControlService->grantPermission($actor, [
                            'staff_id'   => $newStaff->id,
                            'permission' => $override['permission'],
                        ]);
                    } elseif ($effect === 'deny') {
                        $this->accessControlService->revokePermission($actor, [
                            'staff_id'   => $newStaff->id,
                            'permission' => $override['permission'],
                        ]);
                    }
                }
            }

            $result = [
                'staff'        => AuthHelper::formatStaffResponse($newStaff->fresh()),
                'clinic_id'    => $clinicId,
            ];

            if ($isTemporaryPassword) {
                $result['temporary_password'] = $plainPassword;
                $result['note']               = 'This is a one-time temporary password. The staff member must change it upon first login.';
            }

            return $result;
        });
    }

}
