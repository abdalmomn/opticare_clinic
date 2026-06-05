<?php

namespace App\Modules\Authentication\Listeners;

use App\Modules\Authentication\Events\StaffPasswordResetOtpRequested;
use App\Modules\Authentication\Jobs\SendStaffPasswordResetOtpMailJob;

class DispatchStaffPasswordResetOtpMailJob
{
    public function handle(StaffPasswordResetOtpRequested $event): void
    {
    $channel = config('opticare.otp_channel', 'email');

        if (! in_array($channel, ['email', 'both'], true)) {
            return;
        }
        SendStaffPasswordResetOtpMailJob::dispatch(
            email: $event->email,
            otp: $event->otp
        );
    }
}
