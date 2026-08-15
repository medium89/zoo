<?php

namespace App\Services;

class BoardingTaskInstructionParser
{
    /**
     * @return array<int, array{title: string, scheduled_time: string, instructions: string}>
     */
    public function parse(string $text): array
    {
        $subjectLines = [];
        $tasks = [];
        $current = null;

        foreach (preg_split('/\R/u', trim($text)) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if ($period = $this->periodFromLine($line)) {
                if ($current !== null) {
                    $tasks[] = $this->finalize($current, $subjectLines);
                }

                $current = [
                    'label' => $period['label'],
                    'scheduled_time' => $period['scheduled_time'],
                    'instructions' => [],
                ];
                continue;
            }

            if ($current === null) {
                $subjectLines[] = $line;
            } else {
                $current['instructions'][] = $line;
            }
        }

        if ($current !== null) {
            $tasks[] = $this->finalize($current, $subjectLines);
        }

        $tasks = array_values(array_filter($tasks, fn (array $task) => $task['instructions'] !== ''));

        return count($tasks) >= 2 ? $tasks : [];
    }

    /** @return array{label: string, scheduled_time: string}|null */
    private function periodFromLine(string $line): ?array
    {
        $normalized = mb_strtolower(trim($line), 'UTF-8');

        if (str_starts_with($normalized, 'утром')) {
            return ['label' => 'утром', 'scheduled_time' => $this->timeFromRange($normalized, '09:00')];
        }

        if (str_starts_with($normalized, 'днём') || str_starts_with($normalized, 'днем')) {
            return ['label' => 'днём', 'scheduled_time' => $this->timeFromRange($normalized, '13:00')];
        }

        if (str_starts_with($normalized, 'ближе к вечеру')) {
            return ['label' => 'ближе к вечеру', 'scheduled_time' => $this->timeFromRange($normalized, '16:30')];
        }

        if (str_starts_with($normalized, 'обязательное вечернее') || str_starts_with($normalized, 'вечером')) {
            return ['label' => 'вечером', 'scheduled_time' => $this->timeFromRange($normalized, '22:00')];
        }

        if (str_starts_with($normalized, 'на ночь')) {
            return ['label' => 'на ночь', 'scheduled_time' => $this->timeFromRange($normalized, '23:00')];
        }

        return null;
    }

    private function timeFromRange(string $text, string $fallback): string
    {
        if (!preg_match('/с\s*(\d{1,2})(?::(\d{2}))?\s*до\s*(\d{1,2})(?::(\d{2}))?/u', $text, $matches)) {
            return $fallback;
        }

        $start = ((int) $matches[1]) * 60 + (int) ($matches[2] ?? 0);
        $end = ((int) $matches[3]) * 60 + (int) ($matches[4] ?? 0);
        if ($end <= $start) {
            return $fallback;
        }

        $middle = (int) floor(($start + $end) / 2 / 5) * 5;

        return sprintf('%02d:%02d', intdiv($middle, 60), $middle % 60);
    }

    /** @param array{label: string, scheduled_time: string, instructions: array<int, string>} $task */
    private function finalize(array $task, array $subjectLines): array
    {
        $subject = trim(implode(' ', $subjectLines));
        $subject = $subject !== '' ? $subject : 'Уход';

        return [
            'title' => $subject.' · '.$task['label'],
            'scheduled_time' => $task['scheduled_time'],
            'instructions' => trim(implode("\n", $task['instructions'])),
        ];
    }
}
