<?php

namespace App\Modules\MedicalRecords\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Clinic\Models\ClinicPatient;

class MedicalRecord extends Model
{
    public $timestamps = false;

    protected $table = 'medical_records';

    protected $fillable = ['patient_id', 'summary', 'last_visit_id'];

    protected $casts = ['updated_at' => 'datetime'];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ClinicPatient::class, 'patient_id');
    }

    public function lastVisit(): BelongsTo
    {
        return $this->belongsTo(VisitRecord::class, 'last_visit_id');
    }
}
