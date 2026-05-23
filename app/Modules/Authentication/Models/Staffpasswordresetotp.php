<?php

namespace App\Modules\Authentication\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffPasswordResetOtp extends Model
{
    protected $table = 'staff_password_reset_otps';

    protected $fillable = [
        'staff_id',
        'email',
        'captcha_token_hash',
        'otp_hash',
        'otp_expires_at',
        'resend_count',
        'resend_available_at',
        'reset_token_hash',
        'reset_token_expires_at',
        'verified_at',
        'used_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'otp_expires_at'         => 'datetime',
        'resend_available_at'    => 'datetime',
        'reset_token_expires_at' => 'datetime',
        'verified_at'            => 'datetime',
        'used_at'                => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    // ─── State Helpers ────────────────────────────────────────

    public function isOtpExpired(): bool
    {
        return $this->otp_expires_at->isPast();
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function isResetTokenExpired(): bool
    {
        return $this->reset_token_expires_at === null
            || $this->reset_token_expires_at->isPast();
    }

    public function canResend(): bool
    {
        if ($this->resend_available_at === null) {
            return true;
        }

        return $this->resend_available_at->isPast();
    }

    public function nextResendDelaySeconds(): int
    {
        $base = (int) config('opticare.otp_resend_base_seconds', 20);

        return $base * (int) pow(2, $this->resend_count);
    }
}
