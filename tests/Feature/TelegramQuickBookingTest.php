<?php

namespace Tests\Feature;

use App\Http\Controllers\Telegram\TelegramBotController;
use App\Models\Animal;
use App\Models\Boarding;
use App\Models\Category;
use App\Models\Client;
use App\Models\ServiceOrder;
use App\Models\TelegramBotSession;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramQuickBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-11 10:00:00');
        config()->set('services.telegram.bot_token', 'test-token');
        config()->set('services.telegram.allowed_user_ids', ['100']);
        Http::fake(['*' => Http::response(['ok' => true, 'result' => []])]);
    }

    public function test_records_today_lists_legacy_boardings_and_service_orders(): void
    {
        $category = $this->category('Кошки');
        $animal = Animal::create([
            'category_id' => $category->id,
            'name' => 'Мурка',
            'species' => $category->name,
            'order' => 1,
        ]);
        Boarding::create([
            'animal_id' => $animal->id,
            'name' => $animal->name,
            'service_type' => 'передержка',
            'unit_price' => 500,
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-12',
        ]);
        $order = ServiceOrder::create([
            'service_type' => 'уход',
            'units_per_day' => 1,
            'daily_price' => 500,
            'start_date' => '2026-09-11',
            'end_date' => '2026-09-11',
            'status' => 'active',
        ]);
        $order->animals()->create(['label' => 'Пушок', 'quantity' => 1]);

        $this->sendText('Записи сегодня');

        $message = $this->lastMessage();
        $this->assertStringContainsString('Мурка', $message['text']);
        $this->assertStringContainsString('Пушок', $message['text']);
        $this->assertSame('Записи сегодня', $message['reply_markup']['keyboard'][0][0]['text']);
        $this->assertSame('Календарь', $message['reply_markup']['keyboard'][1][0]['text']);
        $this->assertTrue($message['reply_markup']['is_persistent']);
    }

    public function test_records_tomorrow_uses_the_next_day(): void
    {
        $order = ServiceOrder::create([
            'service_type' => 'выгул',
            'units_per_day' => 1,
            'daily_price' => 500,
            'start_date' => '2026-09-12',
            'end_date' => '2026-09-12',
            'status' => 'active',
        ]);
        $order->animals()->create(['label' => 'Рекс', 'quantity' => 1]);

        $this->sendText('Записи завтра');

        $message = $this->lastMessage();
        $this->assertStringContainsString('12 сентября', $message['text']);
        $this->assertStringContainsString('Рекс', $message['text']);
    }

    public function test_quick_booking_starts_in_its_own_session_with_inline_services(): void
    {
        $this->sendText('➕ Добавить запись');

        $session = TelegramBotSession::where('telegram_user_id', '100')->firstOrFail();
        $this->assertSame('quick_service', $session->state);
        $message = $this->lastMessage();
        $this->assertArrayHasKey('inline_keyboard', $message['reply_markup']);
        $this->assertArrayNotHasKey('keyboard', $message['reply_markup']);
        $this->assertSame('quick_service:boarding', $message['reply_markup']['inline_keyboard'][0][0]['callback_data']);
    }

    public function test_partial_search_displays_multiple_same_named_pets_with_their_owners(): void
    {
        $category = $this->category('Собаки');
        $catCategory = $this->category('Кошки');
        $ivan = Client::create(['name' => 'Иван']);
        $anna = Client::create(['name' => 'Анна']);
        Animal::create(['client_id' => $ivan->id, 'category_id' => $category->id, 'name' => 'Бобик', 'species' => 'Собаки', 'order' => 1]);
        Animal::create(['client_id' => $anna->id, 'category_id' => $catCategory->id, 'name' => 'Бобик-младший', 'species' => 'Кошки', 'order' => 2]);

        $this->startExistingWizard();
        $this->sendText('Боб');

        $session = TelegramBotSession::where('telegram_user_id', '100')->firstOrFail();
        $this->assertSame('quick_existing_animal_selection', $session->state);
        $labels = collect($this->lastMessage()['reply_markup']['inline_keyboard'])->flatten(1)->pluck('text');
        $this->assertTrue($labels->contains('Бобик · собака · хозяин Иван'));
        $this->assertTrue($labels->contains('Бобик-младший · кошка · хозяин Анна'));
    }

    public function test_selecting_existing_pet_copies_its_booking_fields_and_opens_dates(): void
    {
        $category = $this->category('Собаки');
        $client = Client::create(['name' => 'Иван', 'phone' => '+79990000000']);
        $animal = Animal::create([
            'client_id' => $client->id,
            'category_id' => $category->id,
            'name' => 'Бобик',
            'species' => 'Собаки',
            'dog_size' => 'small',
            'order' => 1,
        ]);

        $this->startExistingWizard();
        $this->sendText('Боб');
        $this->pressCallback('quick_existing:'.$animal->id);

        $session = TelegramBotSession::where('telegram_user_id', '100')->firstOrFail();
        $this->assertSame('quick_dates', $session->state);
        $this->assertSame($animal->id, $session->payload['animal_id']);
        $this->assertSame($category->id, $session->payload['category_id']);
        $this->assertSame('Собаки', $session->payload['species']);
        $this->assertSame('small', $session->payload['dog_size']);
        $this->assertSame($client->id, $session->payload['client_id']);
        $this->assertSame('Иван', $session->payload['client_name']);
    }

    public function test_new_pet_without_name_gets_a_unique_temporary_name(): void
    {
        $this->category('Собаки');
        Animal::create(['name' => 'Бобик1234', 'species' => 'Собаки', 'order' => 1]);

        $this->startNewWizard();
        $this->sendText('собака');

        $session = TelegramBotSession::where('telegram_user_id', '100')->firstOrFail();
        $this->assertSame('quick_dates', $session->state);
        $this->assertMatchesRegularExpression('/^Бобик\d{4,6}$/', $session->payload['animal_name']);
        $this->assertNotSame('Бобик1234', $session->payload['animal_name']);
        $this->assertTrue($session->payload['generated_animal_name']);
    }

    public function test_confirming_a_dog_can_create_it_when_a_cat_has_the_same_name(): void
    {
        $catCategory = $this->category('Кошки');
        $dogCategory = $this->category('Собаки');
        Animal::create([
            'category_id' => $catCategory->id,
            'name' => 'Мия',
            'species' => 'Кошки',
            'order' => 1,
        ]);

        $this->startNewWizard('care');
        $this->sendText('Мия, собака');
        $this->pressCallback('quick_date:today');
        $this->pressCallback('dog_size:small');
        $this->pressCallback('owner_skip');

        $this->assertDatabaseCount('animals', 1);
        $this->pressCallback('booking_confirm');

        $this->assertSame(2, Animal::where('name', 'Мия')->count());
        $this->assertDatabaseHas('animals', ['name' => 'Мия', 'category_id' => $catCategory->id]);
        $this->assertDatabaseHas('animals', ['name' => 'Мия', 'category_id' => $dogCategory->id]);
        $this->assertDatabaseHas('boardings', ['animal_id' => Animal::where('name', 'Мия')->where('category_id', $dogCategory->id)->value('id')]);
    }

    public function test_today_tomorrow_and_manual_period_are_saved_by_the_date_step(): void
    {
        $category = $this->category('Кошки');

        $this->putQuickDatesSession($category, 'Сегодня');
        $this->pressCallback('quick_date:today');
        $session = TelegramBotSession::where('telegram_user_id', '100')->firstOrFail();
        $this->assertSame('2026-09-11', $session->payload['start_date']);
        $this->assertSame('2026-09-11', $session->payload['end_date']);

        $this->putQuickDatesSession($category, 'Завтра');
        $this->pressCallback('quick_date:tomorrow');
        $session = TelegramBotSession::where('telegram_user_id', '100')->firstOrFail();
        $this->assertSame('2026-09-12', $session->payload['start_date']);
        $this->assertSame('2026-09-12', $session->payload['end_date']);

        $this->assertQuickCustomPeriod($category, '12', '2026-09-12', '2026-09-12');
        $this->assertQuickCustomPeriod($category, '12 сентября', '2026-09-12', '2026-09-12');
        $this->assertQuickCustomPeriod($category, '12, 13, 14', '2026-09-12', '2026-09-14');
        $this->assertQuickCustomPeriod($category, '12–14 сентября', '2026-09-12', '2026-09-14');
        $this->assertQuickCustomPeriod($category, '12.09.2026 — 14.09.2026', '2026-09-12', '2026-09-14');
    }

    public function test_final_callback_is_the_only_step_that_creates_all_booking_records(): void
    {
        $this->category('Кошки');
        $this->startNewWizard('care');
        $this->sendText('Мия, кошка');
        $this->pressCallback('quick_date:today');
        $this->pressCallback('owner_skip');

        $this->assertDatabaseCount('animals', 0);
        $this->assertDatabaseCount('boardings', 0);
        $this->assertDatabaseCount('service_orders', 0);
        $this->assertSame('waiting_booking_confirmation', TelegramBotSession::where('telegram_user_id', '100')->firstOrFail()->state);
        $this->assertSame('booking_confirm', $this->lastMessage()['reply_markup']['inline_keyboard'][0][0]['callback_data']);

        $this->pressCallback('booking_confirm');

        $this->assertDatabaseCount('animals', 1);
        $this->assertDatabaseCount('boardings', 1);
        $this->assertDatabaseCount('service_orders', 1);
        $this->assertDatabaseHas('animals', ['name' => 'Мия']);
        $this->assertDatabaseHas('boardings', ['service_type' => 'уход', 'start_date' => '2026-09-11 00:00:00']);
        $this->assertNull(TelegramBotSession::where('telegram_user_id', '100')->first());
    }

    public function test_generated_name_warning_is_shown_in_confirmation(): void
    {
        $this->category('Кошки');
        $this->startNewWizard();
        $this->sendText('кошка');
        $this->pressCallback('quick_date:today');
        $this->pressCallback('owner_skip');

        $this->assertStringContainsString(
            'Кличка сгенерирована автоматически — её можно переименовать позже',
            $this->lastMessage()['text'],
        );
    }

    private function startExistingWizard(): void
    {
        $this->sendText('➕ Добавить запись');
        $this->pressCallback('quick_service:boarding');
        $this->pressCallback('quick_animal:existing');
    }

    private function startNewWizard(string $service = 'boarding'): void
    {
        $this->sendText('➕ Добавить запись');
        $this->pressCallback('quick_service:'.$service);
        $this->pressCallback('quick_animal:new');
    }

    private function assertQuickCustomPeriod(Category $category, string $input, string $start, string $end): void
    {
        $this->putQuickDatesSession($category, 'Период');
        $this->pressCallback('quick_date:custom');
        $this->sendText($input);
        $session = TelegramBotSession::where('telegram_user_id', '100')->firstOrFail();
        $this->assertSame($start, $session->payload['start_date'], $input);
        $this->assertSame($end, $session->payload['end_date'], $input);
    }

    private function putQuickDatesSession(Category $category, string $name): void
    {
        TelegramBotSession::where('telegram_user_id', '100')->delete();
        TelegramBotSession::create([
            'telegram_user_id' => '100',
            'chat_id' => 200,
            'state' => 'quick_dates',
            'payload' => [
                'service_type' => 'уход',
                'animal_name' => $name,
                'category_id' => $category->id,
                'species' => $category->name,
                'owner_asked' => false,
                'animal_match_checked' => true,
                'pending_photo_file_ids' => [],
            ],
            'expires_at' => now()->addHour(),
        ]);
    }

    private function category(string $name): Category
    {
        return Category::firstOrCreate(['name' => $name], ['slug' => mb_strtolower($name)]);
    }

    private function sendText(string $text): void
    {
        app(TelegramBotController::class)->processUpdate([
            'message' => [
                'message_id' => random_int(1, 100000),
                'from' => ['id' => 100],
                'chat' => ['id' => 200],
                'text' => $text,
            ],
        ]);
    }

    private function pressCallback(string $data): void
    {
        app(TelegramBotController::class)->processUpdate([
            'callback_query' => [
                'id' => 'callback-'.random_int(1, 100000),
                'from' => ['id' => 100],
                'message' => ['chat' => ['id' => 200]],
                'data' => $data,
            ],
        ]);
    }

    private function lastMessage(): array
    {
        $messages = collect(Http::recorded())
            ->filter(fn (array $record): bool => str_ends_with($record[0]->url(), '/sendMessage'))
            ->map(fn (array $record): array => $record[0]->data())
            ->values();

        $this->assertNotEmpty($messages, 'Telegram sendMessage was not called.');

        return $messages->last();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
