<?php

namespace App\Modules\Authentication\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\Authentication\Mail\StaffWelcomeMail;

class SendStaffWelcomeEmailJob implements ShouldQueue, ShouldBeEncrypted
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $temporaryPassword,
        public readonly string $role,
        public readonly ?int $clinicId = null,
        public readonly string $locale = 'en'
    ) {}

    public function handle(): void
    {
        app()->setLocale($this->locale);

        Mail::to($this->email)->send(
            (new StaffWelcomeMail(
                name: $this->name,
                email: $this->email,
                temporaryPassword: $this->temporaryPassword,
                role: $this->role,
                clinicId: $this->clinicId
            ))->locale($this->locale)
        );
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[Auth] Failed to send staff welcome email.', [
            'email' => $this->email,
            'error' => $exception->getMessage(),
        ]);
    }
}
