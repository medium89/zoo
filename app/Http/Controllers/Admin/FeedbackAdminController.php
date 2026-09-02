<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedbackAdminController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|in:new,in_progress,completed,cancelled',
        ]);
        $feedbacks = Feedback::query()
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(fn ($items) => $items->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%"));
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderBy('order')->latest()->paginate(10)->withQueryString();

        return view('admin.feedbacks.index', compact('feedbacks', 'filters'));
    }

    public function edit(Feedback $feedback): View
    {
        return view('admin.feedbacks.edit', compact('feedback'));
    }

    public function update(Request $request, Feedback $feedback): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'message' => 'required|string',
            'status' => 'required|string|in:new,in_progress,completed,cancelled',
        ]);

        $feedback->update($data);

        return redirect()->route('admin.feedbacks.index')->with('success', 'Заявка обновлена');
    }

    public function destroy(Feedback $feedback): RedirectResponse
    {
        $feedback->delete();

        return redirect()->route('admin.feedbacks.index')->with('success', 'Заявка удалена');
    }

    public function reorder(Request $request): RedirectResponse
    {
        foreach ($request->input('orders', []) as $id => $order) {
            Feedback::query()->whereKey($id)->update(['order' => (int) $order]);
        }

        foreach ($request->input('statuses', []) as $id => $status) {
            if (in_array($status, ['new', 'in_progress', 'completed', 'cancelled'], true)) {
                Feedback::query()->whereKey($id)->update(['status' => $status]);
            }
        }

        return back()->with('success', 'Изменения сохранены');
    }
}
