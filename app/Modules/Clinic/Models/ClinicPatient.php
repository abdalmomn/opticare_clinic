<?php

namespace App\Modules\Clinic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Modules\Appointments\Models\Appointment;
use App\Modules\MedicalRecords\Models\MedicalRecord;
use App\Modules\MedicalRecords\Models\SharedMedicalFile;
use App\Modules\MedicalRecords\Models\Surgery;
use App\Modules\MedicalRecords\Models\DoctorPrivateNote;
use App\Modules\MedicalRecords\Models\PatientAccessToken;
use App\Modules\Imaging\Models\ImagingRequest;
use App\Modules\Payments\Models\Invoice;
use App\Modules\Chat\Models\Conversation;

class ClinicPatient extends Model
{
    protected $table = 'clinic_patients';

    protected $fillable = [
        'national_id',
        'passport_id',
        'name',
        'phone',
        'address',
        'birth_date',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    // -------------------------
    // Relationships
    // -------------------------

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'patient_id');
    }

    public function medicalRecord(): HasOne
    {
        return $this->hasOne(MedicalRecord::class, 'patient_id');
    }

    public function sharedMedicalFiles(): HasMany
    {
        return $this->hasMany(SharedMedicalFile::class, 'patient_id');
    }

    public function surgeries(): HasMany
    {
        return $this->hasMany(Surgery::class, 'patient_id');
    }

    public function privateNotes(): HasMany
    {
        return $this->hasMany(DoctorPrivateNote::class, 'patient_id');
    }

    public function accessTokens(): HasMany
    {
        return $this->hasMany(PatientAccessToken::class, 'patient_id');
    }

    public function imagingRequests(): HasMany
    {
        return $this->hasMany(ImagingRequest::class, 'patient_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'patient_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'patient_id');
    }
}
