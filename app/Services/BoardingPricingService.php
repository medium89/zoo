<?php

namespace App\Services;

use App\Models\Boarding;
use App\Models\ServiceTariff;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BoardingPricingService
{
    public const SERVICE_TYPES = ['передержка', 'выгул', 'уход'];

    public const ANIMAL_GROUPS = ['cat', 'dog_small', 'dog_large', 'small_pet', 'other'];

    public function defaultTariffs(): array
    {
        return [
            'передержка' => ['cat' => 500, 'dog_small' => 800, 'dog_large' => 1000, 'small_pet' => 300, 'other' => 500],
            'выгул' => ['cat' => 500, 'dog_small' => 450, 'dog_large' => 500, 'small_pet' => 500, 'other' => 500],
            'уход' => ['cat' => 450, 'dog_small' => 450, 'dog_large' => 450, 'small_pet' => 350, 'other' => 500],
        ];
    }

    public function tariffs(): array
    {
        $tariffs = $this->defaultTariffs();

        ServiceTariff::query()->get()->each(function (ServiceTariff $tariff) use (&$tariffs): void {
            if (isset($tariffs[$tariff->service_type][$tariff->animal_group])) {
                $tariffs[$tariff->service_type][$tariff->animal_group] = $tariff->amount;
            }
        });

        return $tariffs;
    }

    public function updateTariffs(array $tariffs): void
    {
        foreach ($this->defaultTariffs() as $serviceType => $groups) {
            foreach (array_keys($groups) as $animalGroup) {
                ServiceTariff::updateOrCreate(
                    ['service_type' => $serviceType, 'animal_group' => $animalGroup],
                    ['amount' => (int) data_get($tariffs, "{$serviceType}.{$animalGroup}", $groups[$animalGroup])]
                );
            }
        }
    }

    public function animalGroup(?string $species, ?string $dogSize = null): string
    {
        return match (mb_strtolower(trim((string) $species))) {
            'кот', 'кошка', 'котёнок', 'котенок', 'кошки' => 'cat',
            'собака', 'пёс', 'пес', 'щенок', 'собаки' => $dogSize === 'small' ? 'dog_small' : 'dog_large',
            'грызун', 'грызуны', 'хомяк', 'крыса', 'морская свинка', 'кролик', 'птица', 'птицы', 'попугай', 'рыбка', 'рыбки', 'рыба' => 'small_pet',
            default => 'other',
        };
    }

    public function defaultRate(string $serviceType, ?string $species, ?string $dogSize = null, ?array $tariffs = null): int
    {
        $tariffs ??= $this->tariffs();
        $serviceType = in_array($serviceType, self::SERVICE_TYPES, true) ? $serviceType : 'передержка';

        return (int) ($tariffs[$serviceType][$this->animalGroup($species, $dogSize)] ?? $tariffs[$serviceType]['other']);
    }

    public function rateFor(Boarding $boarding, ?array $tariffs = null): int
    {
        if ($boarding->unit_price !== null) {
            return (int) $boarding->unit_price;
        }

        return $this->defaultRate($boarding->service_type, $boarding->animal?->species, $boarding->animal?->dog_size, $tariffs);
    }

    public function unitsPerDay(Boarding $boarding): int
    {
        return max(1, (int) ($boarding->units_per_day ?: 1));
    }

    public function daysBetween(Carbon|string $start, Carbon|string $end): int
    {
        $start = Carbon::parse($start)->startOfDay();
        $end = Carbon::parse($end)->startOfDay();

        return $start->greaterThan($end) ? 0 : $start->diffInDays($end) + 1;
    }

    public function totalForRange(Boarding $boarding, Carbon|string $start, Carbon|string $end, ?array $tariffs = null): int
    {
        return $this->rateFor($boarding, $tariffs)
            * $this->unitsPerDay($boarding)
            * $this->daysBetween($start, $end);
    }

    public function groupLabel(string $group): string
    {
        return ['cat' => 'Кошки', 'dog' => 'Собаки', 'other' => 'Другие'][$group] ?? 'Другие';
    }
}
