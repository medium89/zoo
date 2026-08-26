<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // The worker must stay alive between cron ticks: with stop-when-empty it
        // exits before a newly received Telegram update reaches the database.
        $schedule->command('queue:work database --queue=telegram --sleep=1 --max-time=55 --tries=3')
            ->everyMinute()
            ->withoutOverlapping();

        $schedule->command('telegram:send-tomorrow-bookings')
            ->everyMinute()
            ->withoutOverlapping();

        $schedule->command('telegram:send-boarding-task-notifications')
            ->everyMinute()
            ->withoutOverlapping();

        $schedule->command('boarding:archive-expired')
            ->dailyAt('00:05')
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
