<?php

namespace App\Modules\Authentication\Repositories;

use App\Modules\Core\Repositories\BaseRepository;
use App\Modules\Authentication\Models\StaffPasswordResetOtp;

class StaffPasswordResetOtpRepository extends BaseRepository
{
    public function __construct(StaffPasswordResetOtp $model)
    {
        parent::__construct($model);
    }

    public function findLatestActiveByEmail(string $email): ?StaffPasswordResetOtp
    {
        return $this->query()
            ->where('email', $email)
            ->whereNull('used_at')
            ->latest()
            ->first();
    }

    public function findValidOtpRecord(string $email): ?StaffPasswordResetOtp
    {
        return $this->query()
            ->where('email', $email)
            ->whereNull('used_at')
            ->whereNull('verified_at')
            ->where('otp_expires_at', '>', now())
            ->latest()
            ->first();
    }

    public function findVerifiedUnusedByResetTokenHash(string $resetTokenHash): ?StaffPasswordResetOtp
    {
        return $this->query()
            ->where('reset_token_hash', $resetTokenHash)
            ->whereNotNull('verified_at')
            ->whereNull('used_at')
            ->where('reset_token_expires_at', '>', now())
            ->first();
    }

    public function createOtpRecord(array $data): StaffPasswordResetOtp
    {
        return $this->create($data);
    }

    public function markAsVerified(StaffPasswordResetOtp $record, string $resetTokenHash, \Carbon\Carbon $expiresAt): void
    {
        $record->update([
            'verified_at'            => now(),
            'reset_token_hash'       => $resetTokenHash,
            'reset_token_expires_at' => $expiresAt,
        ]);
    }

    public function markAsUsed(StaffPasswordResetOtp $record): void
    {
        $record->update(['used_at' => now()]);
    }

    public function updateResendData(StaffPasswordResetOtp $record, string $otpHash, \Carbon\Carbon $otpExpiresAt, \Carbon\Carbon $resendAvailableAt): void
    {
        $record->update([
            'otp_hash'           => $otpHash,
            'otp_expires_at'     => $otpExpiresAt,
            'resend_count'       => $record->resend_count + 1,
            'resend_available_at'=> $resendAvailableAt,
            'verified_at'        => null,
            'reset_token_hash'   => null,
            'reset_token_expires_at' => null,
        ]);
    }

    public function invalidateOldRecords(string $email): void
    {
        $this->query()
            ->where('email', $email)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);
    }
}
