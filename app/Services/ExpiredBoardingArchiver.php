<?php

namespace App\Services;

use App\Models\Boarding;

class ExpiredBoardingArchiver
{
    public function archive(): int
    {
        return Boarding::query()
            ->whereNull('archived_at')
            ->whereDate('end_date', '<', now()->startOfDay())
            ->update(['archived_at' => now()]);
    }
}
