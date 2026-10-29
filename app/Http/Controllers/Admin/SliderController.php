<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Slider;
use App\Models\SiteSetting;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Support\ImageProcessor;

class SliderController extends Controller
{
    public function index()
    {
        $sliders = Slider::orderBy('order')->get();
        $siteSettings = SiteSetting::first();
        return view('admin.sliders.index', compact('sliders', 'siteSettings'));
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
            'remove_image' => 'nullable|boolean',
            'text' => 'nullable|string',
            'text_bg' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'remove_text_bg' => 'nullable|boolean',
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
                \App\Support\AuditedStorage::deletePublic($slider->image);
            }
            $path = ImageProcessor::processAndStore($request->file('image'), 'sliders', (int)$request->input('image_scale',100), (int)$request->input('image_quality',85));
            $slider->image = $path;
        } elseif ($request->boolean('remove_image')) {
            if ($slider->image && Storage::disk('public')->exists($slider->image)) {
                \App\Support\AuditedStorage::deletePublic($slider->image);
            }
            $slider->image = '';
        }

        if ($request->hasFile('text_bg')) {
            if ($slider->text_bg && Storage::disk('public')->exists($slider->text_bg)) {
                \App\Support\AuditedStorage::deletePublic($slider->text_bg);
            }
            $slider->text_bg = ImageProcessor::processAndStore($request->file('text_bg'), 'sliders', (int)$request->input('text_bg_scale',100), (int)$request->input('text_bg_quality',85));
        } elseif ($request->boolean('remove_text_bg')) {
            if ($slider->text_bg && Storage::disk('public')->exists($slider->text_bg)) {
                \App\Support\AuditedStorage::deletePublic($slider->text_bg);
            }
            $slider->text_bg = null;
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
            \App\Support\AuditedStorage::deletePublic($slider->image);
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

    public function updateHeroMode(Request $request)
    {
        $data = $request->validate(['mode' => 'required|in:slider,image']);
        if ($data['mode'] === 'slider' && !Slider::where('active', true)->whereNotNull('image')->where('image', '!=', '')->exists()) {
            throw ValidationException::withMessages(['mode' => 'Сначала добавьте и включите хотя бы один слайд с изображением.']);
        }

        $settings = SiteSetting::firstOrCreate([]);
        $settings->hero_mode = $data['mode'];
        $settings->save();

        return redirect()->route('admin.sliders.index', ['tab' => $data['mode']])
            ->with('success', 'Активный вариант первого экрана обновлён.');
    }

    public function updateHeroImage(Request $request)
    {
        $data = $request->validate([
            'hero_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:8192',
            'remove_hero_image' => 'nullable|boolean',
            'hero_text' => 'nullable|string|max:255',
            'hero_image_scale' => 'nullable|integer|min:10|max:100',
            'hero_image_quality' => 'nullable|integer|min:40|max:100',
        ]);

        $settings = SiteSetting::firstOrCreate([]);
        $oldImage = $settings->hero_image;
        if ($request->hasFile('hero_image')) {
            $settings->hero_image = ImageProcessor::processAndStore(
                $request->file('hero_image'),
                'home/hero',
                (int) $request->input('hero_image_scale', 100),
                (int) $request->input('hero_image_quality', 85)
            );
        } elseif ($request->boolean('remove_hero_image')) {
            $settings->hero_image = null;
        }
        $settings->hero_text = $data['hero_text'] ?? null;
        $settings->save();

        if ($oldImage && $oldImage !== $settings->hero_image) {
            \App\Support\AuditedStorage::deletePublic($oldImage);
        }

        return redirect()->route('admin.sliders.index', ['tab' => 'image'])
            ->with('success', 'Единая картинка сохранена.');
    }

}
