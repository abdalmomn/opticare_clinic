<?php

namespace App\Modules\RolesPermissions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Authentication\Models\Staff;

class StaffPermissionOverride extends Model
{
    protected $table = 'staff_permission_overrides';

    protected $fillable = [
        'staff_id',
        'permission_name',
        'effect',
        'is_temporary',
        'expires_at',
        'assigned_by',
        'notes',
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

    public function scopeForPermission($query, string $permissionName)
    {
        return $query->where('permission_name', $permissionName);
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
