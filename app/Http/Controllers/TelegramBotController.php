<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Boarding;
use App\Models\Client;
use App\Models\TelegramBotSession;
use App\Services\AitunnelService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class TelegramBotController extends Controller
{
    public function __construct(private readonly AitunnelService $aitunnel)
    {
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
            $this->saveSession($fromId, $chatId, 'waiting_owner', $payload);
            $this->askOwner($chatId);
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
            $payload['species'] = $animal->species ?: ($payload['species'] ?? null);
            $payload['client_id'] = $animal->client_id ?: ($payload['client_id'] ?? null);
            $payload['client_name'] = $animal->client?->name ?: ($payload['client_name'] ?? null);
            $this->askBookingConfirmation($chatId, $fromId, $payload);
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
            $payload['species'] = in_array($normalized, ['без вида', 'не знаю', 'нет'], true) ? null : $text;
            $this->saveSession($fromId, $chatId, 'waiting_owner', $payload);
            $this->askOwner($chatId);
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
            'species' => $this->normalizeSpecies($animal['species'] ?? null),
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

        if (!$payload['species']) {
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

        if (empty($payload['client_id']) && empty($payload['client_name']) && empty($payload['owner_asked'])) {
            $this->saveSession($fromId, $chatId, 'waiting_owner', $payload);
            $this->askOwner($chatId);
            return;
        }

        $this->askBookingConfirmation($chatId, $fromId, $payload);
    }

    private function askSpecies(int|string $chatId, string $animalName): void
    {
        $this->sendMessage($chatId, "Кто {$animalName}: кот, собака или можно оставить без вида?", [
            'inline_keyboard' => [
                [
                    ['text' => 'Кот', 'callback_data' => 'species:кот'],
                    ['text' => 'Собака', 'callback_data' => 'species:собака'],
                ],
                [
                    ['text' => 'Без вида', 'callback_data' => 'species:none'],
                    ['text' => 'Отмена', 'callback_data' => 'cancel'],
                ],
            ],
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
        $this->saveSession($fromId, $chatId, 'waiting_booking_confirmation', $payload);

        $text = "Будет создана запись:\n";
        $text .= 'Услуга: '.$payload['service_type']."\n";
        $text .= 'Даты: '.$payload['start_date'].' — '.$payload['end_date']."\n";
        $text .= 'Питомец: '.trim(($payload['species'] ? $payload['species'].' ' : '').$payload['animal_name'])."\n";
        $text .= 'Хозяин: '.($payload['client_name'] ?: 'без хозяина')."\n";

        if ($overlaps->count()) {
            $text .= "\nНа эти даты уже есть другие записи:\n";
            foreach ($overlaps as $row) {
                $text .= '• '.$this->bookingLine($row)."\n";
            }
        }

        $text .= "\nПодтвердить?";

        $this->sendMessage($chatId, trim($text), [
            'inline_keyboard' => [
                [
                    ['text' => 'Подтвердить', 'callback_data' => 'booking_confirm'],
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
                'name' => $payload['animal_name'],
                'species' => $payload['species'] ?? null,
                'description' => $payload['description'] ?? null,
                'order' => (int)Animal::max('order') + 1,
            ]);
        } elseif ($client && !$animal->client_id) {
            $animal->client_id = $client->id;
            $animal->save();
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
        return Animal::with(['client', 'boardings' => fn ($query) => $query->latest('start_date')])
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($payload['animal_name'])])
            ->when($payload['species'] ?? null, function ($query, $species) {
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
            return ['ok' => false];
        }

        $response = Http::timeout(30)->post("https://api.telegram.org/bot{$token}/{$method}", $payload);

        return $response->json() ?: ['ok' => false];
    }
}
