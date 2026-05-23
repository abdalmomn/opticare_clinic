<?php

namespace App\Modules\Authentication\Repositories;

use App\Modules\Core\Repositories\BaseRepository;
use App\Modules\Authentication\Models\Staff;

class StaffRepository extends BaseRepository
{
    public function __construct(Staff $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?Staff
    {
        return $this->firstWhere('email', $email);
    }

    public function findActiveByEmail(string $email): ?Staff
    {
        return $this->query()
            ->where('email', $email)
            ->where('is_active', true)
            ->first();
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        return $this->query()
            ->where('email', $email)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }


    public function updatePassword(Staff $staff, string $hashedPassword): void
    {
        $staff->update([
            'password'            => $hashedPassword,
            'password_changed_at' => now(),
        ]);
    }

    public function markLastLogin(Staff $staff): void
    {
        $staff->timestamps = false;
        $staff->update(['last_login_at' => now()]);
        $staff->timestamps = true;
    }
}
