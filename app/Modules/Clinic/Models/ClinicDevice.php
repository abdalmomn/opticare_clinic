<?php

namespace App\Modules\Clinic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Authentication\Models\Staff;

class ClinicDevice extends Model
{
    protected $table = 'clinic_devices';

    protected $fillable = [
        'room_id', 'name', 'serial_number', 'device_type',
        'manufacturer', 'model', 'status',
        'last_maintenance_at', 'notes', 'created_by',
    ];

    protected $casts = [
        'last_maintenance_at' => 'datetime',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }
}
