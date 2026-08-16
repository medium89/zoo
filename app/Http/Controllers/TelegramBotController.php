<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Boarding;
use App\Models\BoardingTask;
use App\Models\BoardingTaskRun;
use App\Models\Client;
use App\Models\Category;
use App\Models\TelegramBotSession;
use App\Services\AitunnelService;
use App\Services\BoardingPricingService;
use App\Services\BoardingTaskInstructionParser;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class TelegramBotController extends Controller
{
    public function __construct(
        private readonly AitunnelService $aitunnel,
        private readonly BoardingTaskInstructionParser $taskInstructionParser,
        private readonly BoardingPricingService $pricing,
    ) {
    }

    public function __invoke(Request $request)
    {
        if (!$this->validSecret($request)) {
            return response()->json(['ok' => false], 403);
        }

        $update = $request->all();

        try {
            if (isset($update['callback_query'])) {
                $this->handleCallback($update['callback_query']);
            } elseif (isset($update['message'])) {
                $this->handleMessage($update['message']);
            }
        } catch (Throwable $e) {
            $chatId = data_get($update, 'message.chat.id') ?: data_get($update, 'callback_query.message.chat.id');
            if ($chatId) {
                $this->sendMessage($chatId, 'Не смог обработать запрос: '.$e->getMessage());
            }
        }

        return response()->json(['ok' => true]);
    }

    private function handleMessage(array $message): void
    {
        $fromId = (string)data_get($message, 'from.id');
        $chatId = data_get($message, 'chat.id');

        if (!$this->isAllowed($fromId)) {
            $this->sendMessage($chatId, 'Нет доступа к этому боту.');
            return;
        }

        if (isset($message['photo'])) {
            $this->handlePhoto($message);
            return;
        }

        $text = trim((string)($message['text'] ?? ''));

        if (isset($message['voice'])) {
            $text = $this->transcribeVoice($message['voice']);
            if ($text === '') {
                $this->sendMessage($chatId, 'Не смог распознать голосовое сообщение.');
                return;
            }
            $this->sendMessage($chatId, 'Распознал: '.$text);
        }

        if ($text === '') {
            $this->sendMessage($chatId, 'Напишите команду текстом или отправьте голосовое сообщение.');
            return;
        }

        if (Str::startsWith($text, '/start')) {
            $this->clearSession($fromId);
            $this->sendMessage($chatId, "Готов работать с календарём.\n\nПримеры:\n• с 15 по 18 августа принесут шпица Рауля\n• покажи записи на этот месяц");
            return;
        }

        if ($tasks = $this->taskInstructionParser->parse($text)) {
            $this->startBoardingTaskCreation($chatId, $fromId, $tasks);
            return;
        }

        $session = $this->session($fromId);
        if ($session) {
            if ($this->handleSessionText($session, $chatId, $fromId, $text)) {
                return;
            }
        }

        $intent = $this->aitunnel->extractIntent($text);
        $this->processIntent($chatId, $fromId, $intent);
    }

    private function handleCallback(array $callback): void
    {
        $fromId = (string)data_get($callback, 'from.id');
        $chatId = data_get($callback, 'message.chat.id');
        $data = (string)($callback['data'] ?? '');

        $this->answerCallback($callback['id'] ?? null);

        if (!$this->isAllowed($fromId)) {
            $this->sendMessage($chatId, 'Нет доступа к этому боту.');
            return;
        }

        if (preg_match('/^task:(\d+):(done|cancel)$/', $data, $matches)) {
            $this->handleBoardingTaskCallback((int) $matches[1], $matches[2], $fromId, $chatId);
            return;
        }

        if (Str::startsWith($data, 'task_boarding:')) {
            $this->selectBoardingForTasks($chatId, $fromId, Str::after($data, 'task_boarding:'));
            return;
        }

        $session = $this->session($fromId);

        if ($data === 'cancel' || $data === 'booking_cancel') {
            $this->clearSession($fromId);
            $this->sendMessage($chatId, 'Отменено.');
            return;
        }

        if (!$session) {
            $this->sendMessage($chatId, 'Контекст устарел. Повторите команду.');
            return;
        }

        $payload = $session->payload ?: [];

        if (Str::startsWith($data, 'species:')) {
            $value = Str::after($data, 'species:');
            $payload['species'] = $value === 'none' ? null : $value;
            $this->continueAfterRequiredFields($chatId, $fromId, $payload);
            return;
        }

        if (Str::startsWith($data, 'category:')) {
            $category = Category::find((int) Str::after($data, 'category:'));
            if (!$category) {
                $this->sendMessage($chatId, 'Категория не найдена. Выберите её ещё раз.');
                return;
            }

            $payload['category_id'] = $category->id;
            $payload['species'] = $category->name;
            $this->continueAfterRequiredFields($chatId, $fromId, $payload);
            return;
        }

        if (Str::startsWith($data, 'dog_size:')) {
            $payload['dog_size'] = Str::after($data, 'dog_size:');
            $this->continueAfterRequiredFields($chatId, $fromId, $payload);
            return;
        }

        if ($data === 'owner_skip') {
            $payload['client_name'] = null;
            $payload['client_phone'] = null;
            $payload['client_id'] = null;
            $payload['owner_asked'] = true;
            $this->continueAfterRequiredFields($chatId, $fromId, $payload);
            return;
        }

        if (Str::startsWith($data, 'animal_yes:')) {
            $animal = Animal::with(['client', 'photos'])->find((int)Str::after($data, 'animal_yes:'));
            if (!$animal) {
                $this->sendMessage($chatId, 'Питомец не найден. Повторите команду.');
                return;
            }

            $payload['animal_id'] = $animal->id;
            $payload['animal_name'] = $animal->name;
            $payload['category_id'] = $animal->category_id ?: ($payload['category_id'] ?? null);
            $payload['species'] = $animal->category?->name ?: $animal->species ?: ($payload['species'] ?? null);
            $payload['dog_size'] = $animal->dog_size ?: ($payload['dog_size'] ?? null);
            $payload['client_id'] = $animal->client_id ?: ($payload['client_id'] ?? null);
            $payload['client_name'] = $animal->client?->name ?: ($payload['client_name'] ?? null);
            $this->continueAfterRequiredFields($chatId, $fromId, $payload);
            return;
        }

        if ($data === 'animal_no') {
            unset($payload['animal_id'], $payload['client_id']);
            $payload['animal_match_checked'] = true;
            $this->continueAfterRequiredFields($chatId, $fromId, $payload);
            return;
        }

        if ($data === 'booking_confirm') {
            $boarding = $this->createBookingFromPayload($payload);
            $this->clearSession($fromId);
            $this->sendMessage($chatId, "Запись создана #{$boarding->id}:\n".$this->bookingLine($boarding));
            return;
        }

        if ($data === 'booking_change_price') {
            $this->saveSession($fromId, $chatId, 'waiting_booking_price', $payload);
            $this->sendMessage($chatId, 'Введите новую цену за одну услугу в рублях. Например: 650');
            return;
        }

        if (preg_match('/^booking_units:(\d+)$/', $data, $matches)) {
            $payload['units_per_day'] = (int) $matches[1];
            unset($payload['unit_price']);
            $this->askBookingConfirmation($chatId, $fromId, $payload);
            return;
        }

        if (Str::startsWith($data, 'delete_booking_select:')) {
            $boardingId = (int) Str::after($data, 'delete_booking_select:');
            if (!in_array($boardingId, array_map('intval', $payload['delete_candidate_ids'] ?? []), true)) {
                $this->sendMessage($chatId, 'Эту запись уже нельзя удалить из текущего запроса. Повторите команду.');
                return;
            }

            $boarding = Boarding::with(['animal.client', 'client'])
                ->whereNull('archived_at')
                ->find($boardingId);
            if (!$boarding) {
                $this->clearSession($fromId);
                $this->sendMessage($chatId, 'Запись не найдена или уже удалена.');
                return;
            }

            $this->askDeleteConfirmation($chatId, $fromId, $boarding);
            return;
        }

        if ($data === 'delete_booking_confirm') {
            $boardingId = (int) ($payload['delete_boarding_id'] ?? 0);
            $boarding = Boarding::with(['animal.client', 'client'])
                ->whereNull('archived_at')
                ->find($boardingId);

            if (!$boarding) {
                $this->clearSession($fromId);
                $this->sendMessage($chatId, 'Запись не найдена или уже удалена.');
                return;
            }

            $line = $this->bookingLine($boarding);
            $boarding->delete();
            $this->clearSession($fromId);
            $this->sendMessage($chatId, "Запись удалена:\n{$line}");
            return;
        }

        $this->sendMessage($chatId, 'Неизвестное действие.');
    }

    private function handleSessionText(TelegramBotSession $session, int|string $chatId, string $fromId, string $text): bool
    {
        $payload = $session->payload ?: [];
        $intent = null;

        try {
            $intent = $this->aitunnel->extractIntent($text);
        } catch (Throwable) {
            // Для коротких ответов достаточно локальной обработки.
        }

        $normalized = mb_strtolower(trim($text));
        $isCancel = in_array($normalized, ['отмена', 'отмени', 'cancel'], true) || ($intent['intent'] ?? null) === 'cancel';
        if ($isCancel) {
            $this->clearSession($fromId);
            $this->sendMessage($chatId, 'Отменено.');
            return true;
        }

        if ($session->state === 'waiting_species') {
            if (in_array($normalized, ['без вида', 'без категории', 'не знаю', 'нет'], true)) {
                $payload['category_id'] = null;
                $payload['species'] = null;
                $this->continueAfterRequiredFields($chatId, $fromId, $payload);
                return true;
            }

            $category = $this->categoryFromText($normalized);
            if (!$category) {
                $this->askSpecies($chatId, $payload['animal_name']);
                return true;
            }

            $payload['category_id'] = $category?->id;
            $payload['species'] = $category->name;
            $this->continueAfterRequiredFields($chatId, $fromId, $payload);
            return true;
        }

        if ($session->state === 'waiting_dog_size') {
            $size = $this->normalizeDogSize($normalized);
            if (!$size) {
                $this->askDogSize($chatId, $payload['animal_name']);
                return true;
            }
            $payload['dog_size'] = $size;
            $this->continueAfterRequiredFields($chatId, $fromId, $payload);
            return true;
        }

        if ($session->state === 'waiting_booking_price') {
            $price = (int) preg_replace('/\D+/', '', $text);
            if ($price < 1 || $price > 100000) {
                $this->sendMessage($chatId, 'Укажите цену целым числом от 1 до 100 000 ₽.');
                return true;
            }
            $payload['unit_price'] = $price;
            $this->askBookingConfirmation($chatId, $fromId, $payload);
            return true;
        }

        if ($session->state === 'waiting_owner') {
            if (in_array($normalized, ['без хозяина', 'без клиента', 'нет'], true)) {
                $payload['client_name'] = null;
                $payload['client_phone'] = null;
                $payload['client_id'] = null;
            } else {
                $client = $intent['client'] ?? [];
                $payload['client_name'] = $client['name'] ?? $text;
                $payload['client_phone'] = $client['phone'] ?? null;
                $payload['client_note'] = $client['note'] ?? null;
            }
            $payload['owner_asked'] = true;

            $this->continueAfterRequiredFields($chatId, $fromId, $payload);
            return true;
        }

        return false;
    }

    private function processIntent(int|string $chatId, string $fromId, array $intent): void
    {
        $type = $intent['intent'] ?? 'unknown';

        if ($type === 'list_bookings') {
            $this->sendBookingsList($chatId, $intent);
            return;
        }

        if ($type === 'show_pet') {
            $this->showAnimal($chatId, (string) data_get($intent, 'animal.name'));
            return;
        }

        if ($type === 'show_client') {
            $this->showClient($chatId, (string) data_get($intent, 'client.name'), (string) data_get($intent, 'animal.name'));
            return;
        }

        if ($type === 'delete_booking') {
            $this->startBookingDeletion($chatId, $fromId, $intent);
            return;
        }

        if ($type !== 'create_booking') {
            $this->sendMessage($chatId, 'Не понял команду. Пример: “с 15 по 18 августа принесут шпица Рауля” или “покажи записи на этот месяц”.');
            return;
        }

        $animal = $intent['animal'] ?? [];
        $client = $intent['client'] ?? [];
        $payload = [
            'service_type' => $this->normalizeServiceType($intent['service_type'] ?? null),
            'start_date' => $intent['start_date'] ?? null,
            'end_date' => $intent['end_date'] ?? ($intent['start_date'] ?? null),
            'animal_name' => $animal['name'] ?? null,
            'category_id' => null,
            'species' => $this->normalizeSpecies($animal['species'] ?? null),
            'dog_size' => $this->normalizeDogSize($animal['size'] ?? null),
            'units_per_day' => max(1, min(24, (int) ($intent['units_per_day'] ?? 1))),
            'description' => $animal['description'] ?? null,
            'client_name' => $client['name'] ?? null,
            'client_phone' => $client['phone'] ?? null,
            'client_note' => $client['note'] ?? null,
            'pending_photo_file_ids' => [],
            'owner_asked' => false,
            'animal_match_checked' => false,
        ];

        if (!$payload['animal_name'] || !$payload['start_date'] || !$payload['end_date']) {
            $this->sendMessage($chatId, 'Не хватает данных для записи. Укажите кличку и период.');
            return;
        }

        if (!$payload['category_id']) {
            $this->saveSession($fromId, $chatId, 'waiting_species', $payload);
            $this->askSpecies($chatId, $payload['animal_name']);
            return;
        }

        $this->continueAfterRequiredFields($chatId, $fromId, $payload);
    }

    private function continueAfterRequiredFields(int|string $chatId, string $fromId, array $payload): void
    {
        $matches = $this->matchingAnimals($payload);

        if ($matches->count() > 0 && empty($payload['animal_match_checked']) && empty($payload['animal_id'])) {
            $animal = $matches->first();
            $this->saveSession($fromId, $chatId, 'waiting_animal_match', $payload);
            $this->askAnimalMatch($chatId, $animal);
            return;
        }

        if ($this->isDog($payload['species'] ?? null) && empty($payload['dog_size'])) {
            $this->saveSession($fromId, $chatId, 'waiting_dog_size', $payload);
            $this->askDogSize($chatId, $payload['animal_name']);
            return;
        }

        if (empty($payload['client_id']) && empty($payload['client_name']) && empty($payload['owner_asked'])) {
            $this->saveSession($fromId, $chatId, 'waiting_owner', $payload);
            $this->askOwner($chatId);
            return;
        }

        $this->askBookingConfirmation($chatId, $fromId, $payload);
    }

    private function askSpecies(int|string $chatId, string $animalName): void
    {
        $speciesButtons = Category::orderBy('name')->get(['id', 'name'])
            ->map(fn (Category $category): array => [
                'text' => mb_strimwidth($category->name, 0, 28, '…'),
                'callback_data' => 'category:'.$category->id,
            ])
            ->values()
            ->chunk(2)
            ->map(fn ($row): array => $row->all())
            ->all();

        $speciesButtons[] = [
            ['text' => 'Без категории', 'callback_data' => 'species:none'],
            ['text' => 'Отмена', 'callback_data' => 'cancel'],
        ];

        $this->sendMessage($chatId, "Какая категория у {$animalName}? Выберите её из списка или напишите сообщением.", [
            'inline_keyboard' => $speciesButtons,
        ]);
    }

    private function askOwner(int|string $chatId): void
    {
        $this->sendMessage($chatId, 'Укажите хозяина. Можно написать имя и телефон одним сообщением или выбрать “Без хозяина”.', [
            'inline_keyboard' => [
                [
                    ['text' => 'Без хозяина', 'callback_data' => 'owner_skip'],
                    ['text' => 'Отмена', 'callback_data' => 'cancel'],
                ],
            ],
        ]);
    }

    private function askDogSize(int|string $chatId, string $animalName): void
    {
        $this->sendMessage($chatId, "Какого размера {$animalName}? Это нужно для тарифа.", [
            'inline_keyboard' => [
                [
                    ['text' => 'Мелкая', 'callback_data' => 'dog_size:small'],
                    ['text' => 'Средняя или крупная', 'callback_data' => 'dog_size:large'],
                ],
                [
                    ['text' => 'Отмена', 'callback_data' => 'cancel'],
                ],
            ],
        ]);
    }

    private function askAnimalMatch(int|string $chatId, Animal $animal): void
    {
        $last = $animal->boardings()->latest('start_date')->first();
        $lastText = $last ? ' уже был у нас '.$last->start_date->format('d.m.Y') : ' уже есть в базе';
        $species = $animal->species ? $animal->species.' ' : '';
        $client = $animal->client ? ', хозяин '.$animal->client->name : '';

        $this->sendAnimalPhotos($chatId, $animal, 1);

        $this->sendMessage($chatId, "Это {$species}{$animal->name}{$client}, который{$lastText}?", [
            'inline_keyboard' => [
                [
                    ['text' => 'Да, это он', 'callback_data' => 'animal_yes:'.$animal->id],
                    ['text' => 'Нет, новый', 'callback_data' => 'animal_no'],
                ],
                [
                    ['text' => 'Отмена', 'callback_data' => 'cancel'],
                ],
            ],
        ]);
    }

    private function askBookingConfirmation(int|string $chatId, string $fromId, array $payload): void
    {
        $overlaps = $this->overlaps($payload);
        $payload['overlap_ids'] = $overlaps->pluck('id')->all();
        $payload['units_per_day'] = max(1, min(24, (int) ($payload['units_per_day'] ?? 1)));
        $payload['unit_price'] = (int) ($payload['unit_price'] ?? $this->pricing->defaultRate(
            $payload['service_type'],
            $payload['species'] ?? null,
            $payload['dog_size'] ?? null,
        ));
        $this->saveSession($fromId, $chatId, 'waiting_booking_confirmation', $payload);

        $text = "Будет создана запись:\n";
        $text .= 'Услуга: '.$payload['service_type']."\n";
        $text .= 'Даты: '.$payload['start_date'].' — '.$payload['end_date']."\n";
        $text .= 'Питомец: '.trim(($payload['species'] ? $payload['species'].' ' : '').$payload['animal_name'])."\n";
        if ($this->isDog($payload['species'] ?? null)) {
            $text .= 'Размер: '.($payload['dog_size'] === 'small' ? 'мелкая собака' : 'средняя или крупная собака')."\n";
        }
        $text .= 'Хозяин: '.($payload['client_name'] ?: 'без хозяина')."\n";
        $days = $this->pricing->daysBetween($payload['start_date'], $payload['end_date']);
        $total = $payload['unit_price'] * $payload['units_per_day'] * $days;
        $unitLabel = $payload['service_type'] === 'передержка' ? 'за сутки' : 'за один раз';
        $quantity = $payload['units_per_day'] > 1 ? ' × '.$payload['units_per_day'].' раз в день' : '';
        $text .= 'Стоимость: '.number_format($payload['unit_price'], 0, '.', ' ')." ₽ {$unitLabel}{$quantity}\n";
        $text .= 'Итого: '.number_format($total, 0, '.', ' ').' ₽ за '.$days.' '.$this->daysLabel($days)."\n";

        if ($overlaps->count()) {
            $text .= "\nНа эти даты уже есть другие записи:\n";
            foreach ($overlaps as $row) {
                $text .= '• '.$this->bookingLine($row)."\n";
            }
        }

        $text .= "\nЦена верна?";

        $this->sendMessage($chatId, trim($text), [
            'inline_keyboard' => [
                [
                    ['text' => 'Подтвердить', 'callback_data' => 'booking_confirm'],
                    ['text' => 'Изменить цену', 'callback_data' => 'booking_change_price'],
                ],
                [
                    ['text' => '1 раз/день', 'callback_data' => 'booking_units:1'],
                    ['text' => '2 раза/день', 'callback_data' => 'booking_units:2'],
                    ['text' => '3 раза/день', 'callback_data' => 'booking_units:3'],
                ],
                [
                    ['text' => 'Отмена', 'callback_data' => 'booking_cancel'],
                ],
            ],
        ]);
    }

    private function createBookingFromPayload(array $payload): Boarding
    {
        $client = null;
        if (!empty($payload['client_id'])) {
            $client = Client::find($payload['client_id']);
        } elseif (!empty($payload['client_name'])) {
            $client = Client::firstOrCreate(
                ['name' => $payload['client_name'], 'phone' => $payload['client_phone']],
                ['note' => $payload['client_note'] ?? null]
            );
        }

        $animal = null;
        if (!empty($payload['animal_id'])) {
            $animal = Animal::find($payload['animal_id']);
        }

        if (!$animal) {
            $animal = Animal::create([
                'client_id' => $client?->id,
                'category_id' => $payload['category_id'] ?? null,
                'name' => $payload['animal_name'],
                'species' => $payload['species'] ?? null,
                'dog_size' => $payload['dog_size'] ?? null,
                'description' => $payload['description'] ?? null,
                'order' => (int)Animal::max('order') + 1,
            ]);
        } else {
            if ($client && !$animal->client_id) {
                $animal->client_id = $client->id;
            }
            if (empty($animal->dog_size) && !empty($payload['dog_size'])) {
                $animal->dog_size = $payload['dog_size'];
            }
            if ($animal->isDirty()) {
                $animal->save();
            }
        }

        foreach ($payload['pending_photo_file_ids'] ?? [] as $fileId) {
            $this->storeTelegramPhoto($animal, $fileId);
        }

        return Boarding::create([
            'client_id' => $client?->id ?: $animal->client_id,
            'animal_id' => $animal->id,
            'name' => $animal->name,
            'description' => $payload['description'] ?? $animal->description,
            'service_type' => $payload['service_type'],
            'units_per_day' => $payload['units_per_day'] ?? 1,
            'unit_price' => $payload['unit_price'],
            'source' => 'telegram_bot',
            'status' => 'active',
            'start_date' => $payload['start_date'],
            'end_date' => $payload['end_date'],
            'confirmed_at' => now(),
        ]);
    }

    private function sendBookingsList(int|string $chatId, array $intent): void
    {
        $start = $intent['start_date'] ? Carbon::parse($intent['start_date'])->startOfDay() : now()->startOfMonth();
        $end = $intent['end_date'] ? Carbon::parse($intent['end_date'])->endOfDay() : now()->endOfMonth();

        $rows = Boarding::with(['animal.client', 'client'])
            ->whereNull('archived_at')
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                    ->orWhere(fn ($sub) => $sub->where('start_date', '<=', $start)->where('end_date', '>=', $end));
            })
            ->orderBy('start_date')
            ->get();

        if ($rows->isEmpty()) {
            $this->sendMessage($chatId, 'Записей за период '.$this->russianDatePeriod($start, $end).' нет.');
            return;
        }

        $text = 'Записи: '.$this->russianDatePeriod($start, $end)."\n";
        foreach ($rows->take(40) as $row) {
            $animal = $row->animal;
            $name = $animal?->name ?: $row->name;
            $text .= "\n🐾 {$name}\n";
            $text .= '📅 '.$this->russianDatePeriod($row->start_date, $row->end_date)."\n";
            $text .= '🛎 '.$this->serviceLabel($row->service_type)."\n";
        }

        if ($rows->count() > 40) {
            $text .= '…ещё '.($rows->count() - 40);
        }

        $this->sendMessage($chatId, trim($text));
    }

    private function startBookingDeletion(int|string $chatId, string $fromId, array $intent): void
    {
        $startValue = data_get($intent, 'start_date');
        $endValue = data_get($intent, 'end_date') ?: $startValue;
        $isUpcoming = data_get($intent, 'delete_scope') === 'upcoming';
        $animalName = trim((string) data_get($intent, 'animal.name'));

        if ($isUpcoming && $animalName === '') {
            $this->sendMessage($chatId, 'Для предстоящих записей укажите кличку питомца. Например: «удали все предстоящие записи Пушка».');
            return;
        }

        if ((!$startValue || !$endValue) && !$isUpcoming) {
            $this->sendMessage($chatId, 'Для удаления укажите период записи. Например: «удали Луну с 28 по 30 июля».');
            return;
        }

        $start = $isUpcoming ? now()->startOfDay() : Carbon::parse($startValue)->startOfDay();
        $end = $isUpcoming ? null : Carbon::parse($endValue)->endOfDay();

        $rows = Boarding::with(['animal.client', 'client'])
            ->whereNull('archived_at')
            ->when($animalName !== '', function ($query) use ($animalName) {
                $name = mb_strtolower($animalName);
                $query->where(function ($sub) use ($name) {
                    $sub->whereRaw('LOWER(name) = ?', [$name])
                        ->orWhereHas('animal', fn ($animalQuery) => $animalQuery->whereRaw('LOWER(name) = ?', [$name]));
                });
            })
            ->when($end, fn ($query) => $query->where('start_date', '<=', $end))
            ->where('end_date', '>=', $start)
            ->orderBy('start_date')
            ->limit(8)
            ->get();

        if ($rows->isEmpty()) {
            $subject = $animalName !== '' ? ' для питомца «'.$animalName.'»' : '';
            $period = $isUpcoming ? 'начиная с '.$this->russianDatePeriod($start, $start) : 'за период '.$this->russianDatePeriod($start, $end);
            $this->sendMessage($chatId, 'Активных записей'.$subject.' '.$period.' не найдено.');
            return;
        }

        if ($rows->count() === 1) {
            $this->askDeleteConfirmation($chatId, $fromId, $rows->first());
            return;
        }

        $payload = ['delete_candidate_ids' => $rows->pluck('id')->all()];
        $this->saveSession($fromId, $chatId, 'waiting_delete_selection', $payload);

        $keyboard = $rows->map(fn (Boarding $row) => [[
            'text' => '#'.$row->id.' · '.$this->deleteButtonLabel($row),
            'callback_data' => 'delete_booking_select:'.$row->id,
        ]])->all();
        $keyboard[] = [['text' => 'Отмена', 'callback_data' => 'cancel']];

        $message = $isUpcoming
            ? 'Нашёл предстоящие записи. Выберите одну запись для удаления:'
            : 'Нашёл несколько записей. Выберите, какую удалить:';
        $this->sendMessage($chatId, $message, [
            'inline_keyboard' => $keyboard,
        ]);
    }

    private function askDeleteConfirmation(int|string $chatId, string $fromId, Boarding $boarding): void
    {
        $this->saveSession($fromId, $chatId, 'waiting_delete_confirmation', [
            'delete_boarding_id' => $boarding->id,
        ]);

        $this->sendMessage($chatId, "Будет удалена запись:\n".$this->bookingLine($boarding)."\n\nПитомец и хозяин останутся в базе. Удалить?", [
            'inline_keyboard' => [
                [
                    ['text' => 'Удалить', 'callback_data' => 'delete_booking_confirm'],
                    ['text' => 'Отмена', 'callback_data' => 'cancel'],
                ],
            ],
        ]);
    }

    private function deleteButtonLabel(Boarding $boarding): string
    {
        $name = $boarding->animal?->name ?: $boarding->name;

        return $name.' · '.$this->russianDatePeriod($boarding->start_date, $boarding->end_date);
    }

    private function russianDatePeriod(Carbon $start, Carbon $end): string
    {
        $months = [
            1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля',
            5 => 'мая', 6 => 'июня', 7 => 'июля', 8 => 'августа',
            9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря',
        ];

        $includeStartYear = $start->year !== $end->year;
        $includeEndYear = $end->year !== now()->year || $includeStartYear;
        $startText = $start->day.' '.$months[$start->month].($includeStartYear ? ' '.$start->year : '');

        if ($start->isSameDay($end)) {
            return $startText.($start->year !== now()->year ? ' '.$start->year : '');
        }

        if ($start->isSameMonth($end) && $start->year === $end->year) {
            return $start->day.'–'.$end->day.' '.$months[$end->month].($includeEndYear ? ' '.$end->year : '');
        }

        $endText = $end->day.' '.$months[$end->month].($includeEndYear ? ' '.$end->year : '');

        return $startText.' — '.$endText;
    }

    private function serviceLabel(string $serviceType): string
    {
        return match ($serviceType) {
            'передержка' => 'Передержка',
            'выгул' => 'Выгул',
            'уход' => 'Уход',
            default => mb_convert_case($serviceType, MB_CASE_TITLE, 'UTF-8'),
        };
    }

    private function showAnimal(int|string $chatId, string $name): void
    {
        $name = trim($name);
        if ($name === '') {
            $this->sendMessage($chatId, 'Укажите кличку питомца. Например: «покажи Луну».');
            return;
        }

        $animals = $this->animalsByName($name);
        if ($animals->isEmpty()) {
            $this->sendMessage($chatId, 'Питомец «'.$name.'» не найден.');
            return;
        }

        if ($animals->count() > 1) {
            $this->sendMessage($chatId, 'Нашёл несколько питомцев с кличкой «'.$name.'». Показываю первого; для точного поиска добавьте имя хозяина.');
        }

        $animal = $animals->first();
        $this->sendAnimalPhotos($chatId, $animal);
        $this->sendMessage($chatId, $this->animalInfoText($animal));
    }

    private function showClient(int|string $chatId, string $clientName, string $animalName = ''): void
    {
        $clientName = trim($clientName);
        $animalName = trim($animalName);

        if ($clientName === '' && $animalName !== '') {
            $animal = $this->animalsByName($animalName)->first();
            if ($animal?->client) {
                $this->sendClientInfo($chatId, $animal->client);
                return;
            }
            $this->sendMessage($chatId, 'У питомца «'.$animalName.'» хозяин не указан.');
            return;
        }

        if ($clientName === '') {
            $this->sendMessage($chatId, 'Укажите имя хозяина или кличку питомца. Например: «покажи хозяина Луны».');
            return;
        }

        $clients = Client::with(['animals.photos'])
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($clientName)])
            ->orWhereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($clientName).'%'])
            ->orderBy('name')
            ->limit(5)
            ->get();

        if ($clients->isEmpty()) {
            $this->sendMessage($chatId, 'Хозяин «'.$clientName.'» не найден.');
            return;
        }

        if ($clients->count() > 1) {
            $this->sendMessage($chatId, 'Нашёл несколько хозяев по запросу «'.$clientName.'». Показываю первого.');
        }

        $this->sendClientInfo($chatId, $clients->first());
    }

    private function sendClientInfo(int|string $chatId, Client $client): void
    {
        $client->loadMissing(['animals.photos']);
        $text = "Хозяин: {$client->name}\n";
        $text .= 'Телефон: '.($client->phone ?: 'не указан')."\n";
        $text .= 'Заметка: '.($client->note ?: '—')."\n";
        $text .= 'Питомцы: '.($client->animals->isNotEmpty() ? $client->animals->pluck('name')->join(', ') : 'не добавлены');

        $this->sendMessage($chatId, $text);

        foreach ($client->animals->take(10) as $animal) {
            $this->sendAnimalPhotos($chatId, $animal, 1);
        }
    }

    private function animalsByName(string $name)
    {
        $normalized = mb_strtolower(trim($name));

        return Animal::with(['client', 'photos', 'boardings' => fn ($query) => $query->latest('start_date')])
            ->where(function ($query) use ($normalized) {
                $query->whereRaw('LOWER(name) = ?', [$normalized])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%'.$normalized.'%']);
            })
            ->orderByRaw('LOWER(name) = ? DESC', [$normalized])
            ->orderBy('name')
            ->limit(5)
            ->get();
    }

    private function animalInfoText(Animal $animal): string
    {
        $last = $animal->boardings->first();
        $text = "Питомец: {$animal->name}\n";
        $text .= 'Вид: '.($animal->species ?: 'не указан')."\n";
        if ($animal->dog_size) {
            $text .= 'Размер: '.($animal->dog_size === 'small' ? 'мелкая собака' : 'средняя или крупная собака')."\n";
        }
        $text .= 'Хозяин: '.($animal->client?->name ?: 'не указан')."\n";
        $text .= 'Описание: '.($animal->description ?: '—')."\n";
        $text .= 'Заметки: '.($animal->note ?: '—')."\n";
        $text .= 'Всего записей: '.$animal->boardings->count();

        if ($last) {
            $text .= "\nПоследняя: {$last->service_type}, {$last->start_date->toDateString()} — {$last->end_date->toDateString()}";
        }

        return $text;
    }

    private function sendAnimalPhotos(int|string $chatId, Animal $animal, ?int $limit = null): void
    {
        $photos = $animal->photos;
        if ($limit) {
            $photos = $photos->take($limit);
        }

        foreach ($photos->take(10) as $photo) {
            $source = $photo->telegram_file_id ?: url(Storage::url($photo->path));
            $this->telegramApi('sendPhoto', [
                'chat_id' => $chatId,
                'photo' => $source,
                'caption' => $animal->name,
            ]);
        }
    }

    private function handlePhoto(array $message): void
    {
        $fromId = (string)data_get($message, 'from.id');
        $chatId = data_get($message, 'chat.id');

        $photos = $message['photo'] ?? [];
        $photo = end($photos);
        $fileId = $photo['file_id'] ?? null;

        if (!$fileId) {
            $this->sendMessage($chatId, 'Не смог получить фото.');
            return;
        }

        $session = $this->session($fromId);
        if ($session && in_array($session->state, ['waiting_booking_confirmation', 'waiting_animal_match'], true)) {
            $payload = $session->payload ?: [];
            $payload['pending_photo_file_ids'] = array_values(array_unique(array_merge($payload['pending_photo_file_ids'] ?? [], [$fileId])));
            $session->payload = $payload;
            $session->save();
            $this->sendMessage($chatId, 'Фото добавлено к будущей записи. Оно привяжется к питомцу после подтверждения.');
            return;
        }

        $caption = trim((string)($message['caption'] ?? ''));
        if ($caption === '') {
            $this->sendMessage($chatId, 'Фото получил. Чтобы привязать его к питомцу, отправьте фото с подписью, например: “фото Рауля”.');
            return;
        }

        $intent = $this->aitunnel->extractIntent($caption);
        $name = data_get($intent, 'animal.name');
        if (!$name) {
            $this->sendMessage($chatId, 'Не понял, к какому питомцу привязать фото. Укажите кличку в подписи.');
            return;
        }

        $animal = Animal::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
        if (!$animal) {
            $this->sendMessage($chatId, 'Питомец '.$name.' не найден. Сначала создайте запись или карточку питомца.');
            return;
        }

        $this->storeTelegramPhoto($animal, $fileId);
        $this->sendMessage($chatId, 'Фото привязано к питомцу '.$animal->name.'.');
    }

    private function transcribeVoice(array $voice): string
    {
        $fileId = $voice['file_id'] ?? null;
        if (!$fileId) {
            return '';
        }

        $bytes = $this->downloadTelegramFile($fileId);
        if ($bytes === null) {
            return '';
        }

        return $this->aitunnel->transcribeAudio($bytes, 'telegram-voice.ogg');
    }

    private function storeTelegramPhoto(Animal $animal, string $fileId): void
    {
        $file = $this->telegramApi('getFile', ['file_id' => $fileId]);
        $path = $file['result']['file_path'] ?? null;
        $bytes = $this->downloadTelegramFile($fileId, $path);

        if ($bytes === null) {
            return;
        }

        $ext = pathinfo((string)$path, PATHINFO_EXTENSION) ?: 'jpg';
        $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '', $fileId) ?: Str::random(12);
        $storagePath = 'animals/'.$animal->id.'/telegram-'.$safeId.'.'.$ext;
        Storage::disk('public')->put($storagePath, $bytes);

        $animal->photos()->firstOrCreate(
            ['telegram_file_id' => $fileId],
            ['path' => $storagePath]
        );
    }

    private function downloadTelegramFile(string $fileId, ?string $knownPath = null): ?string
    {
        $token = config('services.telegram.bot_token');
        if (!$token) {
            return null;
        }

        $path = $knownPath;
        if (!$path) {
            $file = $this->telegramApi('getFile', ['file_id' => $fileId]);
            $path = $file['result']['file_path'] ?? null;
        }

        if (!$path) {
            return null;
        }

        $response = Http::timeout(30)->get("https://api.telegram.org/file/bot{$token}/{$path}");

        return $response->ok() ? $response->body() : null;
    }

    private function matchingAnimals(array $payload)
    {
        return Animal::with(['client', 'category', 'boardings' => fn ($query) => $query->latest('start_date')])
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($payload['animal_name'])])
            ->when($payload['category_id'] ?? null, function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            }, function ($query) use ($payload) {
                if (empty($payload['species'])) return;
                $species = $payload['species'];
                $query->where(function ($sub) use ($species) {
                    $sub->whereNull('species')->orWhereRaw('LOWER(species) = ?', [mb_strtolower($species)]);
                });
            })
            ->limit(5)
            ->get();
    }

    private function overlaps(array $payload)
    {
        $start = Carbon::parse($payload['start_date'])->toDateString();
        $end = Carbon::parse($payload['end_date'])->toDateString();

        return Boarding::with(['animal.client', 'client'])
            ->whereNull('archived_at')
            ->when($payload['animal_id'] ?? null, fn ($query, $animalId) => $query->where('animal_id', '!=', $animalId))
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                    ->orWhere(fn ($sub) => $sub->where('start_date', '<=', $start)->where('end_date', '>=', $end));
            })
            ->orderBy('start_date')
            ->limit(8)
            ->get();
    }

    private function bookingLine(Boarding $boarding): string
    {
        $animal = $boarding->animal;
        $client = $boarding->client ?: $animal?->client;
        $name = $animal?->name ?: $boarding->name;
        $species = $animal?->species ? ' ('.$animal->species.')' : '';
        $clientText = $client ? ', хозяин '.$client->name : '';

        return "{$name}{$species}{$clientText} · {$boarding->service_type} · {$boarding->start_date->toDateString()} — {$boarding->end_date->toDateString()}";
    }

    private function normalizeServiceType(?string $value): string
    {
        return in_array($value, ['передержка', 'выгул', 'уход'], true) ? $value : 'передержка';
    }

    private function normalizeSpecies(?string $value): ?string
    {
        $value = $value ? mb_strtolower(trim($value)) : null;
        return match ($value) {
            'кошка' => 'кот',
            'пес', 'пёс', 'щенок' => 'собака',
            default => $value,
        };
    }

    private function categoryFromText(string $value): ?Category
    {
        $aliases = [
            'кот' => 'кошки',
            'кошка' => 'кошки',
            'собака' => 'собаки',
            'пёс' => 'собаки',
            'пес' => 'собаки',
            'грызун' => 'грызуны',
            'птица' => 'птицы',
            'рыбка' => 'рыбки',
        ];

        return Category::whereRaw('LOWER(name) = ?', [$aliases[$value] ?? $value])->first();
    }

    private function normalizeDogSize(?string $value): ?string
    {
        $value = mb_strtolower(trim((string) $value));

        return match ($value) {
            'мелкая', 'маленькая', 'small' => 'small',
            'средняя', 'крупная', 'средняя или крупная', 'large', 'medium', 'medium_large' => 'large',
            default => null,
        };
    }

    private function isDog(?string $species): bool
    {
        return in_array(mb_strtolower(trim((string) $species)), ['собака', 'собаки', 'пёс', 'пес', 'щенок'], true);
    }

    private function daysLabel(int $days): string
    {
        $last = $days % 10;
        $lastTwo = $days % 100;

        if ($last === 1 && $lastTwo !== 11) return 'день';
        if (in_array($last, [2, 3, 4], true) && !in_array($lastTwo, [12, 13, 14], true)) return 'дня';

        return 'дней';
    }

    private function isAllowed(string $telegramUserId): bool
    {
        $allowed = config('services.telegram.allowed_user_ids', []);
        return $telegramUserId !== '' && in_array($telegramUserId, array_map('strval', $allowed), true);
    }

    private function validSecret(Request $request): bool
    {
        $secret = config('services.telegram.webhook_secret');
        return !$secret || hash_equals($secret, (string)$request->header('X-Telegram-Bot-Api-Secret-Token'));
    }

    private function session(string $fromId): ?TelegramBotSession
    {
        return TelegramBotSession::where('telegram_user_id', $fromId)
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->first();
    }

    private function saveSession(string $fromId, int|string $chatId, string $state, array $payload): TelegramBotSession
    {
        TelegramBotSession::where('telegram_user_id', $fromId)->delete();

        return TelegramBotSession::create([
            'telegram_user_id' => $fromId,
            'chat_id' => $chatId,
            'state' => $state,
            'payload' => $payload,
            'expires_at' => now()->addHours(6),
        ]);
    }

    private function clearSession(string $fromId): void
    {
        TelegramBotSession::where('telegram_user_id', $fromId)->delete();
    }

    private function handleBoardingTaskCallback(int $runId, string $action, string $fromId, int|string $chatId): void
    {
        $run = BoardingTaskRun::with(['task.boarding.animal', 'messages'])->find($runId);
        if (!$run) {
            $this->sendMessage($chatId, 'Это действие уже недоступно.');
            return;
        }

        $animal = $run->task->boarding->animal?->name ?: $run->task->boarding->name;
        if ($run->status !== 'pending') {
            $label = $run->status === 'done' ? 'уже отмечено как выполненное' : 'уже отменено';
            $this->sendMessage($chatId, "«{$run->task->title}» для {$animal} {$label}.");
            return;
        }

        $status = $action === 'done' ? 'done' : 'cancelled';
        $run->update([
            'status' => $status,
            'responded_at' => now(),
            'responded_by' => $fromId,
        ]);

        foreach ($run->messages as $message) {
            $this->telegramApi('editMessageReplyMarkup', [
                'chat_id' => $message->chat_id,
                'message_id' => $message->message_id,
                'reply_markup' => ['inline_keyboard' => []],
            ]);
        }

        $result = $status === 'done' ? '✅ Готово' : '↩️ Отменено';
        $this->sendMessage($chatId, "{$result}: {$run->task->title} — {$animal}.");
    }

    /** @param array<int, array{title: string, scheduled_time: string, instructions: string}> $tasks */
    private function startBoardingTaskCreation(int|string $chatId, string $fromId, array $tasks): void
    {
        $boardings = Boarding::with('animal')
            ->whereNull('archived_at')
            ->whereDate('start_date', '<=', now()->toDateString())
            ->whereDate('end_date', '>=', now()->toDateString())
            ->orderBy('end_date')
            ->get();

        if ($boardings->isEmpty()) {
            $this->sendMessage($chatId, 'Сейчас нет активной передержки, к которой можно привязать действия.');
            return;
        }

        if ($boardings->count() === 1) {
            $boarding = $boardings->first();
            $this->createBoardingTasks($boarding, $tasks);
            $this->sendTaskScheduleSummary($chatId, $boarding, $tasks);
            return;
        }

        $this->saveSession($fromId, $chatId, 'waiting_task_boarding', [
            'tasks' => $tasks,
            'task_boarding_ids' => $boardings->pluck('id')->all(),
        ]);

        $buttons = $boardings->map(function (Boarding $boarding): array {
            $animal = $boarding->animal?->name ?: $boarding->name;

            return ['text' => $animal.' · '.$boarding->end_date->format('d.m'), 'callback_data' => 'task_boarding:'.$boarding->id];
        })->chunk(1)->map(fn ($row) => $row->all())->all();
        $buttons[] = [['text' => 'Отмена', 'callback_data' => 'cancel']];

        $this->sendMessage($chatId, 'К какой активной передержке добавить это расписание?', ['inline_keyboard' => $buttons]);
    }

    private function selectBoardingForTasks(int|string $chatId, string $fromId, string $boardingId): void
    {
        $session = $this->session($fromId);
        $allowedIds = array_map('strval', $session?->payload['task_boarding_ids'] ?? []);
        if (!$session || $session->state !== 'waiting_task_boarding' || !in_array($boardingId, $allowedIds, true)) {
            $this->sendMessage($chatId, 'Выбор передержки устарел. Отправьте инструкции ещё раз.');
            return;
        }

        $boarding = Boarding::with('animal')->whereNull('archived_at')->find((int) $boardingId);
        if (!$boarding) {
            $this->clearSession($fromId);
            $this->sendMessage($chatId, 'Передержка больше недоступна.');
            return;
        }

        $tasks = $session->payload['tasks'] ?? [];
        $this->createBoardingTasks($boarding, $tasks);
        $this->clearSession($fromId);
        $this->sendTaskScheduleSummary($chatId, $boarding, $tasks);
    }

    /** @param array<int, array{title: string, scheduled_time: string, instructions: string}> $tasks */
    private function createBoardingTasks(Boarding $boarding, array $tasks): void
    {
        foreach ($tasks as $task) {
            BoardingTask::create([
                'boarding_id' => $boarding->id,
                'title' => $task['title'],
                'instructions' => $task['instructions'],
                'scheduled_time' => $task['scheduled_time'],
            ]);
        }
    }

    /** @param array<int, array{title: string, scheduled_time: string, instructions: string}> $tasks */
    private function sendTaskScheduleSummary(int|string $chatId, Boarding $boarding, array $tasks): void
    {
        $animal = $boarding->animal?->name ?: $boarding->name;
        $lines = array_map(fn (array $task) => '• '.$task['scheduled_time'].' — '.$task['title'], $tasks);
        $this->sendMessage($chatId, "Расписание добавлено для {$animal}:\n".implode("\n", $lines));
    }

    private function sendMessage(int|string $chatId, string $text, ?array $replyMarkup = null): void
    {
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        if ($replyMarkup) {
            $payload['reply_markup'] = $replyMarkup;
        }

        $this->telegramApi('sendMessage', $payload);
    }

    private function answerCallback(?string $callbackId): void
    {
        if ($callbackId) {
            $this->telegramApi('answerCallbackQuery', ['callback_query_id' => $callbackId]);
        }
    }

    private function telegramApi(string $method, array $payload): array
    {
        $token = config('services.telegram.bot_token');
        if (!$token) {
            Log::error('Telegram API request skipped: bot token is missing.', ['method' => $method]);

            return ['ok' => false];
        }

        try {
            $response = Http::timeout(30)->post("https://api.telegram.org/bot{$token}/{$method}", $payload);
        } catch (Throwable $e) {
            Log::warning('Telegram API request failed.', [
                'method' => $method,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false];
        }

        $result = $response->json();
        if (!$response->successful() || !is_array($result) || !($result['ok'] ?? false)) {
            Log::warning('Telegram API returned an unsuccessful response.', [
                'method' => $method,
                'status' => $response->status(),
                'error_code' => $result['error_code'] ?? null,
                'description' => $result['description'] ?? null,
            ]);
        }

        return is_array($result) ? $result : ['ok' => false];
    }
}
