<?php

namespace App\Modules\Authentication\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\Authentication\Services\TraccarSmsService;

class SendStaffPasswordResetOtpSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        public readonly string $phone,
        public readonly string $otp,
        public readonly string $locale = 'en'
    ) {}

    public function handle(TraccarSmsService $smsService): void
    {
        app()->setLocale($this->locale);

        $message = __('auth.sms.password_reset_otp', [
            'otp' => $this->otp,
        ]);

        $smsService->send(
            phone: $this->phone,
            message: $message
        );
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[Auth] Failed to send password reset OTP SMS.', [
            'phone' => $this->phone,
            'error' => $exception->getMessage(),
        ]);
    }
}
