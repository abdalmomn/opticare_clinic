<?php

namespace App\Modules\Clinic\Models;

use App\Modules\Authentication\Models\Staff;
use App\Modules\Imaging\Models\ImagingFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicDevice extends Model
{
    protected $table = 'clinic_devices';

    protected $fillable = [
        'room_id',
        'name',
        'device_identifier',
        'serial_number',
        'device_type',
        'manufacturer',
        'model',
        'status',
        'last_maintenance_at',
        'notes',
        'created_by',
        'updated_by',
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

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'updated_by');
    }

    public function imagingFiles(): HasMany
    {
        return $this->hasMany(ImagingFile::class, 'device_id');
    }
}
