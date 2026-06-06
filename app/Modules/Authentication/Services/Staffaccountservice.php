<?php

namespace App\Modules\Authentication\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Authentication\Models\Staff;
use App\Modules\Authentication\Helpers\AuthHelper;
use App\Modules\Authentication\Repositories\StaffRepository;
use App\Modules\Authentication\Events\StaffAccountCreated;
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
            throw new HttpException(403, __('auth.errors.unauthorized_create_staff'));
        }

        $role = $data['role'];
        $clinicId = AccessControlHelper::resolveClinicId($data['clinic_id'] ?? null);

        AccessControlHelper::ensureRoleCanBeAssigned($actor, $role);


        if ($this->staffRepository->emailExists($data['email'])) {
            throw new HttpException(422, __('auth.errors.email_already_exists'));
        }

        $plainPassword = empty($data['password'])
            ? AuthHelper::generateTemporaryPassword()
            : $data['password'];

        return DB::transaction(function () use ($actor, $data, $role, $clinicId, $plainPassword) {
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

            DB::afterCommit(function () use ($newStaff, $plainPassword, $role, $clinicId) {
                event(new StaffAccountCreated(
                    name: $newStaff->name,
                    email: $newStaff->email,
                    temporaryPassword: $plainPassword,
                    role: $role,
                    clinicId: $clinicId,
                    locale: app()->getLocale()
                ));
            });

            return [
                'staff'            => AuthHelper::formatStaffResponse($newStaff->fresh()),
                'clinic_id'        => $clinicId,
                'credentials_sent' => true,
                'note'             => __('auth.notes.credentials_sent'),
            ];
        });
    }
}
