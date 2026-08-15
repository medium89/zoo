<?php

namespace App\Console\Commands;

use App\Models\BoardingTask;
use App\Models\BoardingTaskMessage;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendBoardingTaskNotifications extends Command
{
    protected $signature = 'telegram:send-boarding-task-notifications';

    protected $description = 'Send scheduled boarding task notifications to Telegram';

    private const TIMEZONE = 'Asia/Barnaul';

    public function handle(): int
    {
        $now = Carbon::now(self::TIMEZONE);
        $chatIds = array_values(array_filter(config('services.telegram.chat_ids', [])));
        $token = config('services.telegram.bot_token');

        if (!$token || $chatIds === []) {
            Log::warning('Boarding task notifications were skipped: Telegram settings are incomplete.');

            return self::FAILURE;
        }

        $tasks = BoardingTask::with(['boarding.animal'])
            ->where('is_active', true)
            ->whereTime('scheduled_time', '<=', $now->format('H:i:00'))
            ->whereHas('boarding', function ($query) use ($now) {
                $query->whereNull('archived_at')
                    ->whereDate('start_date', '<=', $now->toDateString())
                    ->whereDate('end_date', '>=', $now->toDateString());
            })
            ->get();

        foreach ($tasks as $task) {
            $run = $task->runs()->firstOrCreate(
                ['notification_date' => $now->toDateString()],
                ['status' => 'pending'],
            );

            if ($run->status !== 'pending') {
                continue;
            }

            $sentChatIds = $run->messages()->pluck('chat_id')->all();
            foreach (array_diff(array_map('strval', $chatIds), $sentChatIds) as $chatId) {
                $response = Http::timeout(30)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $this->messageText($task, $now),
                    'reply_markup' => [
                        'inline_keyboard' => [[
                            ['text' => '✅ Готово', 'callback_data' => "task:{$run->id}:done"],
                            ['text' => '↩️ Отмена', 'callback_data' => "task:{$run->id}:cancel"],
                        ]],
                    ],
                ]);

                if ($response->successful() && ($messageId = data_get($response->json(), 'result.message_id'))) {
                    BoardingTaskMessage::create([
                        'boarding_task_run_id' => $run->id,
                        'chat_id' => $chatId,
                        'message_id' => $messageId,
                    ]);
                    continue;
                }

                Log::warning('Boarding task notification was not delivered.', [
                    'task_id' => $task->id,
                    'chat_id' => $chatId,
                    'status' => $response->status(),
                ]);
            }
        }

        return self::SUCCESS;
    }

    private function messageText(BoardingTask $task, Carbon $now): string
    {
        $animal = $task->boarding->animal?->name ?: $task->boarding->name;

        $instructions = trim((string) $task->instructions);

        return "🐾 {$animal}\n🕒 ".substr($task->scheduled_time, 0, 5)."\n\n{$task->title}".($instructions !== '' ? "\n\n{$instructions}" : '');
    }
}
