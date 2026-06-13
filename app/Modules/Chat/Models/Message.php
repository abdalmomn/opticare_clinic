<?php

namespace App\Modules\Chat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Chat\Enums\MessageSenderTypeEnum;
use App\Modules\Chat\Enums\MessageTypeEnum;

class Message extends Model
{
    protected $table = 'messages';

    protected $fillable = [
        'conversation_id', 'sender_type', 'sender_id',
        'message', 'message_type', 'attachments', 'is_read',
    ];

    protected $casts = [
        'attachments'  => 'array',
        'is_read'      => 'boolean',
        'sender_type'  => MessageSenderTypeEnum::class,
        'message_type' => MessageTypeEnum::class,
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }
}
