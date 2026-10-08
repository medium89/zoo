<?php

namespace Tests\Unit;

use App\Services\TelegramDateParser;
use Carbon\Carbon;
use Tests\TestCase;

class TelegramDateParserTest extends TestCase
{
    /**
     * @dataProvider periods
     */
    public function test_parses_human_periods(string $input, string $start, string $end): void
    {
        Carbon::setTestNow('2026-10-08 10:00:00');

        $this->assertSame(
            ['start_date' => $start, 'end_date' => $end],
            app(TelegramDateParser::class)->parse($input),
        );
    }

    public static function periods(): array
    {
        return [
            ['с 24 ноября по 5 декабря', '2026-11-24', '2026-12-05'],
            ['24.11-05.12', '2026-11-24', '2026-12-05'],
            ['с 12-го по 14-е октября', '2026-10-12', '2026-10-14'],
            ['12–14 октября', '2026-10-12', '2026-10-14'],
            ['12, 13 и 14 октября', '2026-10-12', '2026-10-14'],
            ['24.11.26 — 05.12.26', '2026-11-24', '2026-12-05'],
            ['сегодня–послезавтра', '2026-10-08', '2026-10-10'],
        ];
    }
}
