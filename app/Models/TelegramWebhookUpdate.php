<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramWebhookUpdate extends Model
{
    protected $fillable = ['update_id', 'payload', 'processed_at', 'failed_at', 'failure_reason'];

    protected $casts = ['payload' => 'array', 'processed_at' => 'datetime', 'failed_at' => 'datetime'];
}
