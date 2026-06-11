<?php

namespace App\Modules\Imaging\Models;

use App\Modules\Authentication\Models\Staff;
use App\Modules\Clinic\Models\Room;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImagingQueue extends Model
{
    protected $table = 'imaging_queue';

    public const STATUS_WAITING = 'waiting';

    public const STATUS_DISPATCHED = 'dispatched';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'imaging_request_id',
        'room_id',
        'technician_id',
        'queue_number',
        'status',
        'called_at',
        'dispatched_at',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'called_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(ImagingRequest::class, 'imaging_request_id');
    }

    public function imagingRequest(): BelongsTo
    {
        return $this->request();
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'technician_id');
    }
}
