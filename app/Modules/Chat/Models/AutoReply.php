<?php

namespace App\Modules\Chat\Models;

use Illuminate\Database\Eloquent\Model;

class AutoReply extends Model
{
    protected $table = 'auto_replies';

    protected $fillable = ['keyword', 'reply_text', 'is_ai_generated'];

    protected $casts = ['is_ai_generated' => 'boolean'];
}
