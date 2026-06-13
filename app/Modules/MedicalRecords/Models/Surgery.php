<?php

namespace App\Modules\MedicalRecords\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Modules\Patients\Models\ClinicPatient;
use App\Modules\Authentication\Models\Staff;
use App\Modules\MedicalRecords\Enums\SurgeryStatusEnum;

class Surgery extends Model
{
    public $timestamps = false;

    protected $table = 'surgeries';

    protected $fillable = [
        'patient_id', 'doctor_id', 'surgery_type',
        'status', 'notes', 'surgery_date',
    ];

    protected $casts = [
        'surgery_date' => 'datetime',
        'created_at'   => 'datetime',
        'status'       => SurgeryStatusEnum::class,
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ClinicPatient::class, 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'doctor_id');
    }

    public function report(): HasOne
    {
        return $this->hasOne(SurgeryReport::class, 'surgery_id');
    }
}
