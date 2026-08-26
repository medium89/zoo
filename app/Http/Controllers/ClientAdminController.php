<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Animal;
use App\Models\Category;
use Illuminate\Http\Request;

class ClientAdminController extends Controller
{
    public function index()
    {
        $clients = Client::withCount(['animals', 'boardings'])
            ->orderBy('name')
            ->paginate(20);
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

        return view('admin.clients.index', compact('clients', 'mapClients', 'mapClientsPayload', 'yandexMapsKey'));
    }

    public function create()
    {
        return view('admin.clients.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['tags'] = $this->normalizeTags($data['tags'] ?? []);
        $client = Client::create($data);

        return redirect()->route('admin.clients.show', $client)->with('success', 'Клиент добавлен');
    }

    public function show(Client $client)
    {
        $client->load([
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
        $categories = Category::orderBy('name')->get(['id', 'name']);

        return view('admin.clients.show', compact('client', 'availableAnimals', 'categories'));
    }

    public function edit(Client $client)
    {
        return view('admin.clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $data = $this->validated($request);
        $data['tags'] = $this->normalizeTags($data['tags'] ?? []);
        $client->update($data);

        return redirect()->route('admin.clients.show', $client)->with('success', 'Клиент обновлён');
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
}
