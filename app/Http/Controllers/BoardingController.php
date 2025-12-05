<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Boarding;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BoardingController extends Controller
{
    public function index(Request $request)
    {
        $year = (int)($request->query('year', 2026));
        $this->hydrateAnimalsFromBoardings();
        $entries = $this->entriesForYear($year);
        $latest = Boarding::whereNull('archived_at')->orderByDesc('created_at')->take(20)->get();
        $animals = Animal::orderBy('name')->get();

        return view('admin.boarding.index', [
            'year' => $year,
            'entries' => $entries,
            'latest' => $latest,
            'animals' => $animals,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'service_type' => 'required|string|in:передержка,выгул',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $entry = Boarding::create($data);
        $this->syncAnimal($data);

        return back()->with('success', 'Запись добавлена');
    }

    public function update(Request $request, Boarding $boarding)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'service_type' => 'required|string|in:передержка,выгул',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $boarding->update($data);
        $this->syncAnimal($data);

        return back()->with('success', 'Запись обновлена');
    }

    public function data(Request $request)
    {
        $year = (int)($request->query('year', Carbon::now()->year));
        return response()->json([
            'entries' => $this->entriesForYear($year),
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
        $archived = Boarding::whereNotNull('archived_at')
            ->orderByDesc('archived_at')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.boarding.archive', compact('archived'));
    }

    public function animals()
    {
        $this->hydrateAnimalsFromBoardings();
        $animals = Animal::withCount(['boardings'])
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
            fputcsv($out, ['ID','Кличка','Описание','Тип услуги','Дата начала','Дата окончания']);
            foreach ($entries as $row) {
                fputcsv($out, [$row['id'], $row['name'], $row['description'], $row['service_type'], $row['start_date'], $row['end_date']]);
            }
            fclose($out);
        }, 'boarding.csv', $headers);
    }

    private function entriesForYear(int $year)
    {
        $start = Carbon::create($year, 1, 1);
        $end = Carbon::create($year, 12, 31);

        return Boarding::whereNull('archived_at')
            ->where(function($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                  ->orWhereBetween('end_date', [$start, $end])
                  ->orWhere(function($sub) use ($start, $end) {
                      $sub->where('start_date', '<=', $start)->where('end_date', '>=', $end);
                  });
            })
            ->orderBy('start_date')
            ->get()
            ->map(function($item){
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'service_type' => $item->service_type,
                    'start_date' => $item->start_date->toDateString(),
                    'end_date' => $item->end_date->toDateString(),
                ];
            });
    }

    private function syncAnimal(array $data): void
    {
        if (($data['service_type'] ?? null) !== 'передержка') {
            return;
        }

        $animal = Animal::firstOrNew(['name' => $data['name']]);

        if (!empty($data['description'])) {
            $animal->description = $data['description'];
        }

        if (!$animal->exists) {
            $animal->save();
        } elseif ($animal->isDirty()) {
            $animal->save();
        }
    }

    private function hydrateAnimalsFromBoardings(): void
    {
        $existingNames = Animal::pluck('name')->all();
        $missing = Boarding::where('service_type', 'передержка')
            ->whereNotIn('name', $existingNames)
            ->orderByDesc('created_at')
            ->get()
            ->unique('name');

        foreach ($missing as $boarding) {
            Animal::create([
                'name' => $boarding->name,
                'description' => $boarding->description,
            ]);
        }
    }
}
