<?php

namespace App\Modules\Scheduling\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ScheduleException extends Model
{
    protected $table = 'schedule_exceptions';

    protected $fillable = [
        'schedulable_type', 'schedulable_id', 'exception_date',
        'start_time', 'end_time', 'type', 'reason', 'is_full_day',
    ];

    protected $casts = [
        'exception_date' => 'date',
        'is_full_day'    => 'boolean',
    ];

    public function schedulable(): MorphTo
    {
        return $this->morphTo();
    }
}
