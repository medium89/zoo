<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\AnimalPhoto;
use App\Models\Client;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AnimalAdminController extends Controller
{
    public function index()
    {
        $animals = Animal::with(['client', 'category'])
            ->withCount(['boardings', 'photos'])
            ->orderBy('name')
            ->paginate(20);

        return view('admin.animals.index', compact('animals'));
    }

    public function create()
    {
        $clients = Client::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('admin.animals.create', compact('clients', 'categories'));
    }

    public function store(Request $request)
    {
        $data = $this->withCategoryName($this->validated($request));
        $data['tags'] = $this->normalizeTags($data['tags'] ?? []);

        $data['order'] = (int)Animal::max('order') + 1;
        $animal = Animal::create($data);
        $this->storeUploadedPhotos($request, $animal);

        return redirect()->route('admin.animals.show', $animal)->with('success', 'Питомец добавлен');
    }

    public function show(Animal $animal)
    {
        $animal->load([
            'client',
            'category',
            'photos',
            'boardings' => fn ($query) => $query->latest('start_date'),
        ]);

        $clientsPayload = Client::orderBy('name')->get(['id', 'name', 'phone'])
            ->map(fn (Client $client) => [
                'id' => $client->id,
                'name' => $client->name,
                'phone' => $client->phone,
            ])->values()->all();

        return view('admin.animals.show', compact('animal', 'clientsPayload'));
    }

    public function edit(Animal $animal)
    {
        $clients = Client::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('admin.animals.edit', compact('animal', 'clients', 'categories'));
    }

    public function update(Request $request, Animal $animal)
    {
        $data = $this->withCategoryName($this->validated($request));
        $data['tags'] = $this->normalizeTags($data['tags'] ?? []);

        $animal->update($data);
        $this->storeUploadedPhotos($request, $animal);

        return redirect()->route('admin.animals.show', $animal)->with('success', 'Питомец обновлен');
    }

    public function destroy(Animal $animal)
    {
        $animal->delete();

        return redirect()->route('admin.animals.index')->with('success', 'Питомец удален');
    }

    public function assignClient(Request $request, Animal $animal)
    {
        $data = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'new_client_name' => 'nullable|string|max:255',
            'new_client_phone' => 'nullable|string|max:255',
            'new_client_note' => 'nullable|string|max:5000',
        ]);

        $name = trim((string) ($data['new_client_name'] ?? ''));
        if ($name !== '') {
            $client = Client::firstOrCreate(['name' => $name], [
                'phone' => $data['new_client_phone'] ?? null,
                'note' => $data['new_client_note'] ?? null,
            ]);
            $animal->update(['client_id' => $client->id]);

            return back()->with('success', 'Хозяин создан и назначен');
        }

        if (!empty($data['client_id'])) {
            $animal->update(['client_id' => $data['client_id']]);
            return back()->with('success', 'Хозяин назначен');
        }

        return back()->withErrors(['new_client_name' => 'Выберите существующего клиента или укажите имя нового.']);
    }

    public function detachClient(Animal $animal)
    {
        $animal->update(['client_id' => null]);

        return back()->with('success', 'Хозяин отвязан от питомца');
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
            'category_id' => 'nullable|exists:categories,id',
            'dog_size' => 'nullable|in:small,large',
            'description' => 'nullable|string|max:255',
            'note' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*.name' => 'nullable|string|max:60',
            'tags.*.type' => 'nullable|in:positive,negative',
            'photos.*' => 'nullable|image|max:10240',
        ]);

        unset($data['photos']);

        return $data;
    }

    private function withCategoryName(array $data): array
    {
        $data['species'] = !empty($data['category_id'])
            ? Category::find($data['category_id'])?->name
            : null;

        return $data;
    }

    private function normalizeTags(array $tags): array
    {
        return collect($tags)
            ->map(fn (array $tag) => [
                'name' => trim((string) ($tag['name'] ?? '')),
                'type' => ($tag['type'] ?? null) === 'positive' ? 'positive' : 'negative',
            ])
            ->filter(fn (array $tag) => $tag['name'] !== '')
            ->unique(fn (array $tag) => mb_strtolower($tag['name']))
            ->values()
            ->all();
    }

    private function storeUploadedPhotos(Request $request, Animal $animal): void
    {
        foreach ($request->file('photos', []) as $photo) {
            $path = $photo->store('animals/'.$animal->id, 'public');
            $animal->photos()->create(['path' => $path]);
        }
    }
}
