<?php

namespace App\Modules\Authentication\Events;

class StaffPasswordResetOtpRequested
{
    public function __construct(
        public readonly string $email,
        public readonly string $otp
    ) {}
}
