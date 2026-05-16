<?php

namespace App\Modules\MedicalRecords\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Clinic\Models\ClinicPatient;
use App\Modules\Authentication\Models\Staff;

class DoctorPrivateNote extends Model
{
    protected $table = 'doctor_private_notes';

    protected $fillable = [
        'patient_id', 'visit_record_id', 'doctor_id', 'note', 'visibility',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ClinicPatient::class, 'patient_id');
    }

    public function visitRecord(): BelongsTo
    {
        return $this->belongsTo(VisitRecord::class, 'visit_record_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'doctor_id');
    }
}
