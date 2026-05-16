<?php

namespace App\Modules\Chat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\Clinic\Models\ClinicPatient;
use App\Modules\Authentication\Models\Staff;

class Conversation extends Model
{
    protected $table = 'conversations';

    protected $fillable = [
        'patient_id', 'assigned_staff_id', 'status', 'last_message_at',
    ];

    protected $casts = ['last_message_at' => 'datetime'];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ClinicPatient::class, 'patient_id');
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_staff_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'conversation_id');
    }
}
