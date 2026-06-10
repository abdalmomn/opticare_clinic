<?php

namespace App\Modules\MedicalRecords\Models;

use App\Modules\Authentication\Models\Staff;
use App\Modules\Imaging\Models\ImagingFile;
use App\Modules\Patients\Models\ClinicPatient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImagingFileNote extends Model
{
    protected $table = 'imaging_file_notes';

    protected $fillable = [
        'imaging_file_id',
        'patient_id',
        'doctor_id',
        'visit_record_id',
        'note',
        'created_by',
        'updated_by',
    ];

    public function imagingFile(): BelongsTo
    {
        return $this->belongsTo(ImagingFile::class, 'imaging_file_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ClinicPatient::class, 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'doctor_id');
    }

    public function visitRecord(): BelongsTo
    {
        return $this->belongsTo(VisitRecord::class, 'visit_record_id');
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
