<?php

namespace App\Http\Controllers\Telegram;

use App\Exceptions\TelegramApiException;
use App\Http\Controllers\Controller;

use App\Models\Animal;
use App\Models\Boarding;
use App\Models\BoardingTask;
use App\Models\BoardingTaskRun;
use App\Models\Client;
use App\Models\Category;
use App\Models\ServiceOrder;
use App\Models\TelegramBotSession;
use App\Models\TelegramWebhookUpdate;
use App\Jobs\ProcessTelegramUpdate;
use App\Services\AitunnelService;
use App\Services\BoardingPricingService;
use App\Services\BoardingTaskInstructionParser;
use App\Services\TelegramCalendarImageService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
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
        private readonly TelegramCalendarImageService $calendarImage,
    ) {
    }

    public function __invoke(Request $request)
    {
        if (!$this->validSecret($request)) {
            return response()->json(['ok' => false], 403);
        }

        $update = $request->all();
        $updateId = data_get($update, 'update_id');
        if ($updateId === null) {
            return response()->json(['ok' => false], 422);
        }

        try {
            $received = TelegramWebhookUpdate::firstOrCreate(
                ['update_id' => (int) $updateId],
                ['payload' => $update]
            );
        } catch (QueryException) {
            // Telegram may retry the same update in parallel; the unique index
            // makes the already accepted update the single source of truth.
            return response()->json(['ok' => true]);
        }

        if ($received->wasRecentlyCreated) {
            ProcessTelegramUpdate::dispatch($received->id)->onQueue('telegram');
        }

        return response()->json(['ok' => true]);
    }

    public function processUpdate(array $update): void
    {
        try {
            if (isset($update['callback_query'])) {
                $this->handleCallback($update['callback_query']);
            } elseif (isset($update['message'])) {
                $this->handleMessage($update['message']);
            }
        } catch (Throwable $e) {
            $chatId = data_get($update, 'message.chat.id') ?: data_get($update, 'callback_query.message.chat.id');
            if ($e instanceof TelegramApiException) {
                Log::warning('Telegram transport failed while processing update.', ['error' => $e->getMessage()]);
                throw $e;
            }

            if ($chatId) {
                Log::warning('Telegram bot could not process update.', ['error' => $e->getMessage()]);
                $this->sendMessage($chatId, 'Не понял сообщение. Напишите, например: «Запиши кошку Пухлю с 22 по 25 августа, уход» — или уточните, что нужно сделать.');
            }
        }
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

        if (Str::startsWith($text, '/help')) {
            $this->sendHelp($chatId);
            return;
        }

        $calendarCommand = mb_strtolower($text);
        if (in_array($calendarCommand, ['календарь', '/calendar'], true)) {
            $this->sendMonthlyCalendar($chatId, now()->startOfMonth());
            return;
        }

        if (in_array($calendarCommand, ['следующий месяц', 'след. месяц'], true)) {
            $this->sendMonthlyCalendar($chatId, now()->addMonthNoOverflow()->startOfMonth());
            return;
        }

        if (in_array($calendarCommand, ['заказы', '/orders', 'мои заказы', 'заказы и работа'], true)) {
            $this->sendServiceOrdersMenu($chatId);
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
        if ($ownerUpdateIntent = $this->ownerUpdateIntentFromText($text)) {
            $intent = $ownerUpdateIntent;
        }
        if ($anonymousOrderIntent = $this->anonymousOrderIntentFromText($text)) {
            $intent = $anonymousOrderIntent;
        }
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

        if (preg_match('/^calendar:(\d{4}-\d{2})$/', $data, $matches)) {
            try {
                $this->sendMonthlyCalendar($chatId, Carbon::createFromFormat('!Y-m', $matches[1])->startOfMonth());
            } catch (Throwable) {
                $this->sendMessage($chatId, 'Не удалось открыть календарь. Отправьте «календарь» ещё раз.');
            }
            return;
        }

        if (preg_match('/^orders:open:(\d+)$/', $data, $matches)) {
            $this->showServiceOrderMenu($chatId, (int) $matches[1]);
            return;
        }

        if ($data === 'orders:list') {
            $this->sendServiceOrdersMenu($chatId);
            return;
        }

        if (preg_match('/^order:(archive|delete):(\d+)$/', $data, $matches)) {
            $this->askServiceOrderDestructiveConfirmation($chatId, $fromId, (int) $matches[2], $matches[1]);
            return;
        }

        if (preg_match('/^order:(archive|delete):(\d+):confirm$/', $data, $matches)) {
            $this->confirmServiceOrderDestructiveAction($chatId, $fromId, (int) $matches[2], $matches[1]);
            return;
        }

        if (preg_match('/^order:edit:(\d+)$/', $data, $matches)) {
            $this->showServiceOrderEditMenu($chatId, (int) $matches[1]);
            return;
        }

        if (preg_match('/^order:field:(\d+):(dates|address|note|client)$/', $data, $matches)) {
            $this->startServiceOrderFieldEdit($chatId, $fromId, (int) $matches[1], $matches[2]);
            return;
        }

        if (preg_match('/^order:pets:(\d+)$/', $data, $matches)) {
            $this->showServiceOrderPetsMenu($chatId, (int) $matches[1]);
            return;
        }

        if (preg_match('/^order:addpet:(\d+)$/', $data, $matches)) {
            $this->startServiceOrderPetAdd($chatId, $fromId, (int) $matches[1]);
            return;
        }

        if (preg_match('/^order:pet:(\d+):(\d+)$/', $data, $matches)) {
            $this->showServiceOrderPetMenu($chatId, (int) $matches[1], (int) $matches[2]);
            return;
        }

        if (preg_match('/^order:petqty:(\d+):(\d+):(plus|minus)$/', $data, $matches)) {
            $this->changeServiceOrderPetQuantity($chatId, (int) $matches[1], (int) $matches[2], $matches[3]);
            return;
        }

        if (preg_match('/^order:petdelete:(\d+):(\d+)$/', $data, $matches)) {
            $this->askServiceOrderPetDeletion($chatId, $fromId, (int) $matches[1], (int) $matches[2]);
            return;
        }

        if (preg_match('/^order:petdelete:(\d+):(\d+):confirm$/', $data, $matches)) {
            $this->confirmServiceOrderPetDeletion($chatId, $fromId, (int) $matches[1], (int) $matches[2]);
            return;
        }

        if (preg_match('/^order:serviceadd:(\d+):(\d+):(\p{L}+)$/u', $data, $matches)) {
            $this->addServiceToOrderPet($chatId, (int) $matches[1], (int) $matches[2], $matches[3]);
            return;
        }

        if (preg_match('/^order:service:(\d+):(\d+):(\d+)$/', $data, $matches)) {
            $this->showServiceOrderPetServiceMenu($chatId, (int) $matches[1], (int) $matches[2], (int) $matches[3]);
            return;
        }

        if (preg_match('/^order:serviceunits:(\d+):(\d+):(\d+):(\d+)$/', $data, $matches)) {
            $this->changeServiceOrderPetServiceUnits($chatId, (int) $matches[1], (int) $matches[2], (int) $matches[3], (int) $matches[4]);
            return;
        }

        if (preg_match('/^order:servicedelete:(\d+):(\d+):(\d+)$/', $data, $matches)) {
            $this->askServiceOrderPetServiceDeletion($chatId, $fromId, (int) $matches[1], (int) $matches[2], (int) $matches[3]);
            return;
        }

        if (preg_match('/^order:servicedelete:(\d+):(\d+):(\d+):confirm$/', $data, $matches)) {
            $this->confirmServiceOrderPetServiceDeletion($chatId, $fromId, (int) $matches[1], (int) $matches[2], (int) $matches[3]);
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

        if (preg_match('/^photo_target:(animal|client):(\d+)$/', $data, $matches)) {
            if ($session->state !== 'waiting_photo_target_selection') {
                $this->clearSession($fromId);
                $this->sendMessage($chatId, 'Выбор устарел. Отправьте фото ещё раз.');
                return;
            }

            $type = $matches[1];
            $id = (int) $matches[2];
            $allowed = $type === 'animal' ? ($payload['animal_ids'] ?? []) : ($payload['client_ids'] ?? []);
            if (!in_array($id, $allowed, true)) {
                $this->clearSession($fromId);
                $this->sendMessage($chatId, 'Карточка не найдена. Отправьте фото ещё раз.');
                return;
            }

            if ($type === 'animal' && ($animal = Animal::with('client')->find($id))) {
                $this->askPetPhotoConfirmation($chatId, $fromId, $animal, (string) ($payload['file_id'] ?? ''));
                return;
            }
            if ($type === 'client' && ($client = Client::find($id))) {
                $this->askClientPhotoConfirmation($chatId, $fromId, $client, (string) ($payload['file_id'] ?? ''));
                return;
            }

            $this->clearSession($fromId);
            $this->sendMessage($chatId, 'Карточка не найдена. Отправьте фото ещё раз.');
            return;
        }

        if (preg_match('/^pet_photo:choose:(\d+)$/', $data, $matches)) {
            if ($session->state !== 'waiting_pet_photo_selection' || !in_array((int) $matches[1], $payload['animal_ids'] ?? [], true)) {
                $this->clearSession($fromId);
                $this->sendMessage($chatId, 'Список питомцев устарел. Отправьте фото ещё раз.');
                return;
            }

            $animal = Animal::with('client')->find((int) $matches[1]);
            if (!$animal) {
                $this->clearSession($fromId);
                $this->sendMessage($chatId, 'Питомец не найден. Отправьте фото ещё раз.');
                return;
            }

            $this->askPetPhotoConfirmation($chatId, $fromId, $animal, (string) ($payload['file_id'] ?? ''));
            return;
        }

        if ($data === 'pet_photo:confirm') {
            if ($session->state !== 'waiting_pet_photo_confirmation') {
                $this->clearSession($fromId);
                $this->sendMessage($chatId, 'Подтверждение устарело. Отправьте фото ещё раз.');
                return;
            }

            $animal = Animal::find((int) ($payload['animal_id'] ?? 0));
            $fileId = (string) ($payload['file_id'] ?? '');
            if (!$animal || $fileId === '') {
                $this->clearSession($fromId);
                $this->sendMessage($chatId, 'Не удалось сохранить фото. Отправьте его ещё раз.');
                return;
            }

            $this->storeTelegramPhoto($animal, $fileId);
            $this->clearSession($fromId);
            $this->sendMessage($chatId, 'Фото добавлено в профиль питомца '.$animal->name.'.');
            return;
        }

        if ($data === 'client_photo:confirm') {
            if ($session->state !== 'waiting_client_photo_confirmation') {
                $this->clearSession($fromId);
                $this->sendMessage($chatId, 'Подтверждение устарело. Отправьте фото ещё раз.');
                return;
            }

            $client = Client::find((int) ($payload['client_id'] ?? 0));
            $fileId = (string) ($payload['file_id'] ?? '');
            if (!$client || $fileId === '') {
                $this->clearSession($fromId);
                $this->sendMessage($chatId, 'Не удалось сохранить фото. Отправьте его ещё раз.');
                return;
            }

            $this->storeTelegramClientPhoto($client, $fileId);
            $this->clearSession($fromId);
            $this->sendMessage($chatId, 'Фото добавлено в профиль клиента '.$client->name.'.');
            return;
        }

        if (preg_match('/^pet_owner:choose:(\d+)$/', $data, $matches)) {
            if ($session->state !== 'waiting_pet_owner_selection' || !in_array((int) $matches[1], $payload['animal_ids'] ?? [], true)) {
                $this->clearSession($fromId);
                $this->sendMessage($chatId, 'Список питомцев устарел. Повторите сообщение.');
                return;
            }

            $animal = Animal::with('client')->find((int) $matches[1]);
            if (!$animal) {
                $this->clearSession($fromId);
                $this->sendMessage($chatId, 'Питомец не найден. Повторите сообщение.');
                return;
            }

            $this->askPetOwnerUpdateConfirmation($chatId, $fromId, $animal, $payload);
            return;
        }

        if ($data === 'pet_owner:confirm') {
            if ($session->state !== 'waiting_pet_owner_confirmation') {
                $this->clearSession($fromId);
                $this->sendMessage($chatId, 'Подтверждение устарело. Повторите сообщение.');
                return;
            }

            $animal = Animal::with('client')->find((int) ($payload['animal_id'] ?? 0));
            $clientName = trim((string) ($payload['client_name'] ?? ''));
            if (!$animal || $clientName === '') {
                $this->clearSession($fromId);
                $this->sendMessage($chatId, 'Не удалось сохранить владельца. Повторите сообщение.');
                return;
            }

            $client = Client::firstOrCreate(['name' => $clientName], array_filter([
                'phone' => $payload['client_phone'] ?? null,
                'note' => $payload['client_note'] ?? null,
            ], fn ($value) => $value !== null && $value !== ''));
            $animal->update(['client_id' => $client->id]);
            $this->clearSession($fromId);
            $this->sendMessage($chatId, 'Готово: у '.$animal->name.' теперь хозяин '.$client->name.'.');
            return;
        }

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

        if ($data === 'service_order_confirm') {
            $serviceType = (string) ($payload['service_type'] ?? '');
            $groups = is_array($payload['animal_groups'] ?? null) ? $payload['animal_groups'] : [];
            if (!in_array($serviceType, BoardingPricingService::SERVICE_TYPES, true) || empty($groups)) {
                $this->clearSession($fromId);
                $this->sendMessage($chatId, 'Данные заказа устарели. Отправьте заявку ещё раз.');
                return;
            }

            $order = ServiceOrder::create([
                'service_type' => $serviceType,
                'units_per_day' => max(1, (int) ($payload['units_per_day'] ?? 1)),
                'daily_price' => (int) ($payload['daily_price'] ?? 0),
                'start_date' => $payload['start_date'],
                'end_date' => $payload['end_date'],
                'source' => 'telegram_bot',
                'status' => 'active',
                'confirmed_at' => now(),
            ]);
            foreach ($groups as $group) {
                $orderAnimal = $order->animals()->create([
                    'category_id' => $group['category_id'],
                    'label' => $group['label'],
                    'quantity' => $group['quantity'],
                ]);
                $orderAnimal->services()->create([
                    'service_order_id' => $order->id,
                    'service_type' => $serviceType,
                    'units_per_day' => $order->units_per_day,
                    'unit_price' => (int) round($group['daily_price'] / max(1, $group['quantity'])),
                ]);
            }
            $this->clearSession($fromId);
            $this->sendMessage($chatId, "Заказ создан #{$order->id}: ".$this->russianDatePeriod($order->start_date, $order->end_date).'. '.implode(', ', array_column($groups, 'label')).'.');
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

        if (Str::startsWith($data, 'booking_service_select:')) {
            $boardingId = (int) Str::after($data, 'booking_service_select:');
            if (!in_array($boardingId, array_map('intval', $payload['booking_service_candidate_ids'] ?? []), true)) {
                $this->sendMessage($chatId, 'Выбор записи устарел. Повторите команду.');
                return;
            }

            $boarding = Boarding::with('animal.category')->whereNull('archived_at')->find($boardingId);
            if (!$boarding) {
                $this->clearSession($fromId);
                $this->sendMessage($chatId, 'Запись не найдена или уже в архиве.');
                return;
            }

            $this->askBookingServiceUpdateConfirmation($chatId, $fromId, $boarding, (string) ($payload['new_service_type'] ?? ''));
            return;
        }

        if ($data === 'booking_service_update_confirm') {
            $boarding = Boarding::with('animal.category')->whereNull('archived_at')->find((int) ($payload['booking_id'] ?? 0));
            $serviceType = (string) ($payload['new_service_type'] ?? '');
            if (!$boarding || !in_array($serviceType, BoardingPricingService::SERVICE_TYPES, true)) {
                $this->clearSession($fromId);
                $this->sendMessage($chatId, 'Не удалось найти запись для изменения. Повторите команду.');
                return;
            }

            $species = $boarding->animal?->category?->name ?: $boarding->animal?->species;
            $boarding->update([
                'service_type' => $serviceType,
                'units_per_day' => 1,
                'unit_price' => $this->pricing->defaultRate($serviceType, $species, $boarding->animal?->dog_size),
            ]);
            $this->clearSession($fromId);
            $this->sendMessage($chatId, 'Готово: у '.$this->bookingAnimalName($boarding).' услуга изменена на «'.$serviceType.'».');
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

        if (in_array($session->state, ['waiting_order_dates', 'waiting_order_address', 'waiting_order_note', 'waiting_order_client'], true)) {
            $order = ServiceOrder::whereNull('archived_at')->find((int) ($payload['order_id'] ?? 0));
            if (!$order) {
                $this->clearSession($fromId);
                $this->sendMessage($chatId, 'Заказ не найден или уже в архиве.');
                return true;
            }

            if ($session->state === 'waiting_order_dates') {
                if (!preg_match('/(\d{1,2}[.\/-]\d{1,2}[.\/-]\d{2,4})\s*(?:—|-|до|по)\s*(\d{1,2}[.\/-]\d{1,2}[.\/-]\d{2,4})/u', $text, $dates)) {
                    $this->sendMessage($chatId, 'Напишите период так: 22.08.2026 — 25.08.2026');
                    return true;
                }
                try {
                    $start = Carbon::createFromFormat('d.m.Y', str_replace(['/', '-'], '.', $dates[1]))->startOfDay();
                    $end = Carbon::createFromFormat('d.m.Y', str_replace(['/', '-'], '.', $dates[2]))->startOfDay();
                    if ($end->lt($start)) { throw new \InvalidArgumentException(); }
                    $order->update(['start_date' => $start, 'end_date' => $end]);
                } catch (Throwable) {
                    $this->sendMessage($chatId, 'Не удалось распознать даты. Пример: 22.08.2026 — 25.08.2026');
                    return true;
                }
            } elseif ($session->state === 'waiting_order_address') {
                $order->update(['address' => trim($text)]);
            } elseif ($session->state === 'waiting_order_note') {
                $order->update(['note' => trim($text)]);
            } else {
                $client = Client::whereRaw('LOWER(name) = ?', [mb_strtolower(trim($text))])->first();
                if (!$client) {
                    $client = Client::create(['name' => trim($text)]);
                }
                $order->update(['client_id' => $client->id]);
            }

            $this->syncLegacyBoardingForServiceOrder($order->fresh(['animals.services', 'animals.animal']));
            $this->clearSession($fromId);
            $this->sendMessage($chatId, 'Готово: заказ обновлён.');
            $this->showServiceOrderMenu($chatId, $order->id);
            return true;
        }

        if ($session->state === 'waiting_order_add_pet') {
            $order = ServiceOrder::whereNull('archived_at')->find((int) ($payload['order_id'] ?? 0));
            $parts = array_map('trim', explode(',', $text));
            if (!$order || count($parts) < 3) {
                $this->sendMessage($chatId, 'Напишите: Кличка, вид, количество, услуга. Например: Мурка, кошки, 1, уход');
                return true;
            }
            [$name, $species, $quantity, $serviceType] = array_pad($parts, 4, 'уход');
            $category = $this->categoryFromText($this->normalizeSpecies($species));
            $serviceType = $this->normalizeServiceType($serviceType) ?: 'уход';
            if (!$category || !in_array($serviceType, BoardingPricingService::SERVICE_TYPES, true)) {
                $this->sendMessage($chatId, 'Не распознал вид или услугу. Пример: Мурка, кошки, 1, уход');
                return true;
            }
            $quantity = max(1, min(99, (int) $quantity));
            $animal = Animal::firstOrCreate(['name' => $name, 'client_id' => $order->client_id], ['category_id' => $category->id, 'species' => $category->name, 'order' => (int) Animal::max('order') + 1]);
            $position = $order->animals()->create(['animal_id' => $animal->id, 'category_id' => $category->id, 'label' => $animal->name, 'quantity' => $quantity]);
            $position->services()->create(['service_order_id' => $order->id, 'service_type' => $serviceType, 'units_per_day' => 1, 'unit_price' => $this->pricing->defaultRate($serviceType, $category->name, $animal->dog_size)]);
            $this->refreshServiceOrderPrice($order); $this->syncLegacyBoardingForServiceOrder($order->fresh(['animals.services', 'animals.animal']));
            $this->clearSession($fromId); $this->sendMessage($chatId, 'Питомец добавлен в заказ.'); $this->showServiceOrderPetsMenu($chatId, $order->id);
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

        if ($type === 'update_booking') {
            $this->startBookingServiceUpdate($chatId, $fromId, $intent);
            return;
        }

        if ($type === 'update_pet_owner') {
            $this->startPetOwnerUpdate($chatId, $fromId, $intent);
            return;
        }

        if ($type === 'create_service_order') {
            $this->startAnonymousServiceOrder($chatId, $fromId, $intent);
            return;
        }

        if ($type !== 'create_booking') {
            $this->sendMessage($chatId, 'Не понял, что сделать с сообщением. Для новой записи напишите, например: «Запиши кошку Пухлю с 22 по 25 августа, уход». Для просмотра: «Покажи записи на этот месяц».');
            return;
        }

        $animal = $intent['animal'] ?? [];
        $client = $intent['client'] ?? [];
        $payload = [
            'service_type' => $this->normalizeServiceType($intent['service_type'] ?? null),
            'start_date' => $intent['start_date'] ?? null,
            'end_date' => $intent['end_date'] ?? ($intent['start_date'] ?? null),
            'animal_name' => $animal['name'] ?? null,
            'category_id' => $this->categoryFromText($this->normalizeSpecies($animal['species'] ?? null))?->id,
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
            ->map(fn ($row): array => $row->values()->all())
            ->all();

        $speciesButtons[] = [
            ['text' => 'Без категории', 'callback_data' => 'species:none'],
            ['text' => 'Отмена', 'callback_data' => 'cancel'],
        ];

        $this->sendMessage($chatId, "Какая категория у {$animalName}? Выберите её из списка или напишите сообщением.", [
            'inline_keyboard' => $speciesButtons,
        ]);
    }

    private function startBookingServiceUpdate(int|string $chatId, string $fromId, array $intent): void
    {
        $animalName = trim((string) data_get($intent, 'animal.name'));
        $serviceType = (string) ($intent['service_type'] ?? '');

        if ($animalName === '' || !in_array($serviceType, BoardingPricingService::SERVICE_TYPES, true)) {
            $this->sendMessage($chatId, 'Не понял, какую запись и на какую услугу изменить. Пример: «Бобик не передержка, а выгул».');
            return;
        }

        $bookings = Boarding::with('animal.category')
            ->whereNull('archived_at')
            ->whereDate('end_date', '>=', now()->toDateString())
            ->where(function ($query) use ($animalName) {
                $query->whereRaw('LOWER(name) = ?', [mb_strtolower($animalName)])
                    ->orWhereHas('animal', fn ($animals) => $animals->whereRaw('LOWER(name) = ?', [mb_strtolower($animalName)]));
            })
            ->orderBy('start_date')
            ->get();

        if ($bookings->isEmpty()) {
            $this->sendMessage($chatId, 'Не нашёл текущую или будущую запись питомца '.$animalName.'. Уточните кличку или даты.');
            return;
        }

        if ($bookings->count() === 1) {
            $this->askBookingServiceUpdateConfirmation($chatId, $fromId, $bookings->first(), $serviceType);
            return;
        }

        $this->saveSession($fromId, $chatId, 'waiting_booking_service_selection', [
            'new_service_type' => $serviceType,
            'booking_service_candidate_ids' => $bookings->pluck('id')->all(),
        ]);

        $buttons = $bookings->map(fn (Boarding $boarding): array => [[
            'text' => $this->bookingAnimalName($boarding).' · '.$boarding->start_date->format('d.m').'–'.$boarding->end_date->format('d.m').' · '.$boarding->service_type,
            'callback_data' => 'booking_service_select:'.$boarding->id,
        ]])->all();
        $buttons[] = [['text' => 'Отмена', 'callback_data' => 'cancel']];

        $this->sendMessage($chatId, 'Нашёл несколько записей. Какую изменить на «'.$serviceType.'»?', ['inline_keyboard' => $buttons]);
    }

    private function askBookingServiceUpdateConfirmation(int|string $chatId, string $fromId, Boarding $boarding, string $serviceType): void
    {
        if (!in_array($serviceType, BoardingPricingService::SERVICE_TYPES, true)) {
            $this->sendMessage($chatId, 'Неизвестный тип услуги. Повторите команду.');
            return;
        }

        $species = $boarding->animal?->category?->name ?: $boarding->animal?->species;
        $price = $this->pricing->defaultRate($serviceType, $species, $boarding->animal?->dog_size);
        $unitLabel = $serviceType === 'передержка' ? 'за сутки' : 'за один раз';
        $this->saveSession($fromId, $chatId, 'waiting_booking_service_update_confirmation', [
            'booking_id' => $boarding->id,
            'new_service_type' => $serviceType,
        ]);

        $this->sendMessage($chatId, 'Изменить услугу у '.$this->bookingAnimalName($boarding)."\n"
            .'с «'.$boarding->service_type.'» на «'.$serviceType."»?\n"
            .'Будет применён стандартный тариф: '.number_format($price, 0, '.', ' ')." ₽ {$unitLabel}.", [
                'inline_keyboard' => [
                    [
                        ['text' => 'Подтвердить', 'callback_data' => 'booking_service_update_confirm'],
                        ['text' => 'Отмена', 'callback_data' => 'cancel'],
                    ],
                ],
            ]);
    }

    private function bookingAnimalName(Boarding $boarding): string
    {
        return $boarding->animal?->name ?: $boarding->name;
    }

    private function startAnonymousServiceOrder(int|string $chatId, string $fromId, array $intent): void
    {
        $serviceType = (string) ($intent['service_type'] ?? '');
        $startDate = $intent['start_date'] ?? null;
        $endDate = $intent['end_date'] ?? $startDate;
        $groups = $this->anonymousAnimalGroups($intent['animals'] ?? [], $serviceType);

        if (!in_array($serviceType, BoardingPricingService::SERVICE_TYPES, true) || !$startDate || !$endDate || empty($groups)) {
            $this->sendMessage($chatId, 'Не хватает данных для заказа. Напишите, например: «С 22 по 25 августа уход: три кошки и собака, кличек пока не знаю».');
            return;
        }

        $units = max(1, min(24, (int) ($intent['units_per_day'] ?? 1)));
        $dailyPrice = array_sum(array_column($groups, 'daily_price')) * $units;
        $days = $this->pricing->daysBetween($startDate, $endDate);
        $total = $dailyPrice * $days;
        $labels = implode(', ', array_column($groups, 'label'));
        $unitLabel = $serviceType === 'передержка' ? 'за сутки' : 'за визит';

        $payload = [
            'service_type' => $serviceType,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'units_per_day' => $units,
            'daily_price' => $dailyPrice,
            'animal_groups' => $groups,
        ];
        $this->saveSession($fromId, $chatId, 'waiting_service_order_confirmation', $payload);

        $text = "Будет создан заказ без кличек:\n";
        $text .= 'Услуга: '.$serviceType."\n";
        $text .= 'Период: '.$this->russianDatePeriod(Carbon::parse($startDate), Carbon::parse($endDate))."\n";
        $text .= 'Питомцы: '.$labels."\n";
        $text .= 'Стоимость: '.number_format($dailyPrice, 0, '.', ' ')." ₽ {$unitLabel}".($units > 1 ? ' × '.$units.' раз в день' : '')."\n";
        $text .= 'Итого: '.number_format($total, 0, '.', ' ').' ₽ за '.$days.' '.$this->daysLabel($days)."\n\nПодтвердить?";

        $this->sendMessage($chatId, $text, [
            'inline_keyboard' => [[
                ['text' => 'Подтвердить', 'callback_data' => 'service_order_confirm'],
                ['text' => 'Отмена', 'callback_data' => 'cancel'],
            ]],
        ]);
    }

    private function anonymousAnimalGroups(mixed $animals, string $serviceType): array
    {
        if (!is_array($animals)) {
            return [];
        }

        $groups = [];
        foreach ($animals as $animal) {
            if (!is_array($animal) || !empty($animal['name'])) {
                continue;
            }
            $category = $this->categoryFromText($this->normalizeSpecies($animal['species'] ?? null));
            if (!$category) {
                continue;
            }
            $quantity = max(1, min(20, (int) ($animal['quantity'] ?? 1)));
            $groups[$category->id] = ($groups[$category->id] ?? 0) + $quantity;
        }

        return collect($groups)->map(function (int $quantity, int|string $categoryId) use ($serviceType): array {
            $category = Category::find($categoryId);
            $label = $this->anonymousAnimalLabel($category?->name, $quantity);
            $rate = $this->pricing->defaultRate($serviceType, $category?->name);

            return [
                'category_id' => (int) $categoryId,
                'quantity' => $quantity,
                'label' => $label,
                'daily_price' => $rate * $quantity,
            ];
        })->values()->all();
    }

    private function startPetOwnerUpdate(int|string $chatId, string $fromId, array $intent): void
    {
        $animalName = trim((string) data_get($intent, 'animal.name'));
        $clientName = trim((string) data_get($intent, 'client.name'));
        if ($animalName === '' || $clientName === '') {
            $this->sendMessage($chatId, 'Укажите кличку питомца и имя хозяина. Например: «Хозяйку Дейзи зовут Анастасия».');
            return;
        }

        $animals = Animal::with('client')
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($animalName)])
            ->orderBy('id')
            ->limit(8)
            ->get();

        if ($animals->isEmpty()) {
            $this->sendMessage($chatId, 'Питомец «'.$animalName.'» не найден. Проверьте кличку.');
            return;
        }

        $payload = [
            'client_name' => $clientName,
            'client_phone' => data_get($intent, 'client.phone'),
            'client_note' => data_get($intent, 'client.note'),
        ];

        if ($animals->count() === 1) {
            $this->askPetOwnerUpdateConfirmation($chatId, $fromId, $animals->first(), $payload);
            return;
        }

        $this->saveSession($fromId, $chatId, 'waiting_pet_owner_selection', $payload + [
            'animal_ids' => $animals->pluck('id')->map(fn ($id) => (int) $id)->all(),
        ]);
        $buttons = $animals->map(fn (Animal $animal) => [[
            'text' => $animal->name.' · '.$this->animalOwnerLabel($animal),
            'callback_data' => 'pet_owner:choose:'.$animal->id,
        ]])->all();
        $buttons[] = [['text' => 'Отмена', 'callback_data' => 'cancel']];
        $this->sendMessage($chatId, 'Нашёл несколько питомцев с кличкой «'.$animalName.'». Выберите нужного:', ['inline_keyboard' => $buttons]);
    }

    private function askPetOwnerUpdateConfirmation(int|string $chatId, string $fromId, Animal $animal, array $payload): void
    {
        $clientName = trim((string) ($payload['client_name'] ?? ''));
        $client = Client::whereRaw('LOWER(name) = ?', [mb_strtolower($clientName)])->first();
        $target = $client ? $client->name : $clientName.' (новый клиент)';
        $current = $animal->client?->name ?: 'не указан';

        $this->saveSession($fromId, $chatId, 'waiting_pet_owner_confirmation', $payload + ['animal_id' => $animal->id]);
        $this->sendMessage($chatId, 'У питомца '.$animal->name."\n"
            .'Сейчас хозяин: '.$current."\n"
            .'Новый хозяин: '.$target."\n\n"
            .'Сохранить?', [
                'inline_keyboard' => [[
                    ['text' => 'Сохранить', 'callback_data' => 'pet_owner:confirm'],
                    ['text' => 'Отмена', 'callback_data' => 'cancel'],
                ]],
            ]);
    }

    private function animalOwnerLabel(Animal $animal): string
    {
        return $animal->client?->name ?: 'без хозяина';
    }

    private function ownerUpdateIntentFromText(string $text): ?array
    {
        $text = trim($text);
        $patterns = [
            '/^(?:хозяина|хозяйку)\s+(.+?)\s+зовут\s+(.+?)\.?$/ui',
            '/^у\s+(.+?)\s+(?:хозяин|хозяйка)\s+(.+?)\.?$/ui',
            '/^(?:поменяй|измени)\s+(?:хозяина|хозяйку)\s+(.+?)\s+на\s+(.+?)\.?$/ui',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return [
                    'intent' => 'update_pet_owner',
                    'animal' => ['name' => trim($matches[1])],
                    'client' => ['name' => trim($matches[2])],
                ];
            }
        }

        return null;
    }

    private function anonymousOrderIntentFromText(string $text): ?array
    {
        $normalized = mb_strtolower(trim($text));
        if (!str_contains($normalized, 'уход')
            || !preg_match('/\b(\d{1,2})\s*(?:и|до|по|[-–])\s*(\d{1,2})\b/u', $normalized, $dates)) {
            return null;
        }

        $animals = [];
        $numbers = [
            'один' => 1, 'одна' => 1, 'одним' => 1,
            'два' => 2, 'две' => 2, 'двумя' => 2, 'двух' => 2,
            'три' => 3, 'трех' => 3, 'трёх' => 3, 'тремя' => 3,
            'четыре' => 4, 'четырех' => 4, 'четырёх' => 4, 'четырьмя' => 4,
            'пять' => 5, 'пяти' => 5, 'пятью' => 5,
        ];
        $quantity = function (?string $value) use ($numbers): int {
            if (!$value) return 1;
            return is_numeric($value) ? (int) $value : ($numbers[$value] ?? 1);
        };

        $numberPattern = '(\d+|один|одна|одним|два|две|двумя|двух|три|трех|трёх|тремя|четыре|четырех|четырёх|четырьмя|пять|пяти|пятью)';
        if (preg_match('/(?:'.$numberPattern.'\s+)?(?:кот(?:а|ов|ами)?|кошк(?:а|и|ек|ами)?)/u', $normalized, $cats)) {
            $animals[] = ['name' => null, 'species' => 'кошка', 'quantity' => $quantity($cats[1] ?? null)];
        }
        if (preg_match('/(?:'.$numberPattern.'\s+)?собак(?:а|и|ой|ами)?/u', $normalized, $dogs)) {
            $animals[] = ['name' => null, 'species' => 'собака', 'quantity' => $quantity($dogs[1] ?? null)];
        }
        if (empty($animals)) {
            return null;
        }

        $today = now()->startOfDay();
        $start = $today->copy()->startOfMonth()->addDays((int) $dates[1] - 1);
        if ($start->lessThan($today)) {
            $start = $start->addMonthNoOverflow()->startOfMonth()->addDays((int) $dates[1] - 1);
        }
        $end = $start->copy()->startOfMonth()->addDays((int) $dates[2] - 1);
        if ($end->lessThan($start)) {
            $end = $end->addMonthNoOverflow()->startOfMonth()->addDays((int) $dates[2] - 1);
        }

        return [
            'intent' => 'create_service_order',
            'service_type' => 'уход',
            'units_per_day' => 1,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'animals' => $animals,
        ];
    }

    private function anonymousAnimalLabel(?string $categoryName, int $quantity): string
    {
        return match (mb_strtolower((string) $categoryName)) {
            'кошки' => $quantity.' '.($quantity === 1 ? 'кошка' : 'кошки'),
            'собаки' => $quantity.' '.($quantity === 1 ? 'собака' : 'собаки'),
            default => $quantity.' '.mb_strtolower((string) $categoryName),
        };
    }

    private function serviceOrderAnimalsLabel(ServiceOrder $order): string
    {
        return $order->animals
            ->map(fn ($animal) => $animal->label ?: $this->anonymousAnimalLabel($animal->category?->name, $animal->quantity))
            ->implode(', ');
    }

    private function sendHelp(int|string $chatId): void
    {
        $this->sendMessage($chatId, <<<'TEXT'
Я помогаю вести календарь услуг и карточки питомцев.

Новая запись
• Передержка: «Запиши кошку Пухлю с 22 по 25 августа, передержка».
• Уход: «Кошка Пухля с 22 по 25 августа, уход».
• Выгул: «Собака Рекс с 22 по 25 августа, выгул 2 раза в день».

Я уточню категорию, питомца и хозяина при необходимости, покажу стоимость и попрошу подтвердить запись.

Календарь
• «Кто сейчас на попечении?»
• «Какие питомцы предстоят?»
• «Покажи записи на эту неделю».
• «Покажи записи на этот месяц».
• «Календарь» — календарь текущего месяца с питомцами.
• «Следующий месяц» — календарь следующего месяца.

Ещё умею
• «Покажи Пухлю» — карточка питомца.
• «Покажи хозяина Пухли» — карточка клиента.
• «Удали запись Пухли с 22 по 25 августа» — отмена записи.
• «Бобик не передержка, а выгул» — исправление услуги в записи.
• «С 22 по 25 августа уход: три кошки и собака, кличек пока не знаю» — заказ без карточек питомцев.
• «Заказы» — открыть список активных и будущих заказов. Внутри можно изменить период, клиента, адрес и комментарий; менять количество питомцев и добавлять услуги; архивировать или удалить заказ с подтверждением.

Можно писать голосовыми: я сначала покажу распознанный текст. Для отмены текущего диалога — «отмена».
TEXT);
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

        $boarding = Boarding::create([
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

        $order = ServiceOrder::create([
            'legacy_boarding_id' => $boarding->id,
            'client_id' => $boarding->client_id,
            'service_type' => $boarding->service_type,
            'units_per_day' => $boarding->units_per_day,
            'daily_price' => $boarding->unit_price * $boarding->units_per_day,
            'start_date' => $boarding->start_date,
            'end_date' => $boarding->end_date,
            'note' => $boarding->note,
            'source' => $boarding->source,
            'status' => $boarding->status,
            'confirmed_at' => $boarding->confirmed_at,
        ]);
        $orderAnimal = $order->animals()->create(['animal_id' => $animal->id, 'category_id' => $animal->category_id, 'label' => $animal->name, 'quantity' => 1, 'note' => $boarding->description]);
        $orderAnimal->services()->create(['service_order_id' => $order->id, 'service_type' => $boarding->service_type, 'units_per_day' => $boarding->units_per_day, 'unit_price' => $boarding->unit_price]);

        return $boarding;
    }

    private function sendServiceOrdersMenu(int|string $chatId): void
    {
        $orders = ServiceOrder::with(['animals.category', 'animals.animal'])
            ->whereNull('archived_at')->whereDate('end_date', '>=', today())
            ->orderBy('start_date')->limit(12)->get();

        if ($orders->isEmpty()) {
            $this->sendMessage($chatId, 'Активных и предстоящих заказов нет.');
            return;
        }

        $buttons = $orders->map(fn (ServiceOrder $order) => [[
            'text' => '#'.$order->id.' · '.$this->serviceOrderAnimalsLabel($order).' · '.$order->start_date->format('d.m').'–'.$order->end_date->format('d.m'),
            'callback_data' => 'orders:open:'.$order->id,
        ]])->all();
        $this->sendMessage($chatId, 'Заказы: выберите заказ для просмотра или изменения.', ['inline_keyboard' => $buttons]);
    }

    private function serviceOrderForBot(int $orderId): ?ServiceOrder
    {
        return ServiceOrder::with(['client', 'animals.category', 'animals.animal', 'animals.services'])
            ->whereNull('archived_at')->find($orderId);
    }

    private function showServiceOrderMenu(int|string $chatId, int $orderId): void
    {
        $order = $this->serviceOrderForBot($orderId);
        if (!$order) {
            $this->sendMessage($chatId, 'Заказ не найден или уже в архиве.');
            return;
        }

        $text = "Заказ #{$order->id}\n";
        $text .= 'Период: '.$this->russianDatePeriod($order->start_date, $order->end_date)."\n";
        $text .= 'Клиент: '.($order->client?->name ?: 'не указан')."\n";
        if ($order->address) { $text .= 'Адрес: '.$order->address."\n"; }
        $text .= "\nПитомцы и услуги:\n";
        foreach ($order->animals as $position) {
            $name = $position->animal?->name ?: $position->label ?: $this->anonymousAnimalLabel($position->category?->name, $position->quantity);
            $services = $position->services->map(fn ($service) => $service->service_type.($service->service_type === 'передержка' ? '' : ' · '.$service->units_per_day.' р/д'))->implode(', ');
            $text .= '• '.($position->quantity > 1 ? $position->quantity.' × ' : '').$name.' — '.$services."\n";
        }
        if ($order->note) { $text .= "\nКомментарий: {$order->note}"; }

        $this->sendMessage($chatId, trim($text), ['inline_keyboard' => [
            [['text' => 'Редактировать', 'callback_data' => 'order:edit:'.$order->id], ['text' => 'Питомцы и услуги', 'callback_data' => 'order:pets:'.$order->id]],
            [['text' => 'В архив', 'callback_data' => 'order:archive:'.$order->id], ['text' => 'Удалить', 'callback_data' => 'order:delete:'.$order->id]],
            [['text' => 'К списку заказов', 'callback_data' => 'orders:list']],
        ]]);
    }

    private function showServiceOrderEditMenu(int|string $chatId, int $orderId): void
    {
        if (!$this->serviceOrderForBot($orderId)) { $this->sendMessage($chatId, 'Заказ не найден.'); return; }
        $this->sendMessage($chatId, 'Что изменить в заказе?', ['inline_keyboard' => [
            [['text' => 'Период', 'callback_data' => 'order:field:'.$orderId.':dates'], ['text' => 'Клиента', 'callback_data' => 'order:field:'.$orderId.':client']],
            [['text' => 'Адрес', 'callback_data' => 'order:field:'.$orderId.':address'], ['text' => 'Комментарий', 'callback_data' => 'order:field:'.$orderId.':note']],
            [['text' => 'Питомцев и услуги', 'callback_data' => 'order:pets:'.$orderId]],
            [['text' => 'Назад к заказу', 'callback_data' => 'orders:open:'.$orderId]],
        ]]);
    }

    private function startServiceOrderFieldEdit(int|string $chatId, string $fromId, int $orderId, string $field): void
    {
        if (!$this->serviceOrderForBot($orderId)) { $this->sendMessage($chatId, 'Заказ не найден.'); return; }
        $prompts = ['dates' => 'Введите период: 22.08.2026 — 25.08.2026', 'address' => 'Введите новый адрес.', 'note' => 'Введите комментарий к заказу.', 'client' => 'Введите имя клиента. Если такого клиента нет, он будет создан.'];
        $this->saveSession($fromId, $chatId, 'waiting_order_'.$field, ['order_id' => $orderId]);
        $this->sendMessage($chatId, $prompts[$field]);
    }

    private function askServiceOrderDestructiveConfirmation(int|string $chatId, string $fromId, int $orderId, string $action): void
    {
        $order = $this->serviceOrderForBot($orderId);
        if (!$order) { $this->sendMessage($chatId, 'Заказ не найден.'); return; }
        $verb = $action === 'archive' ? 'перенесён в архив' : 'удалён';
        $this->saveSession($fromId, $chatId, 'waiting_order_'.$action.'_confirmation', ['order_id' => $order->id]);
        $this->sendMessage($chatId, "Заказ #{$order->id} будет {$verb}. Подтвердить?", ['inline_keyboard' => [[
            ['text' => $action === 'archive' ? 'В архив' : 'Удалить', 'callback_data' => 'order:'.$action.':'.$order->id.':confirm'],
            ['text' => 'Отмена', 'callback_data' => 'cancel'],
        ]]]);
    }

    private function confirmServiceOrderDestructiveAction(int|string $chatId, string $fromId, int $orderId, string $action): void
    {
        $order = $this->serviceOrderForBot($orderId);
        if (!$order) { $this->clearSession($fromId); $this->sendMessage($chatId, 'Заказ не найден.'); return; }
        if ($action === 'archive') { $order->update(['archived_at' => now(), 'status' => 'archived']); $this->syncLegacyBoardingForServiceOrder($order); }
        else { $order->delete(); }
        $this->clearSession($fromId);
        $this->sendMessage($chatId, $action === 'archive' ? 'Заказ перенесён в архив.' : 'Заказ удалён.');
        $this->sendServiceOrdersMenu($chatId);
    }

    private function showServiceOrderPetsMenu(int|string $chatId, int $orderId): void
    {
        $order = $this->serviceOrderForBot($orderId);
        if (!$order) { $this->sendMessage($chatId, 'Заказ не найден.'); return; }
        $buttons = $order->animals->map(fn ($position) => [[
            'text' => ($position->animal?->name ?: $position->label ?: 'Без клички').' · '.$position->quantity.' шт.',
            'callback_data' => 'order:pet:'.$order->id.':'.$position->id,
        ]])->all();
        $buttons[] = [['text' => '+ Добавить питомца', 'callback_data' => 'order:addpet:'.$order->id]];
        $buttons[] = [['text' => 'Назад к заказу', 'callback_data' => 'orders:open:'.$order->id]];
        $this->sendMessage($chatId, 'Выберите питомца, чтобы изменить количество или услуги.', ['inline_keyboard' => $buttons]);
    }

    private function startServiceOrderPetAdd(int|string $chatId, string $fromId, int $orderId): void
    {
        if (!$this->serviceOrderForBot($orderId)) { $this->sendMessage($chatId, 'Заказ не найден.'); return; }
        $this->saveSession($fromId, $chatId, 'waiting_order_add_pet', ['order_id' => $orderId]);
        $this->sendMessage($chatId, 'Введите питомца одной строкой: «Кличка, вид, количество, услуга». Например: «Мурка, кошки, 1, уход». Если такой питомец уже есть, бот использует его карточку.');
    }

    private function showServiceOrderPetMenu(int|string $chatId, int $orderId, int $positionId): void
    {
        $order = $this->serviceOrderForBot($orderId); $position = $order?->animals->firstWhere('id', $positionId);
        if (!$position) { $this->sendMessage($chatId, 'Питомец в заказе не найден.'); return; }
        $name = $position->animal?->name ?: $position->label ?: 'Без клички';
        $services = $position->services->map(fn ($service) => $service->service_type.' · '.$service->units_per_day.' р/д · '.$service->unit_price.' ₽')->implode("\n• ");
        $keyboard = [
            [['text' => '− Количество', 'callback_data' => 'order:petqty:'.$orderId.':'.$positionId.':minus'], ['text' => '+ Количество', 'callback_data' => 'order:petqty:'.$orderId.':'.$positionId.':plus']],
        ];
        foreach ($position->services as $service) {
            $keyboard[] = [['text' => 'Изменить: '.$this->serviceLabel($service->service_type), 'callback_data' => 'order:service:'.$orderId.':'.$positionId.':'.$service->id]];
        }
        $keyboard = array_merge($keyboard, [
            [['text' => '+ Передержка', 'callback_data' => 'order:serviceadd:'.$orderId.':'.$positionId.':передержка'], ['text' => '+ Выгул', 'callback_data' => 'order:serviceadd:'.$orderId.':'.$positionId.':выгул']],
            [['text' => '+ Уход', 'callback_data' => 'order:serviceadd:'.$orderId.':'.$positionId.':уход'], ['text' => 'Удалить питомца', 'callback_data' => 'order:petdelete:'.$orderId.':'.$positionId]],
            [['text' => 'Назад к питомцам', 'callback_data' => 'order:pets:'.$orderId]],
        ]);
        $this->sendMessage($chatId, "{$name}, количество: {$position->quantity}\nУслуги:\n• {$services}", ['inline_keyboard' => $keyboard]);
    }

    private function changeServiceOrderPetQuantity(int|string $chatId, int $orderId, int $positionId, string $direction): void
    {
        $order = $this->serviceOrderForBot($orderId); $position = $order?->animals->firstWhere('id', $positionId);
        if (!$position) { $this->sendMessage($chatId, 'Питомец в заказе не найден.'); return; }
        $position->update(['quantity' => max(1, min(99, $position->quantity + ($direction === 'plus' ? 1 : -1)))]);
        $this->refreshServiceOrderPrice($order); $this->syncLegacyBoardingForServiceOrder($order->fresh(['animals.services', 'animals.animal']));
        $this->showServiceOrderPetMenu($chatId, $orderId, $positionId);
    }

    private function addServiceToOrderPet(int|string $chatId, int $orderId, int $positionId, string $serviceType): void
    {
        $order = $this->serviceOrderForBot($orderId); $position = $order?->animals->firstWhere('id', $positionId);
        if (!$position || !in_array($serviceType, BoardingPricingService::SERVICE_TYPES, true)) { $this->sendMessage($chatId, 'Не удалось добавить услугу.'); return; }
        if ($position->services->contains('service_type', $serviceType)) { $this->sendMessage($chatId, 'Эта услуга уже есть у питомца.'); return; }
        $species = $position->animal?->category?->name ?: $position->category?->name;
        $position->services()->create(['service_order_id' => $order->id, 'service_type' => $serviceType, 'units_per_day' => 1, 'unit_price' => $this->pricing->defaultRate($serviceType, $species, $position->animal?->dog_size)]);
        $this->refreshServiceOrderPrice($order); $this->syncLegacyBoardingForServiceOrder($order->fresh(['animals.services', 'animals.animal']));
        $this->showServiceOrderPetMenu($chatId, $orderId, $positionId);
    }

    private function showServiceOrderPetServiceMenu(int|string $chatId, int $orderId, int $positionId, int $serviceId): void
    {
        $order = $this->serviceOrderForBot($orderId); $position = $order?->animals->firstWhere('id', $positionId);
        $service = $position?->services->firstWhere('id', $serviceId);
        if (!$service) { $this->sendMessage($chatId, 'Услуга не найдена.'); return; }
        $this->sendMessage($chatId, $this->serviceLabel($service->service_type)."\nКратность: {$service->units_per_day} раз в день\nЦена: {$service->unit_price} ₽ за услугу", ['inline_keyboard' => [
            [[
                ['text' => '1 раз', 'callback_data' => 'order:serviceunits:'.$orderId.':'.$positionId.':'.$serviceId.':1'],
                ['text' => '2 раза', 'callback_data' => 'order:serviceunits:'.$orderId.':'.$positionId.':'.$serviceId.':2'],
                ['text' => '3 раза', 'callback_data' => 'order:serviceunits:'.$orderId.':'.$positionId.':'.$serviceId.':3'],
            ]],
            [['text' => 'Удалить услугу', 'callback_data' => 'order:servicedelete:'.$orderId.':'.$positionId.':'.$serviceId]],
            [['text' => 'Назад к питомцу', 'callback_data' => 'order:pet:'.$orderId.':'.$positionId]],
        ]]);
    }

    private function changeServiceOrderPetServiceUnits(int|string $chatId, int $orderId, int $positionId, int $serviceId, int $units): void
    {
        $order = $this->serviceOrderForBot($orderId); $position = $order?->animals->firstWhere('id', $positionId);
        $service = $position?->services->firstWhere('id', $serviceId);
        if (!$service || $units < 1 || $units > 24) { $this->sendMessage($chatId, 'Не удалось изменить кратность услуги.'); return; }
        $service->update(['units_per_day' => $units]); $this->refreshServiceOrderPrice($order);
        $this->syncLegacyBoardingForServiceOrder($order->fresh(['animals.services', 'animals.animal']));
        $this->showServiceOrderPetServiceMenu($chatId, $orderId, $positionId, $serviceId);
    }

    private function askServiceOrderPetServiceDeletion(int|string $chatId, string $fromId, int $orderId, int $positionId, int $serviceId): void
    {
        $order = $this->serviceOrderForBot($orderId); $position = $order?->animals->firstWhere('id', $positionId);
        if (!$position?->services->firstWhere('id', $serviceId)) { $this->sendMessage($chatId, 'Услуга не найдена.'); return; }
        $this->saveSession($fromId, $chatId, 'waiting_order_service_delete_confirmation', ['order_id' => $orderId, 'position_id' => $positionId, 'service_id' => $serviceId]);
        $this->sendMessage($chatId, 'Удалить услугу у этого питомца?', ['inline_keyboard' => [[
            ['text' => 'Удалить', 'callback_data' => 'order:servicedelete:'.$orderId.':'.$positionId.':'.$serviceId.':confirm'], ['text' => 'Отмена', 'callback_data' => 'cancel'],
        ]]]);
    }

    private function confirmServiceOrderPetServiceDeletion(int|string $chatId, string $fromId, int $orderId, int $positionId, int $serviceId): void
    {
        $order = $this->serviceOrderForBot($orderId); $position = $order?->animals->firstWhere('id', $positionId);
        $service = $position?->services->firstWhere('id', $serviceId);
        if (!$service) { $this->clearSession($fromId); $this->sendMessage($chatId, 'Услуга не найдена.'); return; }
        if ($position->services->count() < 2) { $this->sendMessage($chatId, 'У питомца должна остаться хотя бы одна услуга. Удалите питомца или весь заказ, если он больше не нужен.'); return; }
        $service->delete(); $this->refreshServiceOrderPrice($order);
        $this->syncLegacyBoardingForServiceOrder($order->fresh(['animals.services', 'animals.animal']));
        $this->clearSession($fromId); $this->sendMessage($chatId, 'Услуга удалена.'); $this->showServiceOrderPetMenu($chatId, $orderId, $positionId);
    }

    private function askServiceOrderPetDeletion(int|string $chatId, string $fromId, int $orderId, int $positionId): void
    {
        $order = $this->serviceOrderForBot($orderId);
        if (!$order || !$order->animals->contains('id', $positionId)) { $this->sendMessage($chatId, 'Питомец в заказе не найден.'); return; }
        $this->saveSession($fromId, $chatId, 'waiting_order_pet_delete_confirmation', ['order_id' => $orderId, 'position_id' => $positionId]);
        $this->sendMessage($chatId, 'Удалить питомца и его услуги из заказа?', ['inline_keyboard' => [[
            ['text' => 'Удалить', 'callback_data' => 'order:petdelete:'.$orderId.':'.$positionId.':confirm'], ['text' => 'Отмена', 'callback_data' => 'cancel'],
        ]]]);
    }

    private function confirmServiceOrderPetDeletion(int|string $chatId, string $fromId, int $orderId, int $positionId): void
    {
        $order = $this->serviceOrderForBot($orderId); $position = $order?->animals->firstWhere('id', $positionId);
        if (!$position) { $this->clearSession($fromId); $this->sendMessage($chatId, 'Питомец в заказе не найден.'); return; }
        if ($order->animals->count() < 2) { $this->sendMessage($chatId, 'В заказе должен остаться хотя бы один питомец. Удалите весь заказ, если он больше не нужен.'); return; }
        $position->services()->delete(); $position->delete(); $this->refreshServiceOrderPrice($order);
        $this->clearSession($fromId); $this->sendMessage($chatId, 'Питомец удалён из заказа.'); $this->showServiceOrderPetsMenu($chatId, $orderId);
    }

    private function refreshServiceOrderPrice(ServiceOrder $order): void
    {
        $order->load('animals.services'); $first = $order->animals->first()?->services->first();
        $order->update(['daily_price' => $order->animals->sum(fn ($position) => $position->quantity * $position->services->sum(fn ($service) => $service->units_per_day * $service->unit_price)),
            'service_type' => $first?->service_type ?: $order->service_type, 'units_per_day' => $first?->units_per_day ?: $order->units_per_day]);
    }

    private function syncLegacyBoardingForServiceOrder(ServiceOrder $order): void
    {
        if (!$order->legacy_boarding_id || !($boarding = Boarding::find($order->legacy_boarding_id))) { return; }
        $position = $order->animals->first(); $service = $position?->services->first();
        $boarding->update(['client_id' => $order->client_id, 'animal_id' => $position?->animal_id, 'name' => $position?->animal?->name ?: $position?->label ?: $boarding->name,
            'service_type' => $service?->service_type ?: $order->service_type, 'units_per_day' => $service?->units_per_day ?: $order->units_per_day,
            'unit_price' => $service?->unit_price ?: $order->daily_price, 'start_date' => $order->start_date, 'end_date' => $order->end_date,
            'note' => $order->note, 'archived_at' => $order->archived_at]);
    }

    private function sendBookingsList(int|string $chatId, array $intent): void
    {
        $start = $intent['start_date'] ? Carbon::parse($intent['start_date'])->startOfDay() : now()->startOfMonth();
        $end = $intent['end_date'] ? Carbon::parse($intent['end_date'])->endOfDay() : now()->endOfMonth();

        $rows = Boarding::with(['animal.client', 'client'])
            ->whereNull('archived_at')
            ->doesntHave('serviceOrder')
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                    ->orWhere(fn ($sub) => $sub->where('start_date', '<=', $start)->where('end_date', '>=', $end));
            })
            ->orderBy('start_date')
            ->get();

        $orders = ServiceOrder::with('animals.category')
            ->whereNull('archived_at')
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                    ->orWhere(fn ($sub) => $sub->where('start_date', '<=', $start)->where('end_date', '>=', $end));
            })
            ->orderBy('start_date')
            ->get();

        if ($rows->isEmpty() && $orders->isEmpty()) {
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

        foreach ($orders->take(max(0, 40 - $rows->count())) as $order) {
            $text .= "\n🐾 ".$this->serviceOrderAnimalsLabel($order)."\n";
            $text .= '📅 '.$this->russianDatePeriod($order->start_date, $order->end_date)."\n";
            $text .= '🛎 '.$this->serviceLabel($order->service_type)."\n";
        }

        if ($rows->count() + $orders->count() > 40) {
            $text .= '…ещё '.($rows->count() + $orders->count() - 40);
        }

        $this->sendMessage($chatId, trim($text));
    }

    private function sendMonthlyCalendar(int|string $chatId, Carbon $month): void
    {
        $month = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();
        $bookings = Boarding::with('animal')
            ->whereNull('archived_at')
            ->doesntHave('serviceOrder')
            ->whereDate('start_date', '<=', $monthEnd)
            ->whereDate('end_date', '>=', $month)
            ->orderBy('start_date')
            ->get();
        $orders = ServiceOrder::with('animals.category')
            ->whereNull('archived_at')
            ->whereDate('start_date', '<=', $monthEnd)
            ->whereDate('end_date', '>=', $month)
            ->orderBy('start_date')
            ->get();
        $orders->each(fn (ServiceOrder $order) => $order->setAttribute('calendar_quantity', $order->animals->sum('quantity')));
        $calendarEntries = $bookings->concat($orders);

        $monthNames = [
            1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель',
            5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август',
            9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь',
        ];
        $text = '📅 '.$monthNames[$month->month].' '.$month->year."\n";

        if ($calendarEntries->isEmpty()) {
            $text .= 'В этом месяце записей нет.';
        } else {
            $text .= "Питомцы в календаре\n";
            foreach ($bookings->take(15) as $booking) {
                $name = htmlspecialchars($this->bookingAnimalName($booking), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $text .= '• '.$this->russianDatePeriod($booking->start_date, $booking->end_date)
                    .' — '.$name.' · '.$this->serviceLabel($booking->service_type)."\n";
            }
            foreach ($orders->take(max(0, 15 - $bookings->count())) as $order) {
                $text .= '• '.$this->russianDatePeriod($order->start_date, $order->end_date)
                    .' — '.$this->serviceOrderAnimalsLabel($order).' · '.$this->serviceLabel($order->service_type)."\n";
            }
            if ($calendarEntries->count() > 15) {
                $text .= '…ещё '.($calendarEntries->count() - 15).' записей';
            }
        }

        $previous = $month->copy()->subMonthNoOverflow()->format('Y-m');
        $next = $month->copy()->addMonthNoOverflow()->format('Y-m');
        $keyboard = [
            'inline_keyboard' => [[
                ['text' => '‹ Предыдущий', 'callback_data' => 'calendar:'.$previous],
                ['text' => 'Следующий ›', 'callback_data' => 'calendar:'.$next],
            ]],
        ];

        $path = null;
        try {
            $path = $this->calendarImage->render($month, $calendarEntries);
            $this->sendPhoto($chatId, $path, trim($text), $keyboard);
        } catch (Throwable $e) {
            Log::warning('Telegram calendar image could not be generated.', ['error' => $e->getMessage()]);
            $this->sendMessage($chatId, trim($text), $keyboard);
        } finally {
            if ($path && is_file($path)) {
                @unlink($path);
            }
        }
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
        $client->loadMissing(['photos', 'animals.photos']);
        $text = "Хозяин: {$client->name}\n";
        $text .= 'Телефон: '.($client->phone ?: 'не указан')."\n";
        $text .= 'Фото: '.($client->photos->isNotEmpty() ? 'есть' : 'нет')."\n";
        $text .= 'Заметка: '.($client->note ?: '—')."\n";
        $text .= 'Питомцы: '.($client->animals->isNotEmpty() ? $client->animals->pluck('name')->join(', ') : 'не добавлены');

        $this->sendClientPhotos($chatId, $client, 1);
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
        $text .= 'Фото: '.($animal->photos->isNotEmpty() ? 'есть' : 'нет')."\n";
        $tags = collect($animal->tags ?? [])->filter(fn ($tag) => is_array($tag) && filled($tag['name'] ?? null));
        if ($tags->isNotEmpty()) {
            $text .= "Теги:\n".$tags->map(fn (array $tag) => (($tag['type'] ?? '') === 'positive' ? '🟢 ' : '🔴 ').trim($tag['name']))->implode("\n")."\n";
        }
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
            if ($photo->telegram_file_id) {
                $this->telegramApi('sendPhoto', [
                    'chat_id' => $chatId,
                    'photo' => $photo->telegram_file_id,
                    'caption' => $animal->name,
                ]);
                continue;
            }

            $path = Storage::disk('public')->path($photo->path);
            if (is_file($path)) {
                $this->sendPhoto($chatId, $path, $animal->name);
            }
        }
    }

    private function sendClientPhotos(int|string $chatId, Client $client, ?int $limit = null): void
    {
        $photos = $client->photos;
        if ($limit) {
            $photos = $photos->take($limit);
        }

        foreach ($photos->take(10) as $photo) {
            if ($photo->telegram_file_id) {
                $this->telegramApi('sendPhoto', [
                    'chat_id' => $chatId,
                    'photo' => $photo->telegram_file_id,
                    'caption' => $client->name,
                ]);
                continue;
            }

            $path = Storage::disk('public')->path($photo->path);
            if (is_file($path)) {
                $this->sendPhoto($chatId, $path, $client->name);
            }
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

        // A photo caption often contains just a name. Do not delegate that
        // choice to the intent model: a pet name may look like a person's name.
        $name = $this->photoSubjectFromCaption($caption);
        if (!$name) {
            $this->sendMessage($chatId, 'Не понял, кому принадлежит фото. Укажите в подписи кличку питомца или имя клиента.');
            return;
        }

        $animals = Animal::with('client')
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->orderBy('id')
            ->limit(8)
            ->get();
        $clients = Client::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->orderBy('id')
            ->limit(8)
            ->get();
        if ($animals->isEmpty() && $clients->isEmpty()) {
            $this->sendMessage($chatId, 'Не нашёл питомца или клиента «'.$name.'». Проверьте имя или сначала создайте карточку.');
            return;
        }

        if ($animals->count() === 1 && $clients->isEmpty()) {
            $this->askPetPhotoConfirmation($chatId, $fromId, $animals->first(), $fileId);
            return;
        }

        if ($clients->count() === 1 && $animals->isEmpty()) {
            $this->askClientPhotoConfirmation($chatId, $fromId, $clients->first(), $fileId);
            return;
        }

        $this->askPhotoTarget($chatId, $fromId, $fileId, $animals, $clients, $name);
    }

    private function askPhotoTarget(int|string $chatId, string $fromId, string $fileId, $animals, $clients, string $name): void
    {
        $this->saveSession($fromId, $chatId, 'waiting_photo_target_selection', [
            'file_id' => $fileId,
            'animal_ids' => $animals->pluck('id')->map(fn ($id) => (int) $id)->all(),
            'client_ids' => $clients->pluck('id')->map(fn ($id) => (int) $id)->all(),
        ]);
        $buttons = $animals->map(fn (Animal $animal) => [[
            'text' => '🐾 Питомец: '.$animal->name.' · '.$this->animalOwnerLabel($animal),
            'callback_data' => 'photo_target:animal:'.$animal->id,
        ]])->all();
        foreach ($clients as $client) {
            $buttons[] = [[
                'text' => '👤 Клиент: '.$client->name,
                'callback_data' => 'photo_target:client:'.$client->id,
            ]];
        }
        $buttons[] = [['text' => 'Отмена', 'callback_data' => 'cancel']];
        $this->sendMessage($chatId, 'К кому прикрепить фото «'.$name.'»?', ['inline_keyboard' => $buttons]);
    }

    private function askPetPhotoConfirmation(int|string $chatId, string $fromId, Animal $animal, string $fileId): void
    {
        if ($fileId === '') {
            $this->sendMessage($chatId, 'Не удалось получить фото. Отправьте его ещё раз.');
            return;
        }

        $this->saveSession($fromId, $chatId, 'waiting_pet_photo_confirmation', [
            'animal_id' => $animal->id,
            'file_id' => $fileId,
        ]);
        $owner = $this->animalOwnerLabel($animal);
        $this->sendMessage($chatId, 'Добавить это фото в профиль питомца '.$animal->name.' (хозяин: '.$owner.')?', [
            'inline_keyboard' => [[
                ['text' => 'Да, добавить', 'callback_data' => 'pet_photo:confirm'],
                ['text' => 'Отмена', 'callback_data' => 'cancel'],
            ]],
        ]);
    }

    private function animalNameFromPhotoCaption(string $caption): ?string
    {
        if (preg_match('/^(?:это\s+)?(?:фото|фотография)\s+(.+?)[.!]?$/ui', trim($caption), $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    private function photoSubjectFromCaption(string $caption): ?string
    {
        $name = $this->animalNameFromPhotoCaption($caption);
        if ($name) {
            return $name;
        }

        $caption = trim(preg_replace('/[.!]+$/u', '', $caption) ?? '');

        return mb_strlen($caption) <= 120 ? $caption : null;
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

    private function askClientPhotoConfirmation(int|string $chatId, string $fromId, Client $client, string $fileId): void
    {
        if ($fileId === '') {
            $this->sendMessage($chatId, 'Не удалось получить фото. Отправьте его ещё раз.');
            return;
        }

        $this->saveSession($fromId, $chatId, 'waiting_client_photo_confirmation', [
            'client_id' => $client->id,
            'file_id' => $fileId,
        ]);
        $this->sendMessage($chatId, 'Добавить это фото в профиль клиента '.$client->name.'?', [
            'inline_keyboard' => [[
                ['text' => 'Да, добавить', 'callback_data' => 'client_photo:confirm'],
                ['text' => 'Отмена', 'callback_data' => 'cancel'],
            ]],
        ]);
    }

    private function storeTelegramClientPhoto(Client $client, string $fileId): void
    {
        $file = $this->telegramApi('getFile', ['file_id' => $fileId]);
        $path = $file['result']['file_path'] ?? null;
        $bytes = $this->downloadTelegramFile($fileId, $path);
        if ($bytes === null) {
            return;
        }

        $ext = pathinfo((string) $path, PATHINFO_EXTENSION) ?: 'jpg';
        $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '', $fileId) ?: Str::random(12);
        $storagePath = 'clients/'.$client->id.'/telegram-'.$safeId.'.'.$ext;
        Storage::disk('public')->put($storagePath, $bytes);
        $client->photos()->firstOrCreate(['telegram_file_id' => $fileId], ['path' => $storagePath]);
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
            'кошки' => 'кошки',
            'собака' => 'собаки',
            'собаки' => 'собаки',
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
        return is_string($secret) && $secret !== ''
            && hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token'));
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
        })->values()->chunk(1)->map(fn ($row) => $row->values()->all())->all();
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

    private function sendMessage(int|string $chatId, string $text, ?array $replyMarkup = null, ?string $parseMode = null): void
    {
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        if ($replyMarkup) {
            $payload['reply_markup'] = $replyMarkup;
        }

        if ($parseMode) {
            $payload['parse_mode'] = $parseMode;
        }

        $this->telegramApi('sendMessage', $payload);
    }

    private function sendPhoto(int|string $chatId, string $path, string $caption, ?array $replyMarkup = null): void
    {
        $token = config('services.telegram.bot_token');
        if (!$token || !is_file($path)) {
            throw new \RuntimeException('Не удалось подготовить изображение календаря.');
        }

        $payload = ['chat_id' => $chatId, 'caption' => $caption];
        if ($replyMarkup) {
            $payload['reply_markup'] = json_encode($replyMarkup, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }

        $response = Http::timeout(30)
            ->attach('photo', fopen($path, 'r'), 'calendar.png')
            ->post("https://api.telegram.org/bot{$token}/sendPhoto", $payload);
        $result = $response->json();
        if (!$response->successful() || !is_array($result) || !($result['ok'] ?? false)) {
            Log::warning('Telegram API returned an unsuccessful photo response.', [
                'status' => $response->status(),
                'error_code' => $result['error_code'] ?? null,
                'description' => $result['description'] ?? null,
            ]);
            throw new TelegramApiException('Telegram не принял изображение календаря.');
        }
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
            throw new TelegramApiException('Не настроен токен Telegram-бота.');
        }

        try {
            $response = Http::timeout(30)->post("https://api.telegram.org/bot{$token}/{$method}", $payload);
        } catch (Throwable $e) {
            Log::warning('Telegram API request failed.', [
                'method' => $method,
                'error' => $e->getMessage(),
            ]);

            throw new TelegramApiException('Не удалось подключиться к Telegram API.', previous: $e);
        }

        $result = $response->json();
        if (!$response->successful() || !is_array($result) || !($result['ok'] ?? false)) {
            Log::warning('Telegram API returned an unsuccessful response.', [
                'method' => $method,
                'status' => $response->status(),
                'error_code' => $result['error_code'] ?? null,
                'description' => $result['description'] ?? null,
            ]);
            throw new TelegramApiException('Telegram API вернул ошибку: '.($result['description'] ?? $response->status()));
        }

        return is_array($result) ? $result : ['ok' => false];
    }
}
