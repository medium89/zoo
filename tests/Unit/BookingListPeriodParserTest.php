<?php

namespace Tests\Unit;

use App\Services\BookingListPeriodParser;
use Carbon\Carbon;
use Tests\TestCase;

class BookingListPeriodParserTest extends TestCase
{
    public function test_it_parses_tomorrows_booking_question_without_ai(): void
    {
        Carbon::setTestNow('2026-09-02 12:00:00');

        $period = app(BookingListPeriodParser::class)->parse('Завтра какие передержки?');

        $this->assertSame(['start_date' => '2026-09-03', 'end_date' => '2026-09-03'], $period);
    }

    public function test_it_parses_a_russian_date_range_without_ai(): void
    {
        Carbon::setTestNow('2026-09-02 12:00:00');

        $period = app(BookingListPeriodParser::class)->parse('Кто будет с 3 по 5 сентября?');

        $this->assertSame(['start_date' => '2026-09-03', 'end_date' => '2026-09-05'], $period);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
