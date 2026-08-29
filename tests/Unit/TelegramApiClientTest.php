<?php

namespace Tests\Unit;

use App\Exceptions\TelegramApiException;
use App\Services\TelegramApiClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramApiClientTest extends TestCase
{
    public function test_it_sends_a_successful_telegram_request(): void
    {
        config()->set('services.telegram.bot_token', 'test-token');
        Http::fake([
            'https://api.telegram.org/bottest-token/sendMessage' => Http::response(['ok' => true, 'result' => []]),
        ]);

        app(TelegramApiClient::class)->call('sendMessage', ['chat_id' => 1, 'text' => 'Тест']);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.telegram.org/bottest-token/sendMessage'
            && $request['chat_id'] === 1
            && $request['text'] === 'Тест');
    }

    public function test_it_throws_when_telegram_rejects_a_request(): void
    {
        config()->set('services.telegram.bot_token', 'test-token');
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Bad Request'], 400),
        ]);

        $this->expectException(TelegramApiException::class);

        app(TelegramApiClient::class)->call('sendMessage', ['chat_id' => 1, 'text' => 'Тест']);
    }
}
