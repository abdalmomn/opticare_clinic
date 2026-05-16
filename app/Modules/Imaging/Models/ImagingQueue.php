<?php

namespace App\Modules\Imaging\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Clinic\Models\Room;

class ImagingQueue extends Model
{
    public $timestamps = false;

    protected $table = 'imaging_queue';

    protected $fillable = [
        'imaging_request_id', 'room_id',
        'queue_number', 'status', 'called_at',
    ];

    protected $casts = [
        'called_at'  => 'datetime',
        'created_at' => 'datetime',
    ];

    public function imagingRequest(): BelongsTo
    {
        return $this->belongsTo(ImagingRequest::class, 'imaging_request_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }
}
