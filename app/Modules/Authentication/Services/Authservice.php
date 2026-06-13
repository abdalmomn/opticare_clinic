<?php

namespace App\Modules\Authentication\Services;

use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Authentication\Models\Staff;
use App\Modules\Authentication\Helpers\AuthHelper;
use App\Modules\Authentication\Repositories\StaffRepository;
use App\Modules\Core\Exceptions\ApiException;

class AuthService
{
    public function __construct(
        protected StaffRepository $staffRepository
    ) {}

    public function login(array $data, string $ip, string $userAgent): array
    {
        $staff = $this->staffRepository->findByEmail($data['email']);

        if (! $staff) {
            throw new ApiException(
                401,
                __('auth.errors.invalid_credentials'),
                'INVALID_CREDENTIALS'
            );
        }

        if ($staff->password_reset_required) {
            throw new ApiException(
                423,
                __('auth.errors.password_reset_required'),
                'PASSWORD_RESET_REQUIRED'
            );
        }

        if (! Hash::check($data['password'], $staff->password)) {
            $requiresReset = AuthHelper::handleFailedLoginAttempt($staff);

            if ($requiresReset) {
                throw new ApiException(
                    423,
                    __('auth.errors.password_reset_required'),
                    'PASSWORD_RESET_REQUIRED'
                );
            }

            throw new ApiException(
                401,
                __('auth.errors.invalid_credentials'),
                'INVALID_CREDENTIALS'
            );
        }

        if (! $staff->isActive()) {
            throw new ApiException(
                403,
                __('auth.errors.account_deactivated'),
                'ACCOUNT_DEACTIVATED'
            );
        }

        AuthHelper::clearFailedLoginAttempts($staff);

        $this->staffRepository->markLastLogin($staff);

        $expiresAt = now()->addMinutes(AuthHelper::tokenTtlMinutes());

        $token = $staff->createToken(
            name: 'staff-auth',
            abilities: ['*'],
            expiresAt: $expiresAt
        )->plainTextToken;

        $freshStaff = $staff->fresh([
            'activeClinicRoles',
        ]);

        $clinicRoles = $freshStaff->activeClinicRoles;

        return [
            'token'            => $token,
            'token_expires_at' => $expiresAt->toISOString(),
            'clinic_id'        => $clinicRoles->pluck('clinic_id')->first(),
            'staff'            => AuthHelper::formatStaffResponse($freshStaff),
        ];
    }

    public function logout(Staff $staff): void
    {
        Staff::find($staff->id)->tokens()->delete();
    }


    public function changePassword(Staff $staff, array $data): void
    {
        if (! Hash::check($data['current_password'], $staff->password)) {
            throw new HttpException(422, __('auth.errors.current_password_incorrect'));
        }

        if (Hash::check($data['password'], $staff->password)) {
            throw new HttpException(422, __('auth.errors.new_password_same_as_current'));
        }

        $this->staffRepository->updatePassword($staff, Hash::make($data['password']));

        $staff->tokens()
            ->where('id', '!=', $staff->currentAccessToken()->id)
            ->delete();
    }
}
