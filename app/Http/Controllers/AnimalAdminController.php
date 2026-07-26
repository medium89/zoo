<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\AnimalPhoto;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AnimalAdminController extends Controller
{
    public function index()
    {
        $animals = Animal::with(['client'])
            ->withCount(['boardings', 'photos'])
            ->orderBy('name')
            ->paginate(20);

        return view('admin.animals.index', compact('animals'));
    }

    public function create()
    {
        $clients = Client::orderBy('name')->get();

        return view('admin.animals.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $data['order'] = (int)Animal::max('order') + 1;
        $animal = Animal::create($data);
        $this->storeUploadedPhotos($request, $animal);

        return redirect()->route('admin.animals.show', $animal)->with('success', 'Питомец добавлен');
    }

    public function show(Animal $animal)
    {
        $animal->load([
            'client',
            'photos',
            'boardings' => fn ($query) => $query->latest('start_date'),
        ]);

        return view('admin.animals.show', compact('animal'));
    }

    public function edit(Animal $animal)
    {
        $clients = Client::orderBy('name')->get();

        return view('admin.animals.edit', compact('animal', 'clients'));
    }

    public function update(Request $request, Animal $animal)
    {
        $data = $this->validated($request);

        $animal->update($data);
        $this->storeUploadedPhotos($request, $animal);

        return redirect()->route('admin.animals.show', $animal)->with('success', 'Питомец обновлен');
    }

    public function destroy(Animal $animal)
    {
        $animal->delete();

        return redirect()->route('admin.animals.index')->with('success', 'Питомец удален');
    }

    public function destroyPhoto(Animal $animal, AnimalPhoto $photo)
    {
        abort_unless($photo->animal_id === $animal->id, 404);

        Storage::disk('public')->delete($photo->path);
        $photo->delete();

        return back()->with('success', 'Фото удалено');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'name' => 'required|string|max:255',
            'species' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:255',
            'note' => 'nullable|string',
            'photos.*' => 'nullable|image|max:10240',
        ]);

        unset($data['photos']);

        return $data;
    }

    private function storeUploadedPhotos(Request $request, Animal $animal): void
    {
        foreach ($request->file('photos', []) as $photo) {
            $path = $photo->store('animals/'.$animal->id, 'public');
            $animal->photos()->create(['path' => $path]);
        }
    }
}
