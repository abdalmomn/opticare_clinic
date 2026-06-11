<?php

namespace App\Modules\Imaging\Models;

use App\Modules\Appointments\Models\Appointment;
use App\Modules\Authentication\Models\Staff;
use App\Modules\Clinic\Models\Room;
use App\Modules\MedicalRecords\Models\VisitRecord;
use App\Modules\Patients\Models\ClinicPatient;
use App\Modules\Payments\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ImagingRequest extends Model
{
    protected $table = 'imaging_requests';

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    public const STATUS_PAYMENT_CONFIRMED = 'payment_confirmed';

    public const STATUS_READY_FOR_IMAGING = 'ready_for_imaging';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const LEGACY_STATUS_PENDING = 'pending';

    public const LEGACY_STATUS_CANCELED = 'canceled';

    public const SOURCE_DOCTOR_REQUEST = 'doctor_request';

    public const SOURCE_SECRETARY_REQUEST = 'secretary_request';

    public const SOURCE_DOCTOR_UPLOAD = 'doctor_upload';

    public const SOURCE_EXTERNAL = 'external';

    public const PAYMENT_STATUS_PENDING = 'pending';

    public const PAYMENT_STATUS_CONFIRMED = 'confirmed';

    public const PAYMENT_STATUS_WAIVED = 'waived';

    public const PAYMENT_STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'patient_id',
        'visit_record_id',
        'appointment_id',
        'invoice_item_id',
        'requested_by',
        'room_id',
        'request_type',
        'source',
        'notes',
        'status',
        'priority',
        'payment_status',
        'confirmed_by',
        'sent_to_technician_by',
        'technician_id',
        'created_by',
        'updated_by',
        'payment_confirmed_at',
        'sent_to_technician_at',
        'started_at',
        'completed_at',
        'cancelled_at',
        'cancel_reason',
    ];

    protected $casts = [
        'payment_confirmed_at' => 'datetime',
        'sent_to_technician_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ClinicPatient::class, 'patient_id');
    }

    public function visitRecord(): BelongsTo
    {
        return $this->belongsTo(VisitRecord::class, 'visit_record_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class, 'invoice_item_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'requested_by');
    }

    public function doctor(): BelongsTo
    {
        return $this->requestedBy();
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'confirmed_by');
    }

    public function sentToTechnicianBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'sent_to_technician_by');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'technician_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'updated_by');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ImagingFile::class, 'imaging_request_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ImagingRequestItem::class, 'imaging_request_id');
    }

    public function queue(): HasOne
    {
        return $this->hasOne(ImagingQueue::class, 'imaging_request_id');
    }

    public static function statuses(bool $includeLegacy = false): array
    {
        $statuses = [
            self::STATUS_REQUESTED,
            self::STATUS_PENDING_PAYMENT,
            self::STATUS_PAYMENT_CONFIRMED,
            self::STATUS_READY_FOR_IMAGING,
            self::STATUS_IN_PROGRESS,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
        ];

        return $includeLegacy
            ? [...$statuses, self::LEGACY_STATUS_PENDING, self::LEGACY_STATUS_CANCELED]
            : $statuses;
    }

    public static function normalizeStatus(string $status): string
    {
        return match ($status) {
            self::LEGACY_STATUS_PENDING => self::STATUS_PENDING_PAYMENT,
            self::LEGACY_STATUS_CANCELED => self::STATUS_CANCELLED,
            default => $status,
        };
    }
}
