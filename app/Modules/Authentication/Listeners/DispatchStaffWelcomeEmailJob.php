<?php

namespace App\Modules\Authentication\Listeners;

use App\Modules\Authentication\Events\StaffAccountCreated;
use App\Modules\Authentication\Jobs\SendStaffWelcomeEmailJob;

class DispatchStaffWelcomeEmailJob
{
    public function handle(StaffAccountCreated $event): void
    {
        SendStaffWelcomeEmailJob::dispatch(
            name: $event->name,
            email: $event->email,
            temporaryPassword: $event->temporaryPassword,
            role: $event->role,
            clinicId: $event->clinicId,
            locale: $event->locale
        );
    }
}
