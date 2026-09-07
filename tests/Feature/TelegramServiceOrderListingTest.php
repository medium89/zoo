<?php

namespace Tests\Feature;

use App\Http\Controllers\Telegram\TelegramBotController;
use App\Models\ServiceOrder;
use App\Models\TelegramBotSession;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use ReflectionMethod;
use Tests\TestCase;

class TelegramServiceOrderListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_bot_lists_an_anonymous_service_order_without_a_legacy_boarding(): void
    {
        Carbon::setTestNow('2026-09-02 12:00:00');
        config()->set('services.telegram.bot_token', 'test-token');
        Http::fake([
            'https://api.telegram.org/bottest-token/sendMessage' => Http::response(['ok' => true, 'result' => []]),
        ]);

        $order = ServiceOrder::create([
            'service_type' => 'уход',
            'units_per_day' => 1,
            'daily_price' => 500,
            'start_date' => '2026-09-03',
            'end_date' => '2026-09-05',
            'source' => 'telegram_bot',
            'status' => 'active',
        ]);
        $position = $order->animals()->create(['label' => '1 кошка', 'quantity' => 1]);
        $position->services()->create([
            'service_order_id' => $order->id,
            'service_type' => 'уход',
            'units_per_day' => 1,
            'unit_price' => 500,
        ]);

        $method = new ReflectionMethod(TelegramBotController::class, 'sendBookingsList');
        $method->setAccessible(true);
        $method->invoke(app(TelegramBotController::class), 1, [
            'start_date' => '2026-09-03',
            'end_date' => '2026-09-05',
        ]);

        Http::assertSentCount(1);
        $request = Http::recorded()->first()[0];
        $this->assertStringContainsString('1 кошка', (string) ($request->data()['text'] ?? ''));
        $this->assertStringContainsString('3–5 сентября', (string) ($request->data()['text'] ?? ''));
    }

    public function test_bot_targets_the_canonical_service_order_for_booking_deletion(): void
    {
        Carbon::setTestNow('2026-09-02 12:00:00');
        config()->set('services.telegram.bot_token', 'test-token');
        Http::fake([
            'https://api.telegram.org/bottest-token/sendMessage' => Http::response(['ok' => true, 'result' => []]),
        ]);

        $order = ServiceOrder::create([
            'service_type' => 'уход',
            'units_per_day' => 1,
            'daily_price' => 500,
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-11',
            'source' => 'telegram_bot',
            'status' => 'active',
        ]);
        $order->animals()->create(['label' => 'Тумсис', 'quantity' => 1]);

        $method = new ReflectionMethod(TelegramBotController::class, 'startBookingDeletion');
        $method->setAccessible(true);
        $method->invoke(app(TelegramBotController::class), 1, 'telegram-user', [
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-11',
            'animal' => ['name' => 'Тумсис'],
        ]);

        $session = TelegramBotSession::where('telegram_user_id', 'telegram-user')->firstOrFail();
        $this->assertSame('waiting_order_delete_confirmation', $session->state);
        $this->assertSame($order->id, $session->payload['delete_order_id']);
        $request = Http::recorded()->first()[0];
        $this->assertStringContainsString('перенесён в архив', (string) ($request->data()['text'] ?? ''));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
