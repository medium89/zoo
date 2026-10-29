<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Advantage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Support\ImageProcessor;
use Illuminate\Support\Str;

class AdvantageController extends Controller
{
    public function index()
    {
        $advantages = Advantage::orderBy('order')->orderBy('id')->get();
        return view('admin.advantages.index', compact('advantages'));
    }

    public function updateBackgroundColor(Request $request)
    {
        $data = $request->validate(['advantages_bg_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/']]);
        $settings = \App\Models\SiteSetting::firstOrNew([]);
        $settings->advantages_bg_color = $request->boolean('reset_bg') ? null : ($data['advantages_bg_color'] ?? null);
        $settings->save();
        return back()->with('success', 'Цвет фона преимуществ сохранён');
    }

    public function create()
    {
        return view('admin.advantages.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'title' => 'required|string|max:255',
            'short_text' => 'nullable|string|max:5000',
            'text' => 'required|string',
            'active' => 'required|boolean',
            'order' => 'nullable|integer',
            'image_scale' => 'nullable|integer|min:10|max:100',
            'image_quality' => 'nullable|integer|min:40|max:100',
        ]);
        $path = ImageProcessor::processAndStore($request->file('image'), 'advantages', (int)$request->input('image_scale',100), (int)$request->input('image_quality',85));
        Advantage::create([
            'image' => $path,
            'title' => $request->title,
            'short_text' => $request->input('short_text') ?: Str::words(trim(strip_tags($request->text)), 26, ''),
            'text' => $request->text,
            'active' => $request->boolean('active'),
            'order' => $request->input('order', 0),
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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'remove_image' => 'nullable|boolean',
            'title' => 'required|string|max:255',
            'short_text' => 'nullable|string|max:5000',
            'text' => 'required|string',
            'active' => 'required|boolean',
            'order' => 'nullable|integer',
            'image_scale' => 'nullable|integer|min:10|max:100',
            'image_quality' => 'nullable|integer|min:40|max:100',
        ]);
        if ($request->hasFile('image')) {
            if ($advantage->image && Storage::disk('public')->exists($advantage->image)) {
                \App\Support\AuditedStorage::deletePublic($advantage->image);
            }
            $path = ImageProcessor::processAndStore($request->file('image'), 'advantages', (int)$request->input('image_scale',100), (int)$request->input('image_quality',85));
            $advantage->image = $path;
        } elseif ($request->boolean('remove_image')) {
            if ($advantage->image && Storage::disk('public')->exists($advantage->image)) {
                \App\Support\AuditedStorage::deletePublic($advantage->image);
            }
            $advantage->image = '';
        }
        $advantage->title = $request->title;
        if ($request->filled('short_text')) {
            $advantage->short_text = $request->short_text;
        }
        $advantage->text = $request->text;
        $advantage->active = $request->boolean('active');
        $advantage->order = $request->input('order', 0);
        $advantage->save();
        return redirect()->route('admin.advantages.index')->with('success', 'Преимущество обновлено');
    }

    public function destroy(Advantage $advantage)
    {
        if ($advantage->image && Storage::disk('public')->exists($advantage->image)) {
            \App\Support\AuditedStorage::deletePublic($advantage->image);
        }
        $advantage->delete();
        return redirect()->route('admin.advantages.index')->with('success', 'Преимущество удалено');
    }

    public function updateStatus(Request $request)
    {
        foreach ($request->input('statuses', []) as $id => $status) {
            if ($adv = Advantage::find($id)) {
                $adv->active = (bool)$status;
                $adv->save();
            }
        }
        foreach ($request->input('orders', []) as $id => $order) {
            if ($adv = Advantage::find($id)) {
                $adv->order = (int)$order;
                $adv->save();
            }
        }
        return back()->with('success', 'Статусы обновлены');
    }
}
