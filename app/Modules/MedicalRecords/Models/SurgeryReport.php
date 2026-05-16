<?php

namespace App\Modules\MedicalRecords\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurgeryReport extends Model
{
    public $timestamps = false;

    protected $table = 'surgery_reports';

    protected $fillable = ['surgery_id', 'report', 'file_url'];

    protected $casts = ['created_at' => 'datetime'];

    public function surgery(): BelongsTo
    {
        return $this->belongsTo(Surgery::class, 'surgery_id');
    }
}
