<?php

namespace App\Modules\Appointments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Modules\Clinic\Models\ClinicPatient;
use App\Modules\MedicalRecords\Models\VisitRecord;

class Appointment extends Model
{
    protected $table = 'appointments';

    protected $fillable = [
        'national_id', 'username', 'patient_id',
        'status', 'appointment_date',
    ];

    protected $casts = [
        'appointment_date' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ClinicPatient::class, 'patient_id');
    }

    public function visitRecord(): HasOne
    {
        return $this->hasOne(VisitRecord::class, 'appointment_id');
    }
}
