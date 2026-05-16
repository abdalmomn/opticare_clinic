<?php

namespace App\Modules\MedicalRecords\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionItem extends Model
{
    public $timestamps = false;

    protected $table = 'prescription_items';

    protected $fillable = [
        'prescription_id', 'medicine_name',
        'dosage', 'frequency', 'duration',
    ];

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class, 'prescription_id');
    }
}
