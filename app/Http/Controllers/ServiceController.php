<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Support\ImageProcessor;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::all();
        return view('admin.services.index', compact('services'));
    }

    public function create()
    {
        return view('admin.services.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'title' => 'required|string|max:255',
            'text' => 'required|string',
            'active' => 'required|boolean',
            'image_scale' => 'nullable|integer|min:10|max:100',
            'image_quality' => 'nullable|integer|min:40|max:100',
        ]);
        $path = ImageProcessor::processAndStore($request->file('image'), 'services', (int)$request->input('image_scale',100), (int)$request->input('image_quality',85));
        Service::create([
            'image' => $path,
            'title' => $request->title,
            'text' => $request->text,
            'active' => $request->boolean('active'),
        ]);
        return redirect()->route('admin.services.index')->with('success', 'Услуга добавлена');
    }

    public function edit(Service $service)
    {
        return view('admin.services.edit', compact('service'));
    }

    public function update(Request $request, Service $service)
    {
        $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'title' => 'required|string|max:255',
            'text' => 'required|string',
            'active' => 'required|boolean',
            'image_scale' => 'nullable|integer|min:10|max:100',
            'image_quality' => 'nullable|integer|min:40|max:100',
        ]);
        if ($request->hasFile('image')) {
            if ($service->image && Storage::disk('public')->exists($service->image)) {
                Storage::disk('public')->delete($service->image);
            }
            $path = ImageProcessor::processAndStore($request->file('image'), 'services', (int)$request->input('image_scale',100), (int)$request->input('image_quality',85));
            $service->image = $path;
        }
        $service->title = $request->title;
        $service->text = $request->text;
        $service->active = $request->boolean('active');
        $service->save();
        return redirect()->route('admin.services.index')->with('success', 'Услуга обновлена');
    }

    public function destroy(Service $service)
    {
        if ($service->image && Storage::disk('public')->exists($service->image)) {
            Storage::disk('public')->delete($service->image);
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
        return back()->with('success', 'Статусы обновлены');
    }
}
