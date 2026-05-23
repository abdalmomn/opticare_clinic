<?php

namespace App\Modules\Authentication\Helpers;

use App\Modules\Authentication\Models\Staff;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class AuthHelper
{
    // ─── OTP ──────────────────────────────────────────────────

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

    // ─── Reset Token ──────────────────────────────────────────

    public static function generateResetToken(int $length = 64): string
    {
        return Str::random($length);
    }

    public static function hashResetToken(string $token): string
    {
        return hash('sha256', $token);
    }

    // ─── Captcha ──────────────────────────────────────────────

    public static function hashCaptchaToken(string $token): string
    {
        return hash('sha256', $token);
    }


    // ─── Temporary Password ───────────────────────────────────

    public static function generateTemporaryPassword(int $length = 12): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#!';

        return substr(str_shuffle(str_repeat($chars, 3)), 0, $length);
    }

    // ─── Staff Data Formatter ─────────────────────────────────

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
}
