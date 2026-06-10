<?php

namespace App\Modules\Patients\Models;

use App\Modules\Authentication\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\MedicalRecords\Models\VisitRecord;
use App\Modules\MedicalRecords\Models\EyeMeasurement;
use App\Modules\MedicalRecords\Models\MedicalReport;
use App\Modules\MedicalRecords\Models\Prescription;
use App\Modules\MedicalRecords\Models\DoctorPrivateNote;

class ClinicPatient extends Model
{
    use SoftDeletes;

    protected $table = 'clinic_patients';

    protected $fillable = [
        'central_user_id',
        'medical_file_number',
        'first_name',
        'father_name',
        'last_name',
        'full_name',
        'national_id',
        'passport_number',
        'birth_date',
        'gender',
        'marital_status',
        'phone',
        'alternate_phone',
        'address',
        'status',
        'is_active',
        'created_by',
        'updated_by',
        'archived_at',
        'archived_by',
        'archive_reason',
        'archive_notes',
        'deceased_at',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'is_active' => 'boolean',
        'archived_at' => 'datetime',
        'deceased_at' => 'date',
    ];

    public function appointments(): HasMany
    {
        return $this->hasMany(\App\Modules\Appointments\Models\Appointment::class, 'patient_id');
    }

    public function medicalRecord(): HasOne
    {
        return $this->hasOne(\App\Modules\MedicalRecords\Models\MedicalRecord::class, 'patient_id');
    }

    public function imagingRequests(): HasMany
    {
        return $this->hasMany(\App\Modules\Imaging\Models\ImagingRequest::class, 'patient_id');
    }

    public function surgeries(): HasMany
    {
        return $this->hasMany(\App\Modules\MedicalRecords\Models\Surgery::class, 'patient_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(\App\Modules\Payments\Models\Invoice::class, 'patient_id');
    }

    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'archived_by');
    }

    public function visitRecords(): HasMany
    {
        return $this->hasMany(VisitRecord::class, 'patient_id');
    }

    public function eyeMeasurements(): HasMany
    {
        return $this->hasMany(EyeMeasurement::class, 'patient_id');
    }

    public function medicalReports(): HasMany
    {
        return $this->hasMany(MedicalReport::class, 'patient_id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class, 'patient_id');
    }

    public function doctorPrivateNotes(): HasMany
    {
        return $this->hasMany(DoctorPrivateNote::class, 'patient_id');
    }
}
