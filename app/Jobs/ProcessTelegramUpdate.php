<?php

namespace App\Jobs;

use App\Http\Controllers\Telegram\TelegramBotController;
use App\Models\TelegramWebhookUpdate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessTelegramUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(public readonly int $webhookUpdateId)
    {
    }

    public function handle(TelegramBotController $bot): void
    {
        $update = TelegramWebhookUpdate::find($this->webhookUpdateId);
        if (! $update || $update->processed_at) {
            return;
        }

        $bot->processUpdate($update->payload);
        $update->update(['processed_at' => now()]);
    }
}
