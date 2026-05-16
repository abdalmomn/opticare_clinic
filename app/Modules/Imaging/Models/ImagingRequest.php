<?php

namespace App\Modules\Imaging\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Modules\Clinic\Models\ClinicPatient;
use App\Modules\Clinic\Models\Room;
use App\Modules\Authentication\Models\Staff;
use App\Modules\MedicalRecords\Models\VisitRecord;

class ImagingRequest extends Model
{
    public $timestamps = false;

    protected $table = 'imaging_requests';

    protected $fillable = [
        'patient_id', 'visit_record_id', 'requested_by', 'room_id',
        'request_type', 'notes', 'status', 'priority',
    ];

    protected $casts = [
        'created_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ClinicPatient::class, 'patient_id');
    }

    public function visitRecord(): BelongsTo
    {
        return $this->belongsTo(VisitRecord::class, 'visit_record_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'requested_by');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ImagingFile::class, 'imaging_request_id');
    }

    public function queue(): HasOne
    {
        return $this->hasOne(ImagingQueue::class, 'imaging_request_id');
    }
}
