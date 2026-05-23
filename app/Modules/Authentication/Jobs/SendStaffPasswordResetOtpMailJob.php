<?php

namespace App\Modules\Authentication\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\Authentication\Mail\StaffPasswordResetOtpMail;

class SendStaffPasswordResetOtpMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        public readonly string $email,
        public readonly string $otp
    ) {}

    public function handle(): void
    {
        Mail::to($this->email)->send(
            new StaffPasswordResetOtpMail($this->otp)
        );
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[Auth] Failed to send password reset OTP email.', [
            'email' => $this->email,
            'error' => $exception->getMessage(),
        ]);
    }
}
