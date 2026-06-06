<?php

namespace App\Modules\Authentication\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StaffWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $temporaryPassword,
        public readonly string $role,
        public readonly ?int $clinicId = null
    ) {}

    public function build(): self
    {
        return $this
            ->subject(__('auth.emails.staff_welcome.subject'))
            ->view('emails.auth.staff-welcome')
            ->with([
                'name' => $this->name,
                'email' => $this->email,
                'temporaryPassword' => $this->temporaryPassword,
                'role' => $this->role,
                'clinicId' => $this->clinicId,
                'loginUrl' => config('opticare.clinic_panel_url', config('app.url')),
            ]);
    }
}
