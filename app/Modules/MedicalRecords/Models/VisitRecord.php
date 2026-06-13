<?php

namespace App\Modules\MedicalRecords\Models;

use App\Modules\Appointments\Models\Appointment;
use App\Modules\Authentication\Models\Staff;
use App\Modules\Imaging\Models\ImagingFile;
use App\Modules\Imaging\Models\ImagingRequest;
use App\Modules\MedicalRecords\Enums\VisitRecordStatusEnum;
use App\Modules\MedicalRecords\Enums\VisitTypeEnum;
use App\Modules\Patients\Models\ClinicPatient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class VisitRecord extends Model
{
    protected $table = 'visit_records';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_FINALIZED = 'finalized';

    public const STATUS_CANCELLED = 'cancelled';

    public const TYPE_CONSULTATION = 'consultation';

    public const TYPE_FOLLOW_UP = 'follow_up';

    public const TYPE_EMERGENCY = 'emergency';

    public const TYPE_POST_OP = 'post_op';

    protected $fillable = [
        'patient_id',
        'appointment_id',
        'doctor_id',
        'status',
        'visit_type',
        'visit_at',
        'chief_complaint',
        'symptoms',
        'examination_notes',
        'diagnosis',
        'treatment_plan',
        'notes',
        'finalized_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'visit_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return VisitRecordStatusEnum::values();
    }

    public static function types(): array
    {
        return VisitTypeEnum::values();
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ClinicPatient::class, 'patient_id');
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

    public function eyeMeasurements(): HasMany
    {
        return $this->hasMany(EyeMeasurement::class, 'visit_record_id');
    }

    public function latestEyeMeasurement(): HasOne
    {
        return $this->hasOne(EyeMeasurement::class, 'visit_record_id')->latestOfMany('measured_at');
    }

    public function medicalReports(): HasMany
    {
        return $this->hasMany(MedicalReport::class, 'visit_record_id');
    }

    public function latestMedicalReport(): HasOne
    {
        return $this->hasOne(MedicalReport::class, 'visit_record_id')->latestOfMany();
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class, 'visit_record_id');
    }

    public function latestPrescription(): HasOne
    {
        return $this->hasOne(Prescription::class, 'visit_record_id')->latestOfMany();
    }

    public function diagnosisCodeLinks(): HasMany
    {
        return $this->hasMany(VisitDiagnosisCode::class, 'visit_record_id');
    }

    public function privateNotes(): HasMany
    {
        return $this->hasMany(DoctorPrivateNote::class, 'visit_record_id');
    }

    public function imagingRequests(): HasMany
    {
        return $this->hasMany(ImagingRequest::class, 'visit_record_id');
    }

    public function imagingFiles(): HasMany
    {
        return $this->hasMany(ImagingFile::class, 'visit_record_id');
    }

    public function isFinalized(): bool
    {
        return $this->status === self::STATUS_FINALIZED;
    }
}
