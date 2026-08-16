<?php

namespace App\Http\Controllers;

use App\Models\Boarding;
use App\Models\Client;
use App\Models\Category;
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

        $boardings = Boarding::with('animal.category')
            ->whereNull('archived_at')
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->get();

        $daily = $this->dailyLoad($start, $end, $boardings, $pricing, $tariffs);
        $today = now()->startOfDay();
        $activeToday = Boarding::with('animal.category')
            ->whereNull('archived_at')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->get();

        $activeBySpecies = $categoryCounts;
        foreach ($activeToday as $boarding) {
            $categoryKey = $boarding->animal?->category_id ? (string) $boarding->animal->category_id : 'unassigned';
            $activeBySpecies[$categoryKey]++;
        }

        $species = $categoryCounts;
        $seenAnimals = [];
        foreach ($boardings as $boarding) {
            $animalKey = $boarding->animal_id ? 'animal-'.$boarding->animal_id : 'boarding-'.$boarding->id;
            if (isset($seenAnimals[$animalKey])) {
                continue;
            }

            $seenAnimals[$animalKey] = true;
            $categoryKey = $boarding->animal?->category_id ? (string) $boarding->animal->category_id : 'unassigned';
            $species[$categoryKey]++;
        }

        $upcoming = Boarding::with('animal.category')
            ->whereNull('archived_at')
            ->where(function ($query) use ($today) {
                $query->whereBetween('start_date', [$today, $today->copy()->addDays(7)])
                    ->orWhereBetween('end_date', [$today, $today->copy()->addDays(7)]);
            })
            ->orderBy('start_date')
            ->limit(8)
            ->get()
            ->map(function (Boarding $boarding) use ($today): array {
                $isArrival = $boarding->start_date->greaterThanOrEqualTo($today);

                return [
                    'date' => ($isArrival ? $boarding->start_date : $boarding->end_date)->locale('ru')->translatedFormat('j F'),
                    'type' => $isArrival ? 'Заезд' : 'Выезд',
                    'name' => $boarding->animal?->name ?: $boarding->name,
                    'service' => $boarding->service_type,
                ];
            });

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

    private function dailyLoad(Carbon $start, Carbon $end, $boardings, BoardingPricingService $pricing, array $tariffs): array
    {
        $daily = [];
        for ($day = $start->copy()->startOfDay(); $day->lessThanOrEqualTo($end); $day->addDay()) {
            $daily[$day->toDateString()] = ['date' => $day->toDateString(), 'units' => 0, 'revenue' => 0];
        }

        foreach ($boardings as $boarding) {
            $from = $boarding->start_date->greaterThan($start) ? $boarding->start_date->copy() : $start->copy();
            $to = $boarding->end_date->lessThan($end) ? $boarding->end_date->copy() : $end->copy();
            $rate = $pricing->rateFor($boarding, $tariffs);
            $units = $pricing->unitsPerDay($boarding);

            for ($day = $from->startOfDay(); $day->lessThanOrEqualTo($to); $day->addDay()) {
                $key = $day->toDateString();
                $daily[$key]['units'] += $units;
                $daily[$key]['revenue'] += $rate * $units;
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

}
