<?php

namespace App\Modules\Imaging\Models;

use App\Modules\Appointments\Models\Appointment;
use App\Modules\Authentication\Models\Staff;
use App\Modules\Clinic\Models\ClinicDevice;
use App\Modules\MedicalRecords\Models\ImagingFileNote;
use App\Modules\MedicalRecords\Models\VisitRecord;
use App\Modules\Patients\Models\ClinicPatient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImagingFile extends Model
{
    use SoftDeletes;

    protected $table = 'imaging_files';

    public const SOURCE_TECHNICIAN_UPLOAD = 'technician_upload';

    public const SOURCE_DOCTOR_UPLOAD = 'doctor_upload';

    public const SOURCE_EXTERNAL = 'external';

    protected $fillable = [
        'imaging_request_id',
        'imaging_request_item_id',
        'upload_batch_id',
        'patient_id',
        'visit_record_id',
        'appointment_id',
        'uploaded_by',
        'device_id',
        'source',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'mime_type',
        'device_name',
        'modality',
        'captured_at',
        'uploaded_at',
        'is_primary',
        'image_type',
        'eye',
        'region',
        'image_label',
        'thumbnail_path',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'captured_at' => 'datetime',
        'uploaded_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(ImagingRequest::class, 'imaging_request_id');
    }

    public function imagingRequest(): BelongsTo
    {
        return $this->request();
    }

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

    public function item(): BelongsTo
    {
        return $this->belongsTo(ImagingRequestItem::class, 'imaging_request_item_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'uploaded_by');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->uploader();
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(ClinicDevice::class, 'device_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ImagingFileNote::class, 'imaging_file_id');
    }

    public function doctorNotes(): HasMany
    {
        return $this->notes();
    }
}
