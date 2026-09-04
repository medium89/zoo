<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Client;
use App\Models\Category;
use App\Models\ServiceOrder;
use App\Services\BoardingPricingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, BoardingPricingService $pricing)
    {
        $period = $request->query('period', 'month');
        if (!in_array($period, ['week', 'month', 'quarter', 'year'], true)) {
            $period = 'month';
        }

        [$start, $end, $periodLabel] = $this->periodRange($period);
        $tariffs = $pricing->tariffs();
        $categories = Category::orderBy('name')->get(['id', 'name']);
        $categoryLabels = $categories->mapWithKeys(fn (Category $category) => [(string) $category->id => $category->name])->all();
        $categoryLabels['unassigned'] = 'Не указана';
        $categoryCounts = array_fill_keys(array_keys($categoryLabels), 0);

        $orders = ServiceOrder::with(['animals.category', 'animals.animal.category', 'animals.services'])
            ->whereNull('archived_at')
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->get();

        $daily = $this->dailyLoad($start, $end, $orders);
        $today = now()->startOfDay();
        $activeToday = ServiceOrder::with(['animals.category', 'animals.animal.category'])
            ->whereNull('archived_at')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->get();

        $activeBySpecies = $categoryCounts;
        foreach ($activeToday as $order) {
            foreach ($order->animals as $animal) {
                $categoryKey = $animal->animal?->category_id ?: $animal->category_id;
                $key = $categoryKey ? (string) $categoryKey : 'unassigned';
                $activeBySpecies[$key] = ($activeBySpecies[$key] ?? 0) + $animal->quantity;
            }
        }

        $species = $categoryCounts;
        $seenAnimals = [];
        foreach ($orders as $order) {
            foreach ($order->animals as $animal) {
                $animalKey = $animal->animal_id ? 'animal-'.$animal->animal_id : 'position-'.$animal->id;
                if (isset($seenAnimals[$animalKey])) {
                    continue;
                }

                $seenAnimals[$animalKey] = true;
                $categoryKey = $animal->animal?->category_id ?: $animal->category_id;
                $key = $categoryKey ? (string) $categoryKey : 'unassigned';
                $species[$key] = ($species[$key] ?? 0) + $animal->quantity;
            }
        }

        $upcomingEnd = $today->copy()->addDays(6);
        $upcoming = ServiceOrder::with(['animals.animal', 'animals.services'])
            ->whereNull('archived_at')
            ->where(function ($query) use ($today, $upcomingEnd) {
                $query->whereBetween('start_date', [$today, $upcomingEnd])
                    ->orWhereBetween('end_date', [$today, $upcomingEnd]);
            })
            ->orderBy('start_date')
            ->get()
            ->flatMap(function (ServiceOrder $order) use ($today, $upcomingEnd) {
                $animals = $order->animals
                    ->map(fn ($animal) => $animal->animal?->name ?: $animal->label ?: 'Питомец')
                    ->unique()->implode(', ');
                $services = $order->animals->flatMap->services
                    ->pluck('service_type')->unique()->implode(', ');

                return collect([
                    $this->upcomingEvent($order->start_date, 'Заезд', $animals, $services, $today, $upcomingEnd),
                    $this->upcomingEvent($order->end_date, 'Выезд', $animals, $services, $today, $upcomingEnd),
                ])->filter();
            })
            ->sortBy([
                ['sort_date', 'asc'],
                ['sort_type', 'asc'],
            ])
            ->take(8)
            ->values();

        return view('admin.dashboard', [
            'period' => $period,
            'periodLabel' => $periodLabel,
            'tariffs' => $tariffs,
            'summary' => [
                'active' => $activeBySpecies,
                'new_clients' => Client::whereBetween('created_at', [$start, $end->copy()->endOfDay()])->count(),
                'working_days' => collect($daily)->filter(fn (array $day) => $day['units'] > 0)->count(),
                'revenue' => array_sum(array_column($daily, 'revenue')),
                'pet_days' => array_sum(array_column($daily, 'units')),
            ],
            'chart' => $this->chart($daily, $period, $start),
            'species' => $species,
            'speciesLabels' => $categoryLabels,
            'upcoming' => $upcoming,
        ]);
    }

    public function updateTariffs(Request $request, BoardingPricingService $pricing)
    {
        $data = $request->validate([
            'tariffs' => 'required|array',
            'tariffs.*' => 'required|array',
            'tariffs.*.*' => 'required|integer|min:0|max:100000',
        ]);

        $pricing->updateTariffs($data['tariffs']);

        return redirect()->route('admin.dashboard', ['period' => $request->input('period', 'month')])
            ->with('success', 'Стандартные тарифы сохранены. Новые записи будут создаваться с этими ценами.');
    }

    private function periodRange(string $period): array
    {
        $now = now();

        return match ($period) {
            'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek(), 'за неделю'],
            'quarter' => [$now->copy()->firstOfQuarter(), $now->copy()->lastOfQuarter(), 'за квартал'],
            'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear(), 'за год'],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth(), 'за месяц'],
        };
    }

    private function dailyLoad(Carbon $start, Carbon $end, $orders): array
    {
        $daily = [];
        for ($day = $start->copy()->startOfDay(); $day->lessThanOrEqualTo($end); $day->addDay()) {
            $daily[$day->toDateString()] = ['date' => $day->toDateString(), 'units' => 0, 'revenue' => 0];
        }

        foreach ($orders as $order) {
            $from = $order->start_date->greaterThan($start) ? $order->start_date->copy() : $start->copy();
            $to = $order->end_date->lessThan($end) ? $order->end_date->copy() : $end->copy();

            for ($day = $from->startOfDay(); $day->lessThanOrEqualTo($to); $day->addDay()) {
                $key = $day->toDateString();
                foreach ($order->animals as $animal) {
                    foreach ($animal->services as $service) {
                        $daily[$key]['revenue'] += $animal->quantity * $service->units_per_day * $service->unit_price;
                        if ($service->service_type === 'передержка') {
                            $daily[$key]['units'] += $animal->quantity;
                        }
                    }
                }
            }
        }

        return array_values($daily);
    }

    private function chart(array $daily, string $period, Carbon $periodStart): array
    {
        $buckets = [];

        foreach ($daily as $day) {
            $date = Carbon::parse($day['date']);
            [$key, $label] = match ($period) {
                'quarter' => [$date->isoWeekYear().'-'.$date->isoWeek(), $date->copy()->startOfWeek()->locale('ru')->translatedFormat('j M')],
                'year' => [$date->format('Y-m'), $date->locale('ru')->translatedFormat('M')],
                default => [$date->toDateString(), $period === 'week' ? $date->locale('ru')->translatedFormat('D') : $date->format('j')],
            };

            $buckets[$key] ??= ['label' => $label, 'units' => 0, 'revenue' => 0];
            $buckets[$key]['units'] += $day['units'];
            $buckets[$key]['revenue'] += $day['revenue'];
        }

        return array_values($buckets);
    }

    private function upcomingEvent(
        Carbon $date,
        string $type,
        string $animals,
        string $services,
        Carbon $from,
        Carbon $to,
    ): ?array {
        if (!$date->betweenIncluded($from, $to)) {
            return null;
        }

        return [
            'date' => $date->copy()->locale('ru')->translatedFormat('j F'),
            'sort_date' => $date->toDateString(),
            'sort_type' => $type === 'Заезд' ? 0 : 1,
            'type' => $type,
            'name' => $animals,
            'service' => $services ?: 'Услуга не указана',
        ];
    }

}
