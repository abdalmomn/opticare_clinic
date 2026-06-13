<?php

namespace App\Modules\Payments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\Clinic\Models\ClinicPatient;
use App\Modules\Authentication\Models\Staff;
use App\Modules\MedicalRecords\Models\VisitRecord;
use App\Modules\Payments\Enums\InvoiceStatusEnum;

class Invoice extends Model
{
    public $timestamps = false;

    protected $table = 'invoices';

    protected $fillable = [
        'patient_id', 'visit_record_id', 'invoice_number',
        'total_amount', 'paid_amount', 'remaining_amount',
        'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'status' => InvoiceStatusEnum::class,
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ClinicPatient::class, 'patient_id');
    }

    public function visitRecord(): BelongsTo
    {
        return $this->belongsTo(VisitRecord::class, 'visit_record_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'invoice_id');
    }
}
