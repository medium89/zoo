<?php

namespace App\Http\Controllers;

use App\Models\Advantage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdvantageController extends Controller
{
    public function index()
    {
        $advantages = Advantage::all();
        return view('admin.advantages.index', compact('advantages'));
    }

    public function create()
    {
        return view('admin.advantages.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'title' => 'required|string|max:255',
            'text' => 'required|string',
        ]);
        $path = $request->file('image')->store('advantages', 'public');
        Advantage::create([
            'image' => $path,
            'title' => $request->title,
            'text' => $request->text,
        ]);
        return redirect()->route('admin.advantages.index')->with('success', 'Преимущество добавлено');
    }

    public function edit(Advantage $advantage)
    {
        return view('admin.advantages.edit', compact('advantage'));
    }

    public function update(Request $request, Advantage $advantage)
    {
        $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'title' => 'required|string|max:255',
            'text' => 'required|string',
        ]);
        if ($request->hasFile('image')) {
            if ($advantage->image && Storage::disk('public')->exists($advantage->image)) {
                Storage::disk('public')->delete($advantage->image);
            }
            $path = $request->file('image')->store('advantages', 'public');
            $advantage->image = $path;
        }
        $advantage->title = $request->title;
        $advantage->text = $request->text;
        $advantage->save();
        return redirect()->route('admin.advantages.index')->with('success', 'Преимущество обновлено');
    }

    public function destroy(Advantage $advantage)
    {
        if ($advantage->image && Storage::disk('public')->exists($advantage->image)) {
            Storage::disk('public')->delete($advantage->image);
        }
        $advantage->delete();
        return redirect()->route('admin.advantages.index')->with('success', 'Преимущество удалено');
    }
}
