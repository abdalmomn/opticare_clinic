<?php

namespace App\Modules\MedicalRecords\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiagnosisCode extends Model
{
    protected $table = 'diagnosis_codes';

    protected $fillable = [
        'code',
        'name_en',
        'name_ar',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function visitDiagnosisCodes(): HasMany
    {
        return $this->hasMany(VisitDiagnosisCode::class, 'diagnosis_code_id');
    }
}
