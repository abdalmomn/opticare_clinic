<?php

namespace App\Modules\MedicalRecords\Models;

use App\Modules\Appointments\Models\Appointment;
use App\Modules\Authentication\Models\Staff;
use App\Modules\Patients\Models\ClinicPatient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EyeMeasurement extends Model
{
    protected $table = 'eye_measurements';

    protected $fillable = [
        'patient_id',
        'visit_record_id',
        'appointment_id',
        'doctor_id',
        'visual_acuity_od',
        'visual_acuity_os',
        'iop_od',
        'iop_os',
        'notes',
        'measured_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'iop_od' => 'decimal:2',
        'iop_os' => 'decimal:2',
        'measured_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ClinicPatient::class, 'patient_id');
    }

    public function visitRecord(): BelongsTo
    {
        return $this->belongsTo(VisitRecord::class, 'visit_record_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'doctor_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'updated_by');
    }
}
