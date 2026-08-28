<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\TelegramBotSetting;
use Illuminate\Http\Request;

class TelegramBotSettingsController extends Controller
{
    public function edit()
    {
        return view('admin.telegram-bot-settings.edit', [
            'settings' => TelegramBotSetting::current(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'tomorrow_notification_time' => 'required|date_format:H:i',
        ]);

        $settings = TelegramBotSetting::current();
        $settings->update([
            'tomorrow_notifications_enabled' => $request->boolean('tomorrow_notifications_enabled'),
            'tomorrow_notification_time' => $data['tomorrow_notification_time'].':00',
        ]);

        return back()->with('success', 'Настройки Telegram-бота сохранены.');
    }
}
