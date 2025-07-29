<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'message' => 'required|string',
        ]);

        Feedback::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'message' => $request->message,
            'status' => 'new',
        ]);

        if ($request->ajax()) {
            return response()->json(['message' => 'Ваше сообщение успешно отправлено!']);
        }

        return back()->with('success', 'Ваше сообщение успешно отправлено!');
    }

    public function index()
    {
        $feedbacks = Feedback::latest()->paginate(10);
        return view('admin.feedbacks.index', compact('feedbacks'));
    }

    public function edit(Feedback $feedback)
    {
        return view('admin.feedbacks.edit', compact('feedback'));
    }

    public function update(Request $request, Feedback $feedback)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'message' => 'required|string',
            'status' => 'required|string|in:new,in_progress,completed,cancelled',
        ]);

        $feedback->update($request->all());

        return redirect()->route('admin.feedbacks.index')->with('success', 'Feedback updated successfully!');
    }

    public function destroy(Feedback $feedback)
    {
        $feedback->delete();
        return redirect()->route('admin.feedbacks.index')->with('success', 'Feedback deleted successfully!');
    }
}
