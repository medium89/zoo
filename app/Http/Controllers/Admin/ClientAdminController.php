<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Client;
use App\Models\Animal;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ClientAdminController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:255',
            'animals' => 'nullable|in:with,without',
            'address' => 'nullable|in:with,without',
        ]);
        $clients = Client::with(['animals.category', 'photos'])->withCount(['animals', 'boardings'])
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(fn ($items) => $items->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%"));
            })
            ->when(($filters['animals'] ?? null) === 'with', fn ($query) => $query->has('animals'))
            ->when(($filters['animals'] ?? null) === 'without', fn ($query) => $query->doesntHave('animals'))
            ->when(($filters['address'] ?? null) === 'with', fn ($query) => $query->whereNotNull('address')->where('address', '!=', ''))
            ->when(($filters['address'] ?? null) === 'without', fn ($query) => $query->where(fn ($items) => $items->whereNull('address')->orWhere('address', '')))
            ->orderBy('name')
            ->paginate(20)->withQueryString();
        $mapClients = Client::query()
            ->whereNotNull('address')
            ->where('address', '!=', '')
            ->orderBy('name')
            ->get(['id', 'name', 'address', 'phone']);
        $mapClientsPayload = $mapClients->map(fn (Client $client) => [
            'id' => $client->id,
            'name' => $client->name,
            'address' => $client->address,
            'phone' => $client->phone,
        ])->values()->all();
        $yandexMapsKey = config('services.yandex.maps_api_key');
        $yandexSuggestKey = config('services.yandex.suggest_api_key');

        $categories = Category::orderBy('name')->get(['id', 'name']);
        $animalsPayload = Animal::with('client')->orderBy('name')->get()->map(fn (Animal $animal) => [
            'id' => $animal->id,
            'name' => $animal->name,
            'category_id' => $animal->category_id,
            'client' => $animal->client?->name,
        ])->values()->all();
        $clientsPayload = $clients->getCollection()->mapWithKeys(fn (Client $client) => [$client->id => [
            'id' => $client->id,
            'name' => $client->name,
            'phone' => $client->phone,
            'address' => $client->address,
            'note' => $client->note,
            'photo' => $client->photos->first()?->path ? Storage::url($client->photos->first()->path) : null,
            'animals' => $client->animals->map(fn (Animal $animal) => [
                'id' => $animal->id,
                'name' => $animal->name,
                'category_id' => $animal->category_id,
            ])->values()->all(),
        ]])->all();

        return view('admin.clients.index', compact('clients', 'mapClients', 'mapClientsPayload', 'yandexMapsKey', 'yandexSuggestKey', 'categories', 'animalsPayload', 'clientsPayload', 'filters'));
    }

    public function create()
    {
        return view('admin.clients.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['tags'] = $this->normalizeTags($data['tags'] ?? []);
        $animals = $data['animals'] ?? [];
        $photos = $request->file('photos', []);
        unset($data['animals'], $data['photos']);

        $client = DB::transaction(function () use ($data, $animals) {
            $client = Client::create($data);
            $this->syncAnimals($client, $animals);

            return $client;
        });

        $this->storePhotos($client, $photos);

        return redirect()->route('admin.clients.index')->with('success', 'Клиент добавлен'.($client->animals()->exists() ? ' вместе с питомцами' : ''));
    }

    public function show(Client $client)
    {
        $client->load([
            'photos',
            'animals.photos',
            'animals.category',
            'animals.boardings' => fn ($query) => $query->latest('start_date'),
            'boardings.animal.photos',
            'boardings.animal.category',
        ]);

        $availableAnimals = Animal::with('client')
            ->where(fn ($query) => $query->where('client_id', '!=', $client->id)->orWhereNull('client_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'client_id']);
        $availableAnimalsPayload = $availableAnimals->map(fn (Animal $animal) => [
            'id' => $animal->id,
            'name' => $animal->name,
            'client' => $animal->client?->name,
        ])->values()->all();
        $categories = Category::orderBy('name')->get(['id', 'name']);

        return view('admin.clients.show', compact('client', 'availableAnimalsPayload', 'categories'));
    }

    public function edit(Client $client)
    {
        return view('admin.clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $data = $this->validated($request);
        if ($request->has('tags')) {
            $data['tags'] = $this->normalizeTags($data['tags'] ?? []);
        } else {
            unset($data['tags']);
        }
        $animals = $data['animals'] ?? [];
        $photos = $request->file('photos', []);
        unset($data['animals'], $data['photos']);
        DB::transaction(function () use ($client, $data, $animals) {
            $client->update($data);
            $this->syncAnimals($client, $animals);
        });
        $this->storePhotos($client, $photos);

        return redirect()->route('admin.clients.index')->with('success', 'Клиент обновлён');
    }

    public function destroy(Client $client)
    {
        $client->delete();

        return redirect()->route('admin.clients.index')->with('success', 'Клиент удалён');
    }

    public function attachAnimal(Request $request, Client $client)
    {
        $data = $request->validate([
            'animal_id' => 'nullable|exists:animals,id',
            'new_animal_name' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'dog_size' => 'nullable|in:small,large',
            'description' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:5000',
        ]);

        $name = trim((string) ($data['new_animal_name'] ?? ''));
        if ($name !== '') {
            $category = Category::find($data['category_id'] ?? null);
            $client->animals()->create([
                'name' => $name,
                'category_id' => $category?->id,
                'species' => $category?->name,
                'dog_size' => $data['dog_size'] ?? null,
                'description' => $data['description'] ?? null,
                'note' => $data['note'] ?? null,
                'order' => (int) Animal::max('order') + 1,
            ]);

            return back()->with('success', 'Питомец создан и привязан к клиенту');
        }

        if (!empty($data['animal_id'])) {
            Animal::findOrFail($data['animal_id'])->update(['client_id' => $client->id]);
            return back()->with('success', 'Питомец привязан к клиенту');
        }

        return back()->withErrors(['new_animal_name' => 'Выберите питомца или укажите кличку нового.']);
    }

    public function detachAnimal(Client $client, Animal $animal)
    {
        abort_unless($animal->client_id === $client->id, 404);

        $animal->update(['client_id' => null]);

        return back()->with('success', 'Питомец отвязан от клиента');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
            'note' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*.name' => 'nullable|string|max:60',
            'tags.*.type' => 'nullable|in:positive,negative',
            'photos' => 'nullable|array|max:10',
            'photos.*' => 'nullable|image|max:10240',
            'animals' => 'nullable|array|max:30',
            'animals.*.animal_id' => 'nullable|exists:animals,id',
            'animals.*.name' => 'nullable|string|max:255',
            'animals.*.category_id' => 'nullable|exists:categories,id',
        ]);
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

    private function storePhotos(Client $client, array $photos): void
    {
        foreach ($photos as $photo) {
            if ($photo) {
                $client->photos()->create([
                    'path' => $photo->store('clients/'.$client->id, 'public'),
                ]);
            }
        }
    }

    private function syncAnimals(Client $client, array $positions): void
    {
        $submittedIds = collect($positions)->pluck('animal_id')->filter()->map(fn ($id) => (int) $id)->all();
        $client->animals()->whereNotIn('id', $submittedIds)->update(['client_id' => null]);

        foreach ($positions as $position) {
            $name = trim((string) ($position['name'] ?? ''));
            $animal = !empty($position['animal_id']) ? Animal::find($position['animal_id']) : null;
            if ($animal) {
                $animal->update(['client_id' => $client->id]);
                continue;
            }
            if ($name === '') {
                continue;
            }
            $category = Category::find($position['category_id'] ?? null);
            $client->animals()->create([
                'name' => $name,
                'category_id' => $category?->id,
                'species' => $category?->name,
                'order' => (int) Animal::max('order') + 1,
            ]);
        }
    }
}
