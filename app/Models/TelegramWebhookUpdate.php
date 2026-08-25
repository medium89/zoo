<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramWebhookUpdate extends Model
{
    protected $fillable = ['update_id', 'payload', 'processed_at'];

    protected $casts = ['payload' => 'array', 'processed_at' => 'datetime'];
}
