<?php

namespace App\Http\Controllers;

use App\Models\Slider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SliderController extends Controller
{
    public function index()
    {
        $sliders = Slider::all();
        return view('admin.sliders.index', compact('sliders'));
    }

    public function create()
    {
        return view('admin.sliders.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'text' => 'nullable|string',
            'text_bg' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'position' => 'required|in:left,center,right',
            'active' => 'required|boolean',
        ]);

        $data = [
            'image' => $request->file('image')->store('sliders', 'public'),
            'text' => $request->input('text'),
            'position' => $request->input('position'),
            'active' => $request->boolean('active'),
        ];

        if ($request->hasFile('text_bg')) {
            $data['text_bg'] = $request->file('text_bg')->store('sliders', 'public');
        }

        Slider::create($data);
        return redirect()->route('admin.sliders.index')->with('success', 'Слайд добавлен');
    }

    public function edit(Slider $slider)
    {
        return view('admin.sliders.edit', compact('slider'));
    }

    public function update(Request $request, Slider $slider)
    {
        $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'text' => 'nullable|string',
            'text_bg' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'position' => 'required|in:left,center,right',
            'active' => 'required|boolean',
        ]);
        if ($request->hasFile('image')) {
            if ($slider->image && Storage::disk('public')->exists($slider->image)) {
                Storage::disk('public')->delete($slider->image);
            }
            $path = $request->file('image')->store('sliders', 'public');
            $slider->image = $path;
        }

        if ($request->hasFile('text_bg')) {
            if ($slider->text_bg && Storage::disk('public')->exists($slider->text_bg)) {
                Storage::disk('public')->delete($slider->text_bg);
            }
            $slider->text_bg = $request->file('text_bg')->store('sliders', 'public');
        }

        $slider->text = $request->input('text');
        $slider->position = $request->input('position');
        $slider->active = $request->boolean('active');
        $slider->save();
        return redirect()->route('admin.sliders.index')->with('success', 'Слайд обновлён');
    }

    public function destroy(Slider $slider)
    {
        if ($slider->image && Storage::disk('public')->exists($slider->image)) {
            Storage::disk('public')->delete($slider->image);
        }
        $slider->delete();
        return redirect()->route('admin.sliders.index')->with('success', 'Слайд удалён');
    }

    public function updateStatus(Request $request)
    {
        foreach ($request->input('statuses', []) as $id => $status) {
            if ($slider = Slider::find($id)) {
                $slider->active = (bool)$status;
                $slider->save();
            }
        }
        return back()->with('success', 'Статусы обновлены');
    }

    public function ass()
    {
        // Ваш код здесь
    }

}
