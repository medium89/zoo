<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Boarding;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BoardingController extends Controller
{
    public function index(Request $request)
    {
        $yearParam = $request->query('year', 'all');
        $year = $yearParam === 'all' ? 'all' : (int)$yearParam;
        $range = $this->activeRange();
        $this->hydrateAnimalsFromBoardings();
        $entries = $year === 'all' ? $this->entriesAllActive() : $this->entriesForYear($year);
        $latest = Boarding::with(['animal.photos', 'animal.client', 'client'])
            ->whereNull('archived_at')
            ->orderByDesc('created_at')
            ->take(20)
            ->get();
        $animals = Animal::with(['client', 'photos'])->orderBy('name')->get();
        $clients = Client::orderBy('name')->get();

        return view('admin.boarding.index', [
            'year' => $year,
            'entries' => $entries,
            'latest' => $latest,
            'animals' => $animals,
            'clients' => $clients,
            'minYear' => $range['min'],
            'maxYear' => $range['max'],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'animal_id' => 'nullable|exists:animals,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'service_type' => 'required|string|in:передержка,выгул,уход',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'note' => 'nullable|string',
        ]);

        $animal = $this->syncAnimal($data);
        $data['animal_id'] = $animal?->id;
        $data['client_id'] = $data['client_id'] ?? $animal?->client_id;

        Boarding::create($data);

        return back()->with('success', 'Запись добавлена');
    }

    public function update(Request $request, Boarding $boarding)
    {
        $data = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'animal_id' => 'nullable|exists:animals,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'service_type' => 'required|string|in:передержка,выгул,уход',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'note' => 'nullable|string',
        ]);

        $animal = $this->syncAnimal($data);
        $data['animal_id'] = $animal?->id;
        $data['client_id'] = $data['client_id'] ?? $animal?->client_id;

        $boarding->update($data);

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
        $year = $now->year;
        $currentMonthStart = $now->copy()->startOfMonth();
        $entries = $this->entriesForYear($year)
            ->filter(fn (array $entry): bool => Carbon::parse($entry['end_date'])->greaterThanOrEqualTo($currentMonthStart))
            ->values();

        return view('calendar.index', [
            'year' => $year,
            'entries' => $entries,
            'currentMonth' => $now->month - 1,
        ]);
    }

    public function archive(Boarding $boarding)
    {
        $boarding->archived_at = now();
        $boarding->save();

        return back()->with('success', 'Запись перенесена в архив');
    }

    public function restore(Boarding $boarding)
    {
        $boarding->archived_at = null;
        $boarding->save();

        return back()->with('success', 'Запись восстановлена');
    }

    public function destroy(Boarding $boarding)
    {
        $boarding->delete();

        return back()->with('success', 'Запись удалена');
    }

    public function archiveIndex()
    {
        $archived = Boarding::with(['animal.photos', 'animal.client', 'client'])
            ->whereNotNull('archived_at')
            ->orderByDesc('archived_at')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.boarding.archive', compact('archived'));
    }

    public function animals()
    {
        $this->hydrateAnimalsFromBoardings();
        $animals = Animal::with(['client', 'photos'])
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

        return Boarding::whereNull('archived_at')
            ->with(['animal.photos', 'animal.client', 'client'])
            ->where(function($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                  ->orWhereBetween('end_date', [$start, $end])
                  ->orWhere(function($sub) use ($start, $end) {
                      $sub->where('start_date', '<=', $start)->where('end_date', '>=', $end);
                  });
            })
            ->orderBy('start_date')
            ->get()
            ->map(fn ($item) => $this->entryPayload($item));
    }

    private function entriesAllActive()
    {
        return Boarding::whereNull('archived_at')
            ->with(['animal.photos', 'animal.client', 'client'])
            ->orderBy('start_date')
            ->get()
            ->map(fn ($item) => $this->entryPayload($item));
    }

    private function activeRange(): array
    {
        $minStart = Boarding::whereNull('archived_at')->min('start_date');
        $maxEnd = Boarding::whereNull('archived_at')->max('end_date');
        $nowYear = Carbon::now()->year;

        $min = $minStart ? Carbon::parse($minStart)->year : $nowYear;
        $max = $maxEnd ? Carbon::parse($maxEnd)->year : $nowYear;

        return ['min' => $min, 'max' => $max];
    }

    private function syncAnimal(array $data): ?Animal
    {
        if (!empty($data['animal_id'])) {
            $animal = Animal::find($data['animal_id']);

            if ($animal && !empty($data['client_id']) && !$animal->client_id) {
                $animal->client_id = $data['client_id'];
                $animal->save();
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
            'name' => $animal?->name ?: $item->name,
            'species' => $animal?->species,
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
}
