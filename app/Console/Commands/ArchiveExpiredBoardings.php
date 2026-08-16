<?php

namespace App\Console\Commands;

use App\Services\ExpiredBoardingArchiver;
use Illuminate\Console\Command;

class ArchiveExpiredBoardings extends Command
{
    protected $signature = 'boarding:archive-expired';

    protected $description = 'Move boardings whose end date has passed to the archive';

    public function handle(ExpiredBoardingArchiver $archiver): int
    {
        $count = $archiver->archive();
        $this->info("Archived: {$count}");

        return self::SUCCESS;
    }
}
