<?php

namespace App\Services;

use App\Models\Boarding;
use App\Models\ServiceOrder;

class ExpiredBoardingArchiver
{
    public function archive(): int
    {
        $archivedAt = now();
        $expired = fn ($query) => $query
            ->whereNull('archived_at')
            ->whereDate('end_date', '<', today());

        // ServiceOrder is now the canonical record. Keep legacy calendar
        // entries in sync while they are still present in the database.
        $orders = $expired(ServiceOrder::query())
            ->update(['archived_at' => $archivedAt, 'status' => 'archived']);

        $boardings = $expired(Boarding::query())
            ->update(['archived_at' => $archivedAt, 'status' => 'archived']);

        return $orders + $boardings;
    }
}
