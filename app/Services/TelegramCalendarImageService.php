<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use RuntimeException;

class TelegramCalendarImageService
{
    private const FONT = '/usr/share/fonts/dejavu-sans-fonts/DejaVuSans.ttf';

    /** @param Collection<int, \App\Models\Boarding|\App\Models\ServiceOrder> $bookings */
    public function render(Carbon $month, Collection $bookings): string
    {
        if (!extension_loaded('gd') || !is_file(self::FONT)) {
            throw new RuntimeException('Генерация изображения календаря недоступна.');
        }

        $month = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();
        $width = 1200;
        $height = 980;
        $margin = 54;
        $headerHeight = 170;
        $cellWidth = (int) floor(($width - $margin * 2) / 7);
        $cellHeight = 112;

        $image = imagecreatetruecolor($width, $height);
        imagealphablending($image, true);
        $background = imagecolorallocate($image, 247, 248, 250);
        $white = imagecolorallocate($image, 255, 255, 255);
        $text = imagecolorallocate($image, 31, 41, 55);
        $muted = imagecolorallocate($image, 100, 116, 139);
        $grid = imagecolorallocate($image, 226, 232, 240);
        $orange = imagecolorallocate($image, 234, 138, 38);
        $orangeSoft = imagecolorallocate($image, 255, 239, 216);
        imagefill($image, 0, 0, $background);

        $months = [
            1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель',
            5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август',
            9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь',
        ];
        $this->text($image, 38, $margin, 68, $text, $months[$month->month].' '.$month->year);
        $this->text($image, 17, $margin, 105, $muted, 'Календарь записей · занятые дни выделены оранжевым');

        $weekDays = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
        foreach ($weekDays as $index => $weekDay) {
            $x = $margin + $index * $cellWidth + 15;
            $this->text($image, 16, $x, $headerHeight - 15, $muted, $weekDay);
        }

        $counts = $this->bookingCounts($bookings, $month, $monthEnd);
        $firstWeekday = $month->dayOfWeekIso - 1;
        for ($day = 1; $day <= $month->daysInMonth; $day++) {
            $position = $firstWeekday + $day - 1;
            $column = $position % 7;
            $row = intdiv($position, 7);
            $x = $margin + $column * $cellWidth;
            $y = $headerHeight + $row * $cellHeight;
            $date = $month->copy()->day($day)->toDateString();
            $count = $counts[$date] ?? 0;

            imagefilledrectangle($image, $x, $y, $x + $cellWidth - 8, $y + $cellHeight - 8, $count ? $orangeSoft : $white);
            imagerectangle($image, $x, $y, $x + $cellWidth - 8, $y + $cellHeight - 8, $count ? $orange : $grid);
            $this->text($image, 23, $x + 15, $y + 36, $count ? $orange : $text, (string) $day);

            if ($count) {
                imagefilledellipse($image, $x + $cellWidth - 34, $y + 28, 23, 23, $orange);
                $this->text($image, 13, $x + $cellWidth - 38, $y + 33, $white, (string) $count);
                $label = $count === 1 ? '1 питомец' : $count.' питомца';
                $this->text($image, 14, $x + 15, $y + 76, $orange, $label);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'zooland-calendar-');
        if ($path === false) {
            imagedestroy($image);
            throw new RuntimeException('Не удалось подготовить файл календаря.');
        }

        imagepng($image, $path, 6);
        imagedestroy($image);

        return $path;
    }

    /** @param Collection<int, \App\Models\Boarding|\App\Models\ServiceOrder> $bookings */
    private function bookingCounts(Collection $bookings, Carbon $month, Carbon $monthEnd): array
    {
        $counts = [];
        foreach ($bookings as $booking) {
            $start = $booking->start_date->copy()->max($month);
            $end = $booking->end_date->copy()->min($monthEnd);
            for ($day = $start; $day->lte($end); $day->addDay()) {
                $date = $day->toDateString();
                $counts[$date] = ($counts[$date] ?? 0) + max(1, (int) ($booking->calendar_quantity ?? 1));
            }
        }

        return $counts;
    }

    private function text($image, int $size, int $x, int $y, int $color, string $value): void
    {
        imagefttext($image, $size, 0, $x, $y, $color, self::FONT, $value);
    }
}
