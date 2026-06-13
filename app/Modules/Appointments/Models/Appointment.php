<?php

namespace App\Modules\Appointments\Models;

use App\Modules\Appointments\Enums\AppointmentStatusEnum;
use App\Modules\Appointments\Enums\AppointmentTypeEnum;
use App\Modules\Authentication\Models\Staff;
use App\Modules\Clinic\Models\Room;
use App\Modules\Imaging\Models\ImagingFile;
use App\Modules\Imaging\Models\ImagingRequest;
use App\Modules\MedicalRecords\Models\EyeMeasurement;
use App\Modules\MedicalRecords\Models\VisitRecord;
use App\Modules\Patients\Models\ClinicPatient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    protected $table = 'appointments';

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'room_id',
        'appointment_at',
        'appointment_date',
        'appointment_time',
        'status',
        'type',
        'source',
        'queue_number',
        'reason',
        'notes',
        'cancel_reason',
        'completion_notes',
        'created_by',
        'updated_by',
        'confirmed_by',
        'cancelled_by',
        'checked_in_by',
        'started_by',
        'completed_by',
        'confirmed_at',
        'cancelled_at',
        'checked_in_at',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'appointment_at' => 'datetime',
            'appointment_date' => 'date',
            'appointment_time' => 'datetime:H:i:s',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'queue_number' => 'integer',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ClinicPatient::class, 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'doctor_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class,'room_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'updated_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'confirmed_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'cancelled_by');
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'checked_in_by');
    }

    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'started_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'completed_by');
    }

    public function visitRecord(): HasOne
    {
        return $this->hasOne(VisitRecord::class, 'appointment_id');
    }

    public function eyeMeasurements(): HasMany
    {
        return $this->hasMany(EyeMeasurement::class, 'appointment_id');
    }

    public function imagingRequests(): HasMany
    {
        return $this->hasMany(ImagingRequest::class, 'appointment_id');
    }

    public function imagingFiles(): HasMany
    {
        return $this->hasMany(ImagingFile::class, 'appointment_id');
    }

    public const STATUS_BOOKED = 'booked';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_WAITING = 'waiting';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_NO_SHOW = 'no_show';

    public static function statuses(): array
    {
        return AppointmentStatusEnum::values();
    }

    public const TYPE_CONSULTATION = 'consultation';
    public const TYPE_FOLLOW_UP = 'follow_up';
    public const TYPE_IMAGING = 'imaging';
    public const TYPE_CONSULTATION_AND_IMAGING = 'consultation_and_imaging';
    public const TYPE_SURGERY_PREPARATION = 'surgery_preparation';

    public static function types(): array
    {
        return AppointmentTypeEnum::values();
    }
}
