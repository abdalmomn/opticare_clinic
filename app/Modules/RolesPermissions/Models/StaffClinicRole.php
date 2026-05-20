<?php

namespace App\Modules\RolesPermissions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Authentication\Models\Staff;

class StaffClinicRole extends Model
{
    protected $table = 'staff_clinic_roles';

    protected $fillable = [
        'staff_id',
        'clinic_id',
        'role_name',
        'is_temporary',
        'expires_at',
        'notes',
        'assigned_by',
    ];

    protected $casts = [
        'is_temporary' => 'boolean',
        'expires_at'   => 'datetime',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_by');
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
            ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
                    ->where('expires_at', '<=', now());
    }

    public function scopeForRole($query, string $roleName)
    {
        return $query->where('role_name', $roleName);
    }


    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return ! $this->isExpired();
    }
}
