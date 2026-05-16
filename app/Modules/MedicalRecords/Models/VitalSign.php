<?php

namespace App\Modules\MedicalRecords\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Authentication\Models\Staff;

class VitalSign extends Model
{
    public $timestamps = false;

    protected $table = 'vital_signs';

    protected $fillable = [
        'visit_record_id', 'recorded_by', 'blood_pressure',
        'heart_rate', 'temperature', 'weight', 'height',
        'oxygen_saturation', 'notes', 'recorded_at',
    ];

    protected $casts = ['recorded_at' => 'datetime'];

    public function visitRecord(): BelongsTo
    {
        return $this->belongsTo(VisitRecord::class, 'visit_record_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'recorded_by');
    }
}
