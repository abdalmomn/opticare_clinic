<?php

namespace App\Modules\Imaging\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\Authentication\Models\Staff;
use App\Modules\MedicalRecords\Models\ImagingFileNote;

class ImagingFile extends Model
{
    public $timestamps = false;

    protected $table = 'imaging_files';

    protected $fillable = [
        'imaging_request_id', 'uploaded_by', 'file_path', 'file_name',
        'file_type', 'file_size', 'mime_type', 'device_name',
        'modality', 'captured_at', 'is_primary',
        'image_type', 'eye', 'region', 'image_label', 'thumbnail_path',
    ];

    protected $casts = [
        'is_primary'  => 'boolean',
        'captured_at' => 'datetime',
        'created_at'  => 'datetime',
    ];

    public function imagingRequest(): BelongsTo
    {
        return $this->belongsTo(ImagingRequest::class, 'imaging_request_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'uploaded_by');
    }

    /**
     * Per-doctor notes attached to this image (MedicalRecords module).
     */
    public function doctorNotes(): HasMany
    {
        return $this->hasMany(ImagingFileNote::class, 'imaging_file_id');
    }
}
