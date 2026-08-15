<?php

namespace Tests\Unit;

use App\Services\BoardingTaskInstructionParser;
use PHPUnit\Framework\TestCase;

class BoardingTaskInstructionParserTest extends TestCase
{
    public function test_it_turns_feeding_instructions_into_a_daily_schedule(): void
    {
        $tasks = (new BoardingTaskInstructionParser())->parse(<<<'TEXT'
Кормление
Утром, примерно с 8:00 до 10:00:
Дать влажный корм из баночки.
Днём:
Подкормить ещё один раз.
Ближе к вечеру, с 15:00 до 18:00:
Дать влажный корм из пакетика.
Обязательное вечернее кормление, с 21:00 до 23:00:
Дать геркулесовую кашу с печенью.
На ночь:
Можно оставить влажный корм.
TEXT);

        $this->assertCount(5, $tasks);
        $this->assertSame(['09:00', '13:00', '16:30', '22:00', '23:00'], array_column($tasks, 'scheduled_time'));
        $this->assertSame('Кормление · утром', $tasks[0]['title']);
        $this->assertSame('Дать влажный корм из баночки.', $tasks[0]['instructions']);
    }
}
