<?php

namespace App\Modules\MedicalRecords\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\Appointments\Models\Appointment;
use App\Modules\Authentication\Models\Staff;
use App\Modules\Imaging\Models\ImagingRequest;
use App\Modules\Payments\Models\Invoice;

class VisitRecord extends Model
{
    public $timestamps = false;

    protected $table = 'visit_records';

    protected $fillable = [
        'appointment_id', 'doctor_id', 'visit_date',
        'symptoms', 'diagnosis', 'notes',
    ];

    protected $casts = [
        'visit_date' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'doctor_id');
    }

    public function vitalSigns(): HasOne
    {
        return $this->hasOne(VitalSign::class, 'visit_record_id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class, 'visit_id');
    }

    public function imagingRequests(): HasMany
    {
        return $this->hasMany(ImagingRequest::class, 'visit_record_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'visit_record_id');
    }

    public function privateNotes(): HasMany
    {
        return $this->hasMany(DoctorPrivateNote::class, 'visit_record_id');
    }
}
