<?php

namespace App\Modules\MedicalRecords\Models;

use App\Modules\Imaging\Models\ImagingFile;
use App\Modules\Imaging\Models\ImagingRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalReportImage extends Model
{
    protected $table = 'medical_report_images';

    protected $fillable = [
        'medical_report_id',
        'imaging_request_id',
        'imaging_file_id',
        'notes',
    ];

    public function medicalReport(): BelongsTo
    {
        return $this->belongsTo(MedicalReport::class, 'medical_report_id');
    }

    public function imagingRequest(): BelongsTo
    {
        return $this->belongsTo(ImagingRequest::class, 'imaging_request_id');
    }

    public function imagingFile(): BelongsTo
    {
        return $this->belongsTo(ImagingFile::class, 'imaging_file_id');
    }
}
