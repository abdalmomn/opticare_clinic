<?php

namespace App\Modules\MedicalRecords\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Clinic\Models\ClinicPatient;

class PatientAccessToken extends Model
{
    protected $table = 'patient_access_tokens';

    protected $fillable = ['patient_id', 'token', 'expires_at', 'revoked_at'];

    protected $casts = [
        'expires_at'  => 'datetime',
        'revoked_at'  => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ClinicPatient::class, 'patient_id');
    }
}
