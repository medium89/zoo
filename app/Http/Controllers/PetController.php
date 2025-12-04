<?php

namespace App\Http\Controllers;

use App\Models\Pet;
use App\Models\PetPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Support\ImageProcessor;

class PetController extends Controller
{
    public function index()
    {
        $pets = Pet::orderByDesc('id')->paginate(12);
        return view('admin.pets.index', compact('pets'));
    }

    public function create()
    {
        return view('admin.pets.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'service_type' => 'nullable|in:передержка,выгул,кормление',
            'services' => 'nullable|array',
            'services.*' => 'in:передержка,выгул,кормление',
            'description' => 'nullable|string',
            'owner_name' => 'nullable|string|max:255',
            'owner_phone' => 'nullable|string|max:32',
            'animal_type' => 'nullable|in:кошка,собака,грызун,птица,прочее',
            'pluses' => 'nullable|array',
            'pluses.*' => 'nullable|string|max:255',
            'minuses' => 'nullable|array',
            'minuses.*' => 'nullable|string|max:255',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:12288',
            'scales' => 'nullable|array',
            'scales.*' => 'nullable|integer|min:10|max:100',
            'qualities' => 'nullable|array',
            'qualities.*' => 'nullable|integer|min:40|max:100',
        ]);

        $servicesArr = array_values(array_filter($request->input('services', []), fn($v)=>$v!==null && $v!==''));
        $serviceType = $request->input('service_type') ?: ($servicesArr[0] ?? 'передержка');

        $pet = Pet::create([
            'name' => $request->name,
            'service_type' => $serviceType,
            'services' => $servicesArr ?: null,
            'description' => $request->description,
            'owner_name' => $request->owner_name,
            'owner_phone' => $request->owner_phone,
            'animal_type' => $request->animal_type,
            'pluses' => array_values(array_filter($request->input('pluses', []), fn($v)=>$v!==null && $v!=='')),
            'minuses' => array_values(array_filter($request->input('minuses', []), fn($v)=>$v!==null && $v!=='')),
            'active' => true,
        ]);

        if ($request->hasFile('images')) {
            $scales = $request->input('scales', []);
            $qualities = $request->input('qualities', []);
            $order = 1;
            foreach ($request->file('images') as $i => $uploaded) {
                $scale = (int)($scales[$i] ?? 100);
                $quality = (int)($qualities[$i] ?? 85);
                $path = ImageProcessor::processAndStore($uploaded, 'pets', $scale, $quality);
                PetPhoto::create([
                    'pet_id' => $pet->id,
                    'path' => $path,
                    'order' => $order++,
                ]);
            }
        }

        return redirect()->route('admin.pets.index')->with('success', 'Питомец добавлен');
    }

    public function edit(Pet $pet)
    {
        $pet->load('photos');
        return view('admin.pets.edit', compact('pet'));
    }

    public function show(Pet $pet)
    {
        $pet->load('photos');
        return view('admin.pets.show', compact('pet'));
    }

    public function update(Request $request, Pet $pet)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'service_type' => 'nullable|in:передержка,выгул,кормление',
            'services' => 'nullable|array',
            'services.*' => 'in:передержка,выгул,кормление',
            'description' => 'nullable|string',
            'owner_name' => 'nullable|string|max:255',
            'owner_phone' => 'nullable|string|max:32',
            'animal_type' => 'nullable|in:кошка,собака,грызун,птица,прочее',
            'pluses' => 'nullable|array',
            'pluses.*' => 'nullable|string|max:255',
            'minuses' => 'nullable|array',
            'minuses.*' => 'nullable|string|max:255',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:12288',
            'scales' => 'nullable|array',
            'scales.*' => 'nullable|integer|min:10|max:100',
            'qualities' => 'nullable|array',
            'qualities.*' => 'nullable|integer|min:40|max:100',
        ]);

        $servicesArr = array_values(array_filter($request->input('services', []), fn($v)=>$v!==null && $v!==''));
        $serviceType = $request->input('service_type') ?: ($servicesArr[0] ?? $pet->service_type ?? 'передержка');

        $pet->update([
            'name' => $request->name,
            'service_type' => $serviceType,
            'services' => $servicesArr ?: null,
            'description' => $request->description,
            'owner_name' => $request->owner_name,
            'owner_phone' => $request->owner_phone,
            'animal_type' => $request->animal_type,
            'pluses' => array_values(array_filter($request->input('pluses', []), fn($v)=>$v!==null && $v!=='')),
            'minuses' => array_values(array_filter($request->input('minuses', []), fn($v)=>$v!==null && $v!=='')),
        ]);

        if ($request->hasFile('images')) {
            $scales = $request->input('scales', []);
            $qualities = $request->input('qualities', []);
            $order = (int)($pet->photos()->max('order') ?? 0) + 1;
            foreach ($request->file('images') as $i => $uploaded) {
                $scale = (int)($scales[$i] ?? 100);
                $quality = (int)($qualities[$i] ?? 85);
                $path = ImageProcessor::processAndStore($uploaded, 'pets', $scale, $quality);
                PetPhoto::create([
                    'pet_id' => $pet->id,
                    'path' => $path,
                    'order' => $order++,
                ]);
            }
        }

        return redirect()->route('admin.pets.index')->with('success', 'Питомец обновлён');
    }

    public function destroy(Pet $pet)
    {
        foreach ($pet->photos as $photo) {
            if ($photo->path && Storage::disk('public')->exists($photo->path)) {
                Storage::disk('public')->delete($photo->path);
            }
        }
        $pet->delete();
        return redirect()->route('admin.pets.index')->with('success', 'Питомец удалён');
    }
}
