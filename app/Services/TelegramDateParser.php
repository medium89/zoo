<?php

namespace App\Services;

use Carbon\Carbon;
use Throwable;

class TelegramDateParser
{
    /** @return array{start_date: string, end_date: string}|null */
    public function parse(string $text): ?array
    {
        $text = $this->normalize($text);
        if ($text === '') {
            return null;
        }

        $items = preg_split('/\\s*(?:,|;|\\s+и\\s+)\\s*/u', $text);
        if (is_array($items) && count($items) > 1) {
            $context = $this->context($items);
            $dates = [];

            foreach ($items as $item) {
                $date = $this->parseValue($item, $context);
                if (! $date || ($dates && $date->lt(end($dates)))) {
                    return null;
                }
                $dates[] = $date;
                $context = $date;
            }

            return ['start_date' => $dates[0]->toDateString(), 'end_date' => end($dates)->toDateString()];
        }

        $period = $this->splitPeriod($text);
        if ($period === null) {
            $date = $this->parseValue($text, now()->startOfDay());

            return $date ? ['start_date' => $date->toDateString(), 'end_date' => $date->toDateString()] : null;
        }

        [$startText, $endText] = $period;
        $context = $this->context([$startText, $endText]);
        $start = $this->parseValue($startText, $context);
        $end = $this->parseValue($endText, $start ?: $context);
        if (! $start || ! $end) {
            return null;
        }

        while ($end->lt($start)) {
            $end = $this->hasExplicitMonth($endText) ? $end->addYearNoOverflow() : $end->addMonthNoOverflow();
        }

        return ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString()];
    }

    /** @return array{0: string, 1: string}|null */
    private function splitPeriod(string $text): ?array
    {
        foreach ([
            '/^(?:с|со)\\s+(.+?)\\s+(?:по|до)\\s+(.+)$/u',
            '/^(.+?)\\s+(?:по|до)\\s+(.+)$/u',
        ] as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return [trim($matches[1]), trim($matches[2])];
            }
        }

        $parts = preg_split('/\\s*(?:—|–|\\s+-\\s+|(?<=\\d)-(?=\\d)|(?<=[а-я])-(?=[а-я]))\\s*/u', $text);

        return is_array($parts) && count($parts) === 2 ? [trim($parts[0]), trim($parts[1])] : null;
    }

    /** @param array<int, string> $values */
    private function context(array $values): Carbon
    {
        $default = now()->startOfDay();

        foreach ($values as $value) {
            if ($this->hasExplicitMonth($value)) {
                return $this->parseValue($value, $default) ?: $default;
            }
        }

        return $default;
    }

    private function parseValue(string $value, Carbon $context): ?Carbon
    {
        $value = $this->normalize($value);
        $value = preg_replace('/^(?:с|со|на|в)\\s+/u', '', $value) ?? '';
        $value = trim(preg_replace('/\\s*(?:г\\.?|года)\\.?$/u', '', $value) ?? '');

        if (in_array($value, ['сегодня', 'завтра', 'послезавтра'], true)) {
            return now()->startOfDay()->addDays(array_search($value, ['сегодня', 'завтра', 'послезавтра'], true));
        }

        $day = null;
        $month = $context->month;
        $year = $context->year;

        if (preg_match('/^(\\d{1,2})(?:-?(?:го|е|й))?$/u', $value, $matches)) {
            $day = (int) $matches[1];
        } elseif (preg_match('/^(\\d{1,2})[.\\/-](\\d{1,2})(?:[.\\/-](\\d{2,4}))?\\.?$/', $value, $matches)) {
            $day = (int) $matches[1];
            $month = (int) $matches[2];
            $year = isset($matches[3]) ? $this->year((int) $matches[3]) : $year;
        } elseif (preg_match('/^(\\d{1,2})(?:-?(?:го|е|й))?\\s+([а-я.]+)(?:\\s+(\\d{2,4}))?$/u', $value, $matches)) {
            $months = [
                'январь' => 1, 'января' => 1, 'янв' => 1,
                'февраль' => 2, 'февраля' => 2, 'фев' => 2,
                'март' => 3, 'марта' => 3, 'мар' => 3,
                'апрель' => 4, 'апреля' => 4, 'апр' => 4,
                'май' => 5, 'мая' => 5,
                'июнь' => 6, 'июня' => 6, 'июн' => 6,
                'июль' => 7, 'июля' => 7, 'июл' => 7,
                'август' => 8, 'августа' => 8, 'авг' => 8,
                'сентябрь' => 9, 'сентября' => 9, 'сен' => 9, 'сент' => 9,
                'октябрь' => 10, 'октября' => 10, 'окт' => 10,
                'ноябрь' => 11, 'ноября' => 11, 'ноя' => 11,
                'декабрь' => 12, 'декабря' => 12, 'дек' => 12,
            ];
            $day = (int) $matches[1];
            $month = $months[rtrim($matches[2], '.')] ?? 0;
            $year = isset($matches[3]) ? $this->year((int) $matches[3]) : $year;
        }

        if (! $day || ! $month) {
            return null;
        }

        try {
            return Carbon::createSafe($year, $month, $day)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }

    private function normalize(string $value): string
    {
        $value = str_replace(['ё', '—', '–', '−', '‑'], ['е', '-', '-', '-', '-'], mb_strtolower(trim($value)));

        return trim(preg_replace('/\\s+/u', ' ', $value) ?? '');
    }

    private function hasExplicitMonth(string $value): bool
    {
        return (bool) preg_match('/\\d{1,2}[.\\/-]\\d{1,2}|(?:январ|феврал|март|апрел|ма[йя]|июн|июл|август|сентябр|октябр|ноябр|декабр)/u', $value);
    }

    private function year(int $year): int
    {
        return $year < 100 ? 2000 + $year : $year;
    }
}
