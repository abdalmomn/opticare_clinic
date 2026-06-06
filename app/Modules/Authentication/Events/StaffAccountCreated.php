<?php

namespace App\Modules\Authentication\Events;

class StaffAccountCreated
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $temporaryPassword,
        public readonly string $role,
        public readonly ?int $clinicId = null,
        public readonly string $locale = 'en'
    ) {}
}
