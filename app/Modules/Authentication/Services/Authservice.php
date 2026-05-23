<?php

namespace App\Modules\Authentication\Services;

use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Authentication\Models\Staff;
use App\Modules\Authentication\Helpers\AuthHelper;
use App\Modules\Authentication\Repositories\StaffRepository;

class AuthService
{
    public function __construct(
        protected StaffRepository $staffRepository
    ) {}

    // ─── Login ────────────────────────────────────────────────

    public function login(array $data, string $ip, string $userAgent): array
    {
        $staff = $this->staffRepository->findByEmail($data['email']);

        if (! $staff) {
            throw new HttpException(401, 'The provided credentials are incorrect.');
        }

        if (! Hash::check($data['password'], $staff->password)) {
            throw new HttpException(401, 'The provided credentials are incorrect.');
        }

        if (! $staff->isActive()) {
            throw new HttpException(403, 'Your account has been deactivated. Please contact your administrator.');
        }

        $this->staffRepository->markLastLogin($staff);

        $token = $staff->createToken(
            name: 'staff-auth',
            abilities: ['*'],
            expiresAt: null
        )->plainTextToken;

        return [
            'token'      => $token,
            'token_type' => 'Bearer',
            'staff'      => AuthHelper::formatStaffResponse($staff->fresh()),
        ];
    }

    // ─── Logout ───────────────────────────────────────────────

    public function logout(Staff $staff): void
    {
        Staff::find($staff->id)->tokens()->delete();
    }


    public function changePassword(Staff $staff, array $data): void
    {
        if (! Hash::check($data['current_password'], $staff->password)) {
            throw new HttpException(422, 'The current password is incorrect.');
        }

        if (Hash::check($data['password'], $staff->password)) {
            throw new HttpException(422, 'The new password must be different from the current password.');
        }

        $this->staffRepository->updatePassword($staff, Hash::make($data['password']));

        $staff->tokens()
            ->where('id', '!=', $staff->currentAccessToken()->id)
            ->delete();
    }
}
