<?php

namespace Tests\Feature;

use App\Jobs\ProcessTelegramUpdate;
use App\Models\TelegramWebhookUpdate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class TelegramWebhookQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_accepts_an_update_once_and_queues_processing(): void
    {
        config(['services.telegram.webhook_secret' => 'test-secret']);
        Queue::fake();

        $payload = [
            'update_id' => 987654,
            'message' => [
                'chat' => ['id' => 123],
                'from' => ['id' => 123],
                'text' => '/help',
            ],
        ];

        $headers = ['X-Telegram-Bot-Api-Secret-Token' => 'test-secret'];

        $this->postJson('/api/telegram/webhook', $payload, $headers)->assertOk();
        $this->postJson('/api/telegram/webhook', $payload, $headers)->assertOk();

        $this->assertDatabaseCount('telegram_webhook_updates', 1);
        Queue::assertPushed(ProcessTelegramUpdate::class, 1);
    }

    public function test_webhook_rejects_a_request_without_valid_secret(): void
    {
        config(['services.telegram.webhook_secret' => 'test-secret']);

        $this->postJson('/api/telegram/webhook', ['update_id' => 1])
            ->assertForbidden();
    }

    public function test_final_queue_failure_is_recorded_on_the_webhook_update(): void
    {
        $update = TelegramWebhookUpdate::create([
            'update_id' => 987655,
            'payload' => ['update_id' => 987655],
        ]);

        (new ProcessTelegramUpdate($update->id))->failed(new RuntimeException('Telegram is unavailable'));

        $this->assertDatabaseHas('telegram_webhook_updates', [
            'id' => $update->id,
            'failure_reason' => 'Telegram is unavailable',
        ]);
    }
}
