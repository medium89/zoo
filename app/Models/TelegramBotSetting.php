<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelegramBotSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'tomorrow_notifications_enabled',
        'tomorrow_notification_time',
        'last_tomorrow_notification_for',
    ];

    protected $casts = [
        'tomorrow_notifications_enabled' => 'boolean',
        'last_tomorrow_notification_for' => 'date',
    ];

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'tomorrow_notifications_enabled' => true,
            'tomorrow_notification_time' => '22:00:00',
        ]);
    }
}
