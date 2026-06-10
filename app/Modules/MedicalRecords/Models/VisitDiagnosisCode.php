<?php

namespace App\Modules\MedicalRecords\Models;

use App\Modules\Authentication\Models\Staff;
use App\Modules\Patients\Models\ClinicPatient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitDiagnosisCode extends Model
{
    protected $table = 'visit_diagnosis_codes';

    protected $fillable = [
        'visit_record_id',
        'patient_id',
        'diagnosis_code_id',
        'doctor_id',
        'created_by',
    ];

    public function visitRecord(): BelongsTo
    {
        return $this->belongsTo(VisitRecord::class, 'visit_record_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ClinicPatient::class, 'patient_id');
    }

    public function diagnosisCode(): BelongsTo
    {
        return $this->belongsTo(DiagnosisCode::class, 'diagnosis_code_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'doctor_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }
}
