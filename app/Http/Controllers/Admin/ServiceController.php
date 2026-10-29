<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Support\ImageProcessor;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::orderBy('order')->orderBy('id')->get();
        return view('admin.services.index', compact('services'));
    }

    public function create()
    {
        $previewOrders = Service::where('active', true)->orderBy('order')->orderBy('id')->get(['id', 'order']);
        return view('admin.services.create', compact('previewOrders'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'title' => 'required|string|max:255',
            'text' => 'required|string',
            'tags_text' => 'nullable|string|max:4000',
            'sticker_image' => 'nullable|file|mimetypes:image/jpeg,image/png,image/gif,image/webp,image/avif,image/bmp,image/x-ms-bmp,image/svg+xml|max:5120',
            'sticker_doodle' => 'nullable|file|mimetypes:image/jpeg,image/png,image/gif,image/webp,image/avif,image/bmp,image/x-ms-bmp,image/svg+xml|max:5120',
            'remove_sticker_image' => 'sometimes|boolean',
            'remove_sticker_doodle' => 'sometimes|boolean',
            'active' => 'required|boolean',
            'order' => 'nullable|integer',
            'image_scale' => 'nullable|integer|min:10|max:100',
            'image_quality' => 'nullable|integer|min:40|max:100',
            'sticker_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'tape_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'custom_sticker_color' => 'nullable|boolean',
            'custom_tape_color' => 'nullable|boolean',
        ]);
        $path = ImageProcessor::processAndStore($request->file('image'), 'services', (int)$request->input('image_scale',100), (int)$request->input('image_quality',85));
        Service::create([
            'image' => $path,
            'sticker_image' => $request->hasFile('sticker_image')
                ? ImageProcessor::processAndStore($request->file('sticker_image'), 'services/stickers', (int)$request->input('image_scale',100), (int)$request->input('image_quality',85))
                : null,
            'sticker_doodle' => $request->file('sticker_doodle')?->store('services/sticker-doodles', 'public'),
            'title' => $request->title,
            'text' => $request->text,
            'tags' => self::parseTags($request->input('tags_text')),
            'sticker_color' => $request->boolean('custom_sticker_color') ? $request->input('sticker_color') : null,
            'tape_color' => $request->boolean('custom_tape_color') ? $request->input('tape_color') : null,
            'active' => $request->boolean('active'),
            'order' => $request->input('order', 0),
        ]);
        return redirect()->route('admin.services.index')->with('success', 'Услуга добавлена');
    }

    public function edit(Service $service)
    {
        $previewOrders = Service::where('active', true)->orderBy('order')->orderBy('id')->get(['id', 'order']);
        return view('admin.services.edit', compact('service', 'previewOrders'));
    }

    public function update(Request $request, Service $service)
    {
        $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'remove_image' => 'nullable|boolean',
            'title' => 'required|string|max:255',
            'text' => 'required|string',
            'tags_text' => 'nullable|string|max:4000',
            'sticker_image' => 'nullable|file|mimetypes:image/jpeg,image/png,image/gif,image/webp,image/avif,image/bmp,image/x-ms-bmp,image/svg+xml|max:5120',
            'sticker_doodle' => 'nullable|file|mimetypes:image/jpeg,image/png,image/gif,image/webp,image/avif,image/bmp,image/x-ms-bmp,image/svg+xml|max:5120',
            'remove_sticker_image' => 'sometimes|boolean',
            'remove_sticker_doodle' => 'sometimes|boolean',
            'active' => 'required|boolean',
            'order' => 'nullable|integer',
            'image_scale' => 'nullable|integer|min:10|max:100',
            'image_quality' => 'nullable|integer|min:40|max:100',
            'sticker_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'tape_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'custom_sticker_color' => 'nullable|boolean',
            'custom_tape_color' => 'nullable|boolean',
        ]);
        if ($request->hasFile('image')) {
            if ($service->image && Storage::disk('public')->exists($service->image)) {
                \App\Support\AuditedStorage::deletePublic($service->image);
            }
            $path = ImageProcessor::processAndStore($request->file('image'), 'services', (int)$request->input('image_scale',100), (int)$request->input('image_quality',85));
            $service->image = $path;
        } elseif ($request->boolean('remove_image')) {
            if ($service->image && Storage::disk('public')->exists($service->image)) {
                \App\Support\AuditedStorage::deletePublic($service->image);
            }
            $service->image = '';
        }
        $previousStickerImage = $service->sticker_image;
        if ($request->hasFile('sticker_image')) {
            $service->sticker_image = ImageProcessor::processAndStore($request->file('sticker_image'), 'services/stickers', (int)$request->input('image_scale',100), (int)$request->input('image_quality',85));
        } elseif ($request->boolean('remove_sticker_image')) {
            $service->sticker_image = null;
        }
        $previousDoodle = $service->sticker_doodle;
        if ($request->hasFile('sticker_doodle')) {
            $service->sticker_doodle = $request->file('sticker_doodle')->store('services/sticker-doodles', 'public');
        } elseif ($request->boolean('remove_sticker_doodle')) {
            $service->sticker_doodle = null;
        }
        $service->title = $request->title;
        $service->text = $request->text;
        $service->tags = self::parseTags($request->input('tags_text'));
        if ($request->exists('sticker_color')) {
            $service->sticker_color = $request->boolean('custom_sticker_color') ? $request->input('sticker_color') : null;
        }
        if ($request->exists('tape_color')) {
            $service->tape_color = $request->boolean('custom_tape_color') ? $request->input('tape_color') : null;
        }
        $service->active = $request->boolean('active');
        $service->order = $request->input('order', 0);
        $service->save();
        if ($previousStickerImage && $previousStickerImage !== $service->sticker_image) {
            \App\Support\AuditedStorage::deletePublic($previousStickerImage);
        }
        if ($previousDoodle && $previousDoodle !== $service->sticker_doodle) {
            \App\Support\AuditedStorage::deletePublic($previousDoodle);
        }
        return redirect()->route('admin.services.index')->with('success', 'Услуга обновлена');
    }

    private static function parseTags(?string $input): array
    {
        return collect(preg_split('/\R/u', $input ?? ''))
            ->map(fn (string $tag) => trim($tag))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function destroy(Service $service)
    {
        if ($service->sticker_image) {
            \App\Support\AuditedStorage::deletePublic($service->sticker_image);
        }
        if ($service->sticker_doodle) {
            \App\Support\AuditedStorage::deletePublic($service->sticker_doodle);
        }
        if ($service->image && Storage::disk('public')->exists($service->image)) {
            \App\Support\AuditedStorage::deletePublic($service->image);
        }
        $service->delete();
        return redirect()->route('admin.services.index')->with('success', 'Услуга удалена');
    }

    public function updateStatus(Request $request)
    {
        foreach ($request->input('statuses', []) as $id => $status) {
            if ($service = Service::find($id)) {
                $service->active = (bool)$status;
                $service->save();
            }
        }
        foreach ($request->input('orders', []) as $id => $order) {
            if ($service = Service::find($id)) {
                $service->order = (int)$order;
                $service->save();
            }
        }
        return back()->with('success', 'Статусы обновлены');
    }
}
