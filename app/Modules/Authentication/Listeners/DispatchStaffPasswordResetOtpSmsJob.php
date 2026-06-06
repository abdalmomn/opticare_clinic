<?php

namespace App\Modules\Authentication\Listeners;

use Illuminate\Support\Facades\Log;
use App\Modules\Authentication\Events\StaffPasswordResetOtpRequested;
use App\Modules\Authentication\Jobs\SendStaffPasswordResetOtpSmsJob;

class DispatchStaffPasswordResetOtpSmsJob
{
    public function handle(StaffPasswordResetOtpRequested $event): void
    {
        $channel = config('opticare.otp_channel', 'email');

        if (! in_array($channel, ['sms', 'both'], true)) {
            return;
        }

        if (empty($event->phone)) {
            Log::warning('[Auth] OTP SMS skipped because staff phone is missing.', [
                'email' => $event->email,
            ]);

            return;
        }

        SendStaffPasswordResetOtpSmsJob::dispatch(
            phone: $event->phone,
            otp: $event->otp,
            locale: $event->locale
        );
    }
}
