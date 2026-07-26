<?php

namespace App\Console\Commands;

use App\Models\Boarding;
use App\Models\TelegramBotSetting;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendTomorrowBookingsNotification extends Command
{
    protected $signature = 'telegram:send-tomorrow-bookings {--force : Send immediately, ignoring the configured time and previous delivery}';

    protected $description = 'Send Telegram reminder with bookings scheduled for tomorrow';

    private const TIMEZONE = 'Asia/Barnaul';

    public function handle(): int
    {
        $settings = TelegramBotSetting::current();
        $now = Carbon::now(self::TIMEZONE);
        $tomorrow = $now->copy()->addDay()->startOfDay();
        $force = (bool) $this->option('force');

        if (!$force && !$settings->tomorrow_notifications_enabled) {
            return self::SUCCESS;
        }

        $configuredTime = substr((string) $settings->tomorrow_notification_time, 0, 5);
        if (!$force && $configuredTime !== $now->format('H:i')) {
            return self::SUCCESS;
        }

        if (!$force && $settings->last_tomorrow_notification_for?->isSameDay($tomorrow)) {
            return self::SUCCESS;
        }

        $chatIds = array_values(array_filter(config('services.telegram.chat_ids', [])));
        $token = config('services.telegram.bot_token');
        if (!$token || $chatIds === []) {
            Log::warning('Telegram tomorrow notification was skipped: bot token or chat ID is missing.');
            $this->error('TELEGRAM_BOT_TOKEN or TELEGRAM_CHAT_ID is not configured.');

            return self::FAILURE;
        }

        $bookings = Boarding::with(['animal', 'client'])
            ->whereNull('archived_at')
            ->whereDate('start_date', '<=', $tomorrow)
            ->whereDate('end_date', '>=', $tomorrow)
            ->orderBy('start_date')
            ->get();

        $text = $this->notificationText($tomorrow, $bookings);
        $sent = 0;

        foreach ($chatIds as $chatId) {
            try {
                $response = Http::timeout(30)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $text,
                ]);

                if ($response->ok()) {
                    $sent++;
                    continue;
                }

                Log::warning('Telegram tomorrow notification was not delivered.', [
                    'chat_id' => $chatId,
                    'status' => $response->status(),
                ]);
            } catch (\Throwable $exception) {
                Log::warning('Telegram tomorrow notification failed.', [
                    'chat_id' => $chatId,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        if ($sent === 0) {
            $this->error('Notification was not delivered.');

            return self::FAILURE;
        }

        if (!$force) {
            $settings->last_tomorrow_notification_for = $tomorrow;
            $settings->save();
        }

        $this->info('Tomorrow notification sent to '.$sent.' chat(s).');

        return self::SUCCESS;
    }

    private function notificationText(Carbon $tomorrow, $bookings): string
    {
        $heading = 'Напоминание на завтра, '.$this->russianDate($tomorrow).":";

        if ($bookings->isEmpty()) {
            return $heading."\n\nЗаписей нет.";
        }

        $lines = [$heading];
        foreach ($bookings as $booking) {
            $animal = $booking->animal?->name ?: $booking->name;
            $lines[] = "🐾 {$animal}\n📅 ".$this->russianPeriod($booking->start_date, $booking->end_date)."\n🛎 ".$this->serviceLabel($booking->service_type);
        }

        return implode("\n\n", $lines);
    }

    private function russianDate(Carbon $date): string
    {
        return $date->day.' '.$this->monthName($date->month).($date->year !== Carbon::now(self::TIMEZONE)->year ? ' '.$date->year : '');
    }

    private function russianPeriod(Carbon $start, Carbon $end): string
    {
        if ($start->isSameDay($end)) {
            return $this->russianDate($start);
        }

        if ($start->isSameMonth($end) && $start->year === $end->year) {
            return $start->day.'–'.$end->day.' '.$this->monthName($end->month);
        }

        return $this->russianDate($start).' — '.$this->russianDate($end);
    }

    private function monthName(int $month): string
    {
        return [
            1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля',
            5 => 'мая', 6 => 'июня', 7 => 'июля', 8 => 'августа',
            9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря',
        ][$month];
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
}
