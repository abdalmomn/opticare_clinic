<?php

namespace App\Modules\Scheduling\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Schedule extends Model
{
    protected $table = 'schedules';

    protected $fillable = [
        'schedulable_type', 'schedulable_id',
        'day_of_week', 'start_time', 'end_time', 'is_available',
    ];

    protected $casts = ['is_available' => 'boolean'];

    public function schedulable(): MorphTo
    {
        return $this->morphTo();
    }
}
