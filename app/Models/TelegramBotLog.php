<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramBotLog extends Model
{
    protected $fillable = [
        'telegram_webhook_update_id', 'direction', 'event', 'chat_id', 'telegram_user_id',
        'text', 'context', 'level',
    ];

    protected $casts = ['context' => 'array'];
}
