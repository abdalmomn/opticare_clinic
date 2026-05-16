<?php

namespace App\Modules\MedicalRecords\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Clinic\Models\ClinicPatient;
use App\Modules\Authentication\Models\Staff;

class SharedMedicalFile extends Model
{
    protected $table = 'shared_medical_files';

    protected $fillable = [
        'patient_id', 'file_path', 'title',
        'file_type', 'is_active', 'uploaded_by',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ClinicPatient::class, 'patient_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'uploaded_by');
    }
}
