<?php

namespace App\Services;

use Carbon\Carbon;

class BookingListPeriodParser
{
    /** @return array{start_date: string, end_date: string}|null */
    public function parse(string $text): ?array
    {
        $normalized = mb_strtolower(trim($text));
        if (! $this->isListRequest($normalized)) {
            return null;
        }

        if (preg_match('/\bзавтра\b/ui', $normalized)) {
            $tomorrow = now()->addDay()->toDateString();

            return ['start_date' => $tomorrow, 'end_date' => $tomorrow];
        }

        if (! preg_match('/(?:\bс\s+)?(\d{1,2})\s*(?:по|[-–])\s*(\d{1,2})\s+([а-яё]+)/ui', $normalized, $matches)) {
            return null;
        }

        $month = $this->monthNumber($matches[3]);
        if (! $month) {
            return null;
        }

        $start = $this->nearestFutureDate((int) $matches[1], $month);
        $end = $this->nearestFutureDate((int) $matches[2], $month);
        if ($end->lt($start)) {
            $end->addYear();
        }

        return [
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
        ];
    }

    private function isListRequest(string $text): bool
    {
        return preg_match('/\b(кто|какие|покажи|показать|будет|будут|завтра)\b/ui', $text) === 1
            && preg_match('/\b(запис|заказ|передерж|питом|будет|будут|завтра)\b/ui', $text) === 1;
    }

    private function nearestFutureDate(int $day, int $month): Carbon
    {
        $date = Carbon::create(now()->year, $month, $day)->startOfDay();

        return $date->lt(today()) ? $date->addYear() : $date;
    }

    private function monthNumber(string $month): ?int
    {
        return [
            'января' => 1, 'февраля' => 2, 'марта' => 3, 'апреля' => 4,
            'мая' => 5, 'июня' => 6, 'июля' => 7, 'августа' => 8,
            'сентября' => 9, 'октября' => 10, 'ноября' => 11, 'декабря' => 12,
        ][mb_strtolower($month)] ?? null;
    }
}
