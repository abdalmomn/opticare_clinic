<?php

namespace App\Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Model;

class InternalNotification extends Model
{
    protected $table = 'internal_notifications';

    protected $fillable = [
        'user_id', 'type', 'title', 'message',
        'related_type', 'related_id', 'is_read',
    ];

    protected $casts = ['is_read' => 'boolean'];
}
