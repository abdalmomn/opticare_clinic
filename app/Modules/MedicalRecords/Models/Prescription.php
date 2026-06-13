<?php

namespace App\Modules\MedicalRecords\Models;

use App\Modules\Authentication\Models\Staff;
use App\Modules\MedicalRecords\Enums\PrescriptionStatusEnum;
use App\Modules\Patients\Models\ClinicPatient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prescription extends Model
{
    protected $table = 'prescriptions';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_FINALIZED = 'finalized';

    protected $fillable = [
        'patient_id',
        'visit_record_id',
        'doctor_id',
        'prescription_text',
        'status',
        'finalized_at',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'finalized_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return PrescriptionStatusEnum::values();
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ClinicPatient::class, 'patient_id');
    }

    public function visitRecord(): BelongsTo
    {
        return $this->belongsTo(VisitRecord::class, 'visit_record_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class, 'prescription_id');
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

    public function isFinalized(): bool
    {
        return $this->status === self::STATUS_FINALIZED;
    }
}
