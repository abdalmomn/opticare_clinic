<?php

namespace App\Modules\Authentication\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StaffPasswordResetOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $otp
    ) {}

    public function build(): self
    {
        return $this
            ->subject('OptiCare Password Reset Code')
            ->view('emails.auth.staff-password-reset-otp')
            ->with([
                'otp' => $this->otp,
            ]);
    }
}
