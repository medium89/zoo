<?php

namespace App\Http\Controllers;

use App\Models\Boarding;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BoardingController extends Controller
{
    public function index(Request $request)
    {
        $year = (int)($request->query('year', 2026));
        $entries = $this->entriesForYear($year);
        $latest = Boarding::orderByDesc('created_at')->take(20)->get();

        return view('admin.boarding.index', [
            'year' => $year,
            'entries' => $entries,
            'latest' => $latest,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        Boarding::create($data);

        return back()->with('success', 'Запись добавлена');
    }

    public function data(Request $request)
    {
        $year = (int)($request->query('year', Carbon::now()->year));
        return response()->json([
            'entries' => $this->entriesForYear($year),
        ]);
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
            fputcsv($out, ['ID','Кличка','Описание','Дата начала','Дата окончания']);
            foreach ($entries as $row) {
                fputcsv($out, [$row['id'], $row['name'], $row['description'], $row['start_date'], $row['end_date']]);
            }
            fclose($out);
        }, 'boarding.csv', $headers);
    }

    private function entriesForYear(int $year)
    {
        $start = Carbon::create($year, 1, 1);
        $end = Carbon::create($year, 12, 31);

        return Boarding::where(function($q) use ($start, $end) {
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
                    'start_date' => $item->start_date->toDateString(),
                    'end_date' => $item->end_date->toDateString(),
                ];
            });
    }
}
