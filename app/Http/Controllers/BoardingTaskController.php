<?php

namespace App\Http\Controllers;

use App\Models\Boarding;
use App\Models\BoardingTask;
use Illuminate\Http\Request;

class BoardingTaskController extends Controller
{
    public function index(Boarding $boarding)
    {
        $boarding->load(['animal', 'tasks.runs' => fn ($query) => $query->latest('notification_date')->limit(7)]);

        return view('admin.boarding.tasks', compact('boarding'));
    }

    public function store(Request $request, Boarding $boarding)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'scheduled_time' => 'required|date_format:H:i',
        ]);

        $boarding->tasks()->create($data);

        return back()->with('success', 'Действие добавлено.');
    }

    public function update(Request $request, BoardingTask $task)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'scheduled_time' => 'required|date_format:H:i',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $task->update($data);

        return back()->with('success', 'Действие обновлено.');
    }

    public function destroy(BoardingTask $task)
    {
        $task->delete();

        return back()->with('success', 'Действие удалено.');
    }
}
