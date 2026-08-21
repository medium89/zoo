<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Boarding;
use App\Models\Client;
use App\Models\Category;
use App\Models\ServiceOrder;
use App\Services\BoardingPricingService;
use App\Services\ExpiredBoardingArchiver;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BoardingController extends Controller
{
    public function index(Request $request, BoardingPricingService $pricing, ExpiredBoardingArchiver $archiver)
    {
        $archiver->archive();
        $yearParam = $request->query('year', 'all');
        $year = $yearParam === 'all' ? 'all' : (int)$yearParam;
        $range = $this->activeRange();
        $this->hydrateAnimalsFromBoardings();
        $entries = $year === 'all' ? $this->entriesAllActive() : $this->entriesForYear($year);
        $latest = Boarding::with(['animal.photos', 'animal.client', 'animal.category', 'client'])
            ->whereNull('archived_at')
            ->orderByDesc('created_at')
            ->take(20)
            ->get();
        $serviceOrders = ServiceOrder::with(['client', 'animals.category', 'animals.animal'])
            ->whereNull('archived_at')
            ->orderBy('start_date')
            ->take(6)
            ->get();
        $animals = Animal::with(['client', 'photos', 'category'])->orderBy('name')->get();
        $clients = Client::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('admin.boarding.index', [
            'year' => $year,
            'entries' => $entries,
            'latest' => $latest,
            'serviceOrders' => $serviceOrders,
            'animals' => $animals,
            'clients' => $clients,
            'categories' => $categories,
            'minYear' => $range['min'],
            'maxYear' => $range['max'],
            'tariffs' => $pricing->tariffs(),
        ]);
    }

    public function store(Request $request, BoardingPricingService $pricing)
    {
        $data = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'animal_id' => 'nullable|exists:animals,id',
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string|max:255',
            'service_type' => 'required|string|in:передержка,выгул,уход',
            'units_per_day' => 'nullable|integer|min:1|max:24',
            'unit_price' => 'nullable|integer|min:0|max:100000',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'note' => 'nullable|string',
        ]);

        $animal = $this->syncAnimal($data);
        unset($data['category_id']);
        $data['animal_id'] = $animal?->id;
        $data['client_id'] = $data['client_id'] ?? $animal?->client_id;
        $data['units_per_day'] = (int) ($data['units_per_day'] ?? 1);
        $data['unit_price'] = $data['unit_price'] ?? $pricing->defaultRate($data['service_type'], $animal?->species, $animal?->dog_size);

        $boarding = Boarding::create($data);
        $this->syncServiceOrderFromBoarding($boarding);

        return back()->with('success', 'Запись добавлена');
    }

    public function update(Request $request, Boarding $boarding, BoardingPricingService $pricing)
    {
        $data = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'animal_id' => 'nullable|exists:animals,id',
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string|max:255',
            'service_type' => 'required|string|in:передержка,выгул,уход',
            'units_per_day' => 'nullable|integer|min:1|max:24',
            'unit_price' => 'nullable|integer|min:0|max:100000',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'note' => 'nullable|string',
        ]);

        $animal = $this->syncAnimal($data);
        unset($data['category_id']);
        $data['animal_id'] = $animal?->id;
        $data['client_id'] = $data['client_id'] ?? $animal?->client_id;
        $data['units_per_day'] = (int) ($data['units_per_day'] ?? $boarding->units_per_day ?? 1);
        $data['unit_price'] = $data['unit_price'] ?? $boarding->unit_price ?? $pricing->defaultRate($data['service_type'], $animal?->species, $animal?->dog_size);

        $boarding->update($data);
        $this->syncServiceOrderFromBoarding($boarding->fresh());

        return back()->with('success', 'Запись обновлена');
    }

    public function data(Request $request)
    {
        $yearParam = $request->query('year', Carbon::now()->year);
        $range = $this->activeRange();
        if ($yearParam === 'all') {
            return response()->json([
                'entries' => $this->entriesAllActive(),
                'minYear' => $range['min'],
                'maxYear' => $range['max'],
            ]);
        }
        $year = (int)$yearParam;
        return response()->json([
            'entries' => $this->entriesForYear($year),
            'minYear' => $range['min'],
            'maxYear' => $range['max'],
        ]);
    }

    public function publicCalendar()
    {
        $now = Carbon::now();
        $today = $now->copy()->startOfDay();
        $year = $now->year;
        $entries = $this->entriesForYear($year)
            ->filter(fn (array $entry): bool => Carbon::parse($entry['end_date'])->greaterThanOrEqualTo($today))
            ->map(function (array $entry) use ($today): array {
                $visibleStart = Carbon::parse($entry['start_date']);
                if ($visibleStart->lessThan($today)) {
                    $visibleStart = $today->copy();
                }

                $visibleEnd = Carbon::parse($entry['end_date']);
                $entry['start_date'] = $visibleStart->toDateString();
                $entry['end_date'] = $visibleEnd->toDateString();
                $entry['start_date_label'] = $visibleStart->locale('ru')->translatedFormat('j F');
                $entry['end_date_label'] = $visibleEnd->locale('ru')->translatedFormat('j F');

                return $entry;
            })
            ->values();

        return view('calendar.index', [
            'year' => $year,
            'entries' => $entries,
            'currentMonth' => $now->month - 1,
            'today' => $today->toDateString(),
        ]);
    }

    public function archive(Boarding $boarding)
    {
        $boarding->archived_at = now();
        $boarding->save();
        $this->syncServiceOrderFromBoarding($boarding);

        return back()->with('success', 'Запись перенесена в архив');
    }

    public function restore(Boarding $boarding)
    {
        $boarding->archived_at = null;
        $boarding->save();
        $this->syncServiceOrderFromBoarding($boarding);

        return back()->with('success', 'Запись восстановлена');
    }

    public function destroy(Boarding $boarding)
    {
        ServiceOrder::where('legacy_boarding_id', $boarding->id)->delete();
        $boarding->delete();

        return back()->with('success', 'Запись удалена');
    }

    public function archiveIndex()
    {
        $archived = Boarding::with(['animal.photos', 'animal.client', 'animal.category', 'client'])
            ->whereNotNull('archived_at')
            ->orderByDesc('archived_at')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.boarding.archive', compact('archived'));
    }

    public function animals()
    {
        $this->hydrateAnimalsFromBoardings();
        $animals = Animal::with(['client', 'photos', 'category'])
            ->withCount(['boardings'])
            ->with(['boardings' => function($query) {
                $query->latest('start_date')->limit(1);
            }])
            ->orderBy('name')
            ->get();

        return view('admin.boarding.animals', compact('animals'));
    }

    public function export(Request $request): StreamedResponse
    {
        $year = (int)($request->query('year', Carbon::now()->year));
        $entries = $this->entriesForYear($year);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="boarding_'.$year.'.csv"',
        ];

        return response()->streamDownload(function() use ($entries) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID','Кличка','Вид','Хозяин','Описание','Тип услуги','Дата начала','Дата окончания']);
            foreach ($entries as $row) {
                fputcsv($out, [$row['id'], $row['name'], $row['species'], $row['client_name'], $row['description'], $row['service_type'], $row['start_date'], $row['end_date']]);
            }
            fclose($out);
        }, 'boarding.csv', $headers);
    }

    private function entriesForYear(int $year)
    {
        $start = Carbon::create($year, 1, 1);
        $end = Carbon::create($year, 12, 31);

        return $this->serviceOrderEntries($start, $end);
    }

    private function entriesAllActive()
    {
        return $this->serviceOrderEntries();
    }

    private function activeRange(): array
    {
        $minStart = collect([
            ServiceOrder::whereNull('archived_at')->min('start_date'),
        ])->filter()->min();
        $maxEnd = collect([
            ServiceOrder::whereNull('archived_at')->max('end_date'),
        ])->filter()->max();
        $nowYear = Carbon::now()->year;

        $min = $minStart ? Carbon::parse($minStart)->year : $nowYear;
        $max = $maxEnd ? Carbon::parse($maxEnd)->year : $nowYear;

        return ['min' => $min, 'max' => $max];
    }

    private function syncAnimal(array $data): ?Animal
    {
        $category = !empty($data['category_id']) ? Category::find($data['category_id']) : null;

        if (!empty($data['animal_id'])) {
            $animal = Animal::find($data['animal_id']);

            if ($animal) {
                if (!empty($data['client_id']) && !$animal->client_id) {
                    $animal->client_id = $data['client_id'];
                }

                if ($category) {
                    $animal->category_id = $category->id;
                    $animal->species = $category->name;
                }

                if ($animal->isDirty()) {
                    $animal->save();
                }
            }

            return $animal;
        }

        $animal = Animal::whereRaw('LOWER(name) = ?', [mb_strtolower($data['name'])])
            ->when($data['client_id'] ?? null, fn ($query, $clientId) => $query->where('client_id', $clientId))
            ->first();

        if (!$animal) {
            $animal = new Animal(['name' => $data['name']]);
        }

        if (!empty($data['client_id'])) {
            $animal->client_id = $data['client_id'];
        }

        if (!empty($data['description'])) {
            $animal->description = $data['description'];
        }

        if ($category) {
            $animal->category_id = $category->id;
            $animal->species = $category->name;
        }

        if (!$animal->exists) {
            $animal->save();
        } elseif ($animal->isDirty()) {
            $animal->save();
        }

        return $animal;
    }

    private function hydrateAnimalsFromBoardings(): void
    {
        $missing = Boarding::whereNull('animal_id')
            ->orderByDesc('created_at')
            ->get();

        foreach ($missing as $boarding) {
            $animal = Animal::whereRaw('LOWER(name) = ?', [mb_strtolower($boarding->name)])->first();

            if (!$animal) {
                $animal = Animal::create([
                    'name' => $boarding->name,
                    'description' => $boarding->description,
                ]);
            }

            $boarding->animal_id = $animal->id;
            $boarding->client_id = $animal->client_id;
            $boarding->save();
        }
    }

    private function entryPayload(Boarding $item): array
    {
        $animal = $item->animal;
        $client = $item->client ?: $animal?->client;

        return [
            'id' => $item->id,
            'entry_type' => 'boarding',
            'name' => $animal?->name ?: $item->name,
            'species' => $animal?->category?->name ?: $animal?->species,
            'client_name' => $client?->name,
            'description' => $item->description ?: $animal?->description,
            'photo_url' => $animal?->photos->first()
                ? url(\Illuminate\Support\Facades\Storage::url($animal->photos->first()->path))
                : null,
            'service_type' => $item->service_type,
            'start_date' => $item->start_date->toDateString(),
            'end_date' => $item->end_date->toDateString(),
        ];
    }

    private function syncServiceOrderFromBoarding(Boarding $boarding): void
    {
        $order = ServiceOrder::updateOrCreate(
            ['legacy_boarding_id' => $boarding->id],
            [
                'client_id' => $boarding->client_id,
                'service_type' => $boarding->service_type,
                'units_per_day' => $boarding->units_per_day,
                'daily_price' => $boarding->unit_price * $boarding->units_per_day,
                'start_date' => $boarding->start_date,
                'end_date' => $boarding->end_date,
                'note' => $boarding->note,
                'source' => $boarding->source,
                'status' => $boarding->status,
                'confirmed_at' => $boarding->confirmed_at,
                'archived_at' => $boarding->archived_at,
            ]
        );
        $order->animals()->delete();
        $orderAnimal = $order->animals()->create(['animal_id' => $boarding->animal_id, 'category_id' => $boarding->animal?->category_id, 'label' => $boarding->animal?->name ?: $boarding->name, 'quantity' => 1, 'note' => $boarding->description]);
        $orderAnimal->services()->create(['service_order_id' => $order->id, 'service_type' => $boarding->service_type, 'units_per_day' => $boarding->units_per_day, 'unit_price' => $boarding->unit_price]);
    }

    private function serviceOrderEntries(?Carbon $start = null, ?Carbon $end = null)
    {
        return ServiceOrder::whereNull('archived_at')
            ->with(['animals.category', 'animals.animal.photos', 'client', 'services'])
            ->when($start && $end, function ($query) use ($start, $end) {
                $query->where(function ($sub) use ($start, $end) {
                    $sub->whereBetween('start_date', [$start, $end])
                        ->orWhereBetween('end_date', [$start, $end])
                        ->orWhere(fn ($range) => $range->where('start_date', '<=', $start)->where('end_date', '>=', $end));
                });
            })
            ->orderBy('start_date')
            ->get()
            ->map(fn (ServiceOrder $order) => $this->serviceOrderEntryPayload($order));
    }

    private function serviceOrderEntryPayload(ServiceOrder $order): array
    {
        $animals = $order->animals
            ->map(fn ($animal) => $animal->animal?->name ?: $animal->label ?: trim($animal->quantity.' '.mb_strtolower((string) $animal->category?->name)))
            ->filter()
            ->implode(', ');

        return [
            'id' => 'order-'.$order->id,
            'entry_type' => 'service_order',
            'name' => $animals ?: 'Питомцы без кличек',
            'species' => $order->animals->first()?->animal?->category?->name ?: $order->animals->first()?->category?->name,
            'client_name' => $order->client?->name,
            'description' => $order->note ?: 'Клички питомцев пока не указаны',
            'photo_url' => $order->animals->first()?->animal?->photos->first() ? url(\Illuminate\Support\Facades\Storage::url($order->animals->first()->animal->photos->first()->path)) : null,
            'service_type' => $order->services->pluck('service_type')->map(fn ($type) => ucfirst($type))->implode(', ') ?: $order->service_type,
            'start_date' => $order->start_date->toDateString(),
            'end_date' => $order->end_date->toDateString(),
        ];
    }
}
