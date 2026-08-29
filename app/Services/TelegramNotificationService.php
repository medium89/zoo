<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramNotificationService
{
    public function notifyConfiguredChats(string $text): int
    {
        $token = config('services.telegram.bot_token');
        $chatIds = array_values(array_filter(config('services.telegram.chat_ids', [])));
        if (! $token || $chatIds === []) {
            Log::warning('Telegram notification skipped: bot token or chat IDs are missing.');

            return 0;
        }

        $sent = 0;
        foreach ($chatIds as $chatId) {
            try {
                $response = Http::retry(2, 250)
                    ->timeout(15)
                    ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                        'chat_id' => $chatId,
                        'text' => $text,
                    ]);

                if ($response->successful() && $response->json('ok')) {
                    $sent++;
                    continue;
                }

                Log::warning('Telegram notification was not delivered.', ['chat_id' => $chatId, 'status' => $response->status()]);
            } catch (Throwable $exception) {
                Log::warning('Telegram notification failed.', ['chat_id' => $chatId, 'error' => $exception->getMessage()]);
            }
        }

        return $sent;
    }
}
