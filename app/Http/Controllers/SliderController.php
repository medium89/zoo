<?php

namespace App\Http\Controllers;

use App\Models\Slider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Support\ImageProcessor;

class SliderController extends Controller
{
    public function index()
    {
        $sliders = Slider::orderBy('order')->get();
        return view('admin.sliders.index', compact('sliders'));
    }

    public function create()
    {
        return view('admin.sliders.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'text' => 'nullable|string',
            'text_bg' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'position' => 'required|in:left,center,right',
            'order' => 'nullable|integer',
            'active' => 'required|boolean',
            'image_scale' => 'nullable|integer|min:10|max:100',
            'image_quality' => 'nullable|integer|min:40|max:100',
            'text_bg_scale' => 'nullable|integer|min:10|max:100',
            'text_bg_quality' => 'nullable|integer|min:40|max:100',
        ]);

        $imgScale = (int)$request->input('image_scale', 100);
        $imgQuality = (int)$request->input('image_quality', 85);
        $data = [
            'image' => ImageProcessor::processAndStore($request->file('image'), 'sliders', $imgScale, $imgQuality),
            'text' => $request->input('text'),
            'position' => $request->input('position'),
            'order' => $request->input('order'),
            'active' => $request->boolean('active'),
        ];

        if ($request->hasFile('text_bg')) {
            $bgScale = (int)$request->input('text_bg_scale', 100);
            $bgQuality = (int)$request->input('text_bg_quality', 85);
            $data['text_bg'] = ImageProcessor::processAndStore($request->file('text_bg'), 'sliders', $bgScale, $bgQuality);
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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'text' => 'nullable|string',
            'text_bg' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'position' => 'required|in:left,center,right',
            'order' => 'nullable|integer',
            'active' => 'required|boolean',
            'image_scale' => 'nullable|integer|min:10|max:100',
            'image_quality' => 'nullable|integer|min:40|max:100',
            'text_bg_scale' => 'nullable|integer|min:10|max:100',
            'text_bg_quality' => 'nullable|integer|min:40|max:100',
        ]);
        if ($request->hasFile('image')) {
            if ($slider->image && Storage::disk('public')->exists($slider->image)) {
                Storage::disk('public')->delete($slider->image);
            }
            $path = ImageProcessor::processAndStore($request->file('image'), 'sliders', (int)$request->input('image_scale',100), (int)$request->input('image_quality',85));
            $slider->image = $path;
        }

        if ($request->hasFile('text_bg')) {
            if ($slider->text_bg && Storage::disk('public')->exists($slider->text_bg)) {
                Storage::disk('public')->delete($slider->text_bg);
            }
            $slider->text_bg = ImageProcessor::processAndStore($request->file('text_bg'), 'sliders', (int)$request->input('text_bg_scale',100), (int)$request->input('text_bg_quality',85));
        }

        $slider->text = $request->input('text');
        $slider->position = $request->input('position');
        $slider->order = $request->input('order');
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
        foreach ($request->input('orders', []) as $id => $order) {
            if ($slider = Slider::find($id)) {
                $slider->order = (int)$order;
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
