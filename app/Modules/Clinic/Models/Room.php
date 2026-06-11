<?php

namespace App\Modules\Clinic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Imaging\Models\ImagingRequest;
use App\Modules\Imaging\Models\ImagingQueue;
use App\Modules\Scheduling\Models\Schedule;
use App\Modules\Scheduling\Models\ScheduleException;

class Room extends Model
{
    protected $table = 'rooms';

    protected $fillable = [
        'name',
        'room_type',
        'parent_room_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // -------------------------
    // Relationships
    // -------------------------

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'parent_room_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Room::class, 'parent_room_id');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(ClinicDevice::class, 'room_id');
    }

    public function imagingRequests(): HasMany
    {
        return $this->hasMany(ImagingRequest::class, 'room_id');
    }

    public function imagingQueue(): HasMany
    {
        return $this->hasMany(ImagingQueue::class, 'room_id');
    }

    public function imagingQueues(): HasMany
    {
        return $this->hasMany(ImagingQueue::class, 'room_id');
    }

    public function clinicDevices(): HasMany
    {
        return $this->hasMany(ClinicDevice::class, 'room_id');
    }

    public function schedules()
    {
        return $this->morphMany(Schedule::class, 'schedulable');
    }

    public function scheduleExceptions()
    {
        return $this->morphMany(ScheduleException::class, 'schedulable');
    }
}
