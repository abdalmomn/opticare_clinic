<?php

namespace App\Modules\Authentication\Helpers;

use App\Modules\Authentication\Models\Staff;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class AuthHelper
{
    public static function generateOtp(int $digits = 6): string
    {
        $min = (int) str_pad('1', $digits, '0');
        $max = (int) str_pad('9', $digits, '9');

        return (string) random_int($min, $max);
    }

    public static function hashOtp(string $otp): string
    {
        return Hash::make($otp);
    }

    public static function verifyOtp(string $plainOtp, string $otpHash): bool
    {
        return Hash::check($plainOtp, $otpHash);
    }


    public static function generateResetToken(int $length = 64): string
    {
        return Str::random($length);
    }

    public static function hashResetToken(string $token): string
    {
        return hash('sha256', $token);
    }


    public static function hashCaptchaToken(string $token): string
    {
        return hash('sha256', $token);
    }



    public static function generateTemporaryPassword(int $length = 12): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#!';

        return substr(str_shuffle(str_repeat($chars, 3)), 0, $length);
    }


    public static function formatStaffResponse(Staff $staff): array
    {
        return [
            'id'          => $staff->id,
            'name'        => $staff->name,
            'email'       => $staff->email,
            'phone'       => $staff->phone ?? null,
            'is_active'   => $staff->is_active,
            'roles'       => $staff->getRoleNames()->values(),
            'permissions' => $staff->getAllPermissions()->pluck('name')->values(),
        ];
    }

    public static function tokenTtlMinutes(): int
    {
        return (int) config(
            'opticare.auth.clinic_token_ttl_minutes',
            config('sanctum.expiration') ?: 1440
        );
    }

    public static function handleFailedLoginAttempt(Staff $staff): bool
    {
        $maxAttempts = (int) config('opticare.auth.max_failed_login_attempts', 5);

        $attempts = ((int) $staff->failed_login_attempts) + 1;

        $requiresReset = $attempts >= $maxAttempts;

        $staff->forceFill([
            'failed_login_attempts' => $attempts,
            'password_reset_required' => $requiresReset,
            'password_reset_required_at' => $requiresReset ? now() : null,
        ])->save();

        return $requiresReset;
    }

    public static function clearFailedLoginAttempts(Staff $staff): void
    {
        if (
            (int) $staff->failed_login_attempts === 0
            && ! $staff->password_reset_required
            && $staff->password_reset_required_at === null
        ) {
            return;
        }

        $staff->forceFill([
            'failed_login_attempts' => 0,
            'password_reset_required' => false,
            'password_reset_required_at' => null,
        ])->save();
    }

    public static function otpChannel(): string
    {
        $channel = config('opticare.otp_channel', 'email');

        if (! in_array($channel, ['email', 'sms', 'both'], true)) {
            return 'email';
        }

        return $channel;
    }

    public static function genericSendCodeResponse(): array
    {
        $baseDelay = (int) config('opticare.otp_resend_base_seconds', 20);

        return [
            'resend_available_at' => now()
                ->addSeconds($baseDelay)
                ->toISOString(),
            'otp_channel' => self::otpChannel(),
        ];
    }

    public static function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        if (str_starts_with($phone, '+')) {
            return $phone;
        }

        $digits = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($digits, '00')) {
            return '+' . substr($digits, 2);
        }

        $countryCode = config('services.traccar_sms.default_country_code', '963');

        if (str_starts_with($digits, '0')) {
            return '+' . $countryCode . substr($digits, 1);
        }

        if (str_starts_with($digits, $countryCode)) {
            return '+' . $digits;
        }

        return '+' . $countryCode . $digits;
    }
}
