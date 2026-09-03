<?php

namespace App\Services;

use App\Exceptions\TelegramApiException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramApiClient
{
    public function call(string $method, array $payload): array
    {
        $token = config('services.telegram.bot_token');
        if (! $token) {
            Log::error('Telegram API request skipped: bot token is missing.', ['method' => $method]);

            throw new TelegramApiException('Не настроен токен Telegram-бота.');
        }

        try {
            $response = Http::timeout(30)->post("https://api.telegram.org/bot{$token}/{$method}", $payload);
        } catch (Throwable $exception) {
            Log::warning('Telegram API request failed.', [
                'method' => $method,
                'error' => $exception->getMessage(),
            ]);

            throw new TelegramApiException('Не удалось подключиться к Telegram API.', previous: $exception);
        }

        return $this->successfulResponse($method, $response->status(), $response->json());
    }

    public function sendPhoto(int|string $chatId, string $path, string $caption, ?array $replyMarkup = null): void
    {
        $token = config('services.telegram.bot_token');
        if (! $token || ! is_file($path)) {
            throw new TelegramApiException('Не удалось подготовить изображение для Telegram.');
        }

        $payload = ['chat_id' => $chatId, 'caption' => $caption];
        if ($replyMarkup) {
            $payload['reply_markup'] = json_encode($replyMarkup, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }

        try {
            $response = Http::timeout(30)
                ->attach('photo', fopen($path, 'r'), 'image.png')
                ->post("https://api.telegram.org/bot{$token}/sendPhoto", $payload);
        } catch (Throwable $exception) {
            Log::warning('Telegram photo upload failed.', ['error' => $exception->getMessage()]);

            throw new TelegramApiException('Не удалось загрузить изображение в Telegram.', previous: $exception);
        }

        $this->successfulResponse('sendPhoto', $response->status(), $response->json());
    }

    public function sendTyping(int|string $chatId): void
    {
        try {
            $this->call('sendChatAction', ['chat_id' => $chatId, 'action' => 'typing']);
        } catch (Throwable $exception) {
            // The typing indicator must never interrupt a bot reply.
            Log::debug('Telegram typing indicator failed.', ['error' => $exception->getMessage()]);
        }
    }

    /** @param array<string, mixed>|mixed $result */
    private function successfulResponse(string $method, int $status, mixed $result): array
    {
        if ($status < 200 || $status >= 300 || ! is_array($result) || ! ($result['ok'] ?? false)) {
            Log::warning('Telegram API returned an unsuccessful response.', [
                'method' => $method,
                'status' => $status,
                'error_code' => is_array($result) ? ($result['error_code'] ?? null) : null,
                'description' => is_array($result) ? ($result['description'] ?? null) : null,
            ]);

            throw new TelegramApiException('Telegram API вернул ошибку: '.(is_array($result) ? ($result['description'] ?? $status) : $status));
        }

        return $result;
    }
}
