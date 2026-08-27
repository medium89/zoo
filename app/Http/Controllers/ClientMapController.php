<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Category;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClientMapController extends Controller
{
    public function index()
    {
        $clients = Client::with('photos')->orderBy('name')->get();
        $animals = Animal::with(['photos', 'category'])->orderBy('name')->get();
        $categories = Category::orderBy('name')->get(['id', 'name']);
        $clientsPayload = $clients->map(fn (Client $client) => $this->clientPayload($client))->values()->all();
        $animalsPayload = $animals->map(fn (Animal $animal) => $this->animalPayload($animal))->values()->all();

        // Передаём сами модели также: карта должна быть заполнена уже первым
        // HTML-ответом, а JavaScript только добавляет перетаскивание и связи.
        return view('admin.client-map.index', compact('categories', 'clients', 'animals', 'clientsPayload', 'animalsPayload'));
    }

    public function storeClient(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
            'map_x' => 'nullable|numeric|between:-1000000,1000000',
            'map_y' => 'nullable|numeric|between:-1000000,1000000',
        ]);

        $client = Client::create($data);

        return response()->json($this->clientPayload($client), 201);
    }

    public function storeAnimal(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'photo' => 'nullable|image|max:10240',
            'map_x' => 'nullable|numeric|between:-1000000,1000000',
            'map_y' => 'nullable|numeric|between:-1000000,1000000',
        ]);

        $category = Category::find($data['category_id'] ?? null);
        $animal = Animal::create([
            'name' => $data['name'],
            'category_id' => $category?->id,
            'species' => $category?->name,
            'map_x' => $data['map_x'] ?? null,
            'map_y' => $data['map_y'] ?? null,
            'order' => (int) Animal::max('order') + 1,
        ]);

        if ($request->hasFile('photo')) {
            $animal->photos()->create(['path' => $request->file('photo')->store('animals/'.$animal->id, 'public')]);
        }

        return response()->json($this->animalPayload($animal->fresh('photos')), 201);
    }

    public function updateClient(Request $request, Client $client)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
        ]);

        $client->update($data);

        return response()->json($this->clientPayload($client->fresh()));
    }

    public function updateAnimal(Request $request, Animal $animal)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
        ]);
        $category = Category::find($data['category_id'] ?? null);

        $animal->update([
            'name' => $data['name'],
            'category_id' => $category?->id,
            'species' => $category?->name,
        ]);

        return response()->json($this->animalPayload($animal->fresh('photos')));
    }

    public function savePositions(Request $request)
    {
        $data = $request->validate([
            'nodes' => 'required|array|max:500',
            'nodes.*.type' => 'required|in:client,animal',
            'nodes.*.id' => 'required|integer',
            'nodes.*.x' => 'required|numeric|between:-1000000,1000000',
            'nodes.*.y' => 'required|numeric|between:-1000000,1000000',
        ]);

        foreach ($data['nodes'] as $node) {
            $model = $node['type'] === 'client' ? Client::find($node['id']) : Animal::find($node['id']);
            $model?->update(['map_x' => $node['x'], 'map_y' => $node['y']]);
        }

        return response()->json(['ok' => true]);
    }

    public function attachAnimal(Animal $animal, Client $client)
    {
        $animal->update(['client_id' => $client->id]);

        return response()->json(['ok' => true]);
    }

    public function detachAnimal(Animal $animal)
    {
        $animal->update(['client_id' => null]);

        return response()->json(['ok' => true]);
    }

    private function clientPayload(Client $client): array
    {
        return ['id' => $client->id, 'name' => $client->name, 'phone' => $client->phone, 'address' => $client->address, 'x' => $client->map_x, 'y' => $client->map_y,
            'photo' => $client->photos->first()?->path ? Storage::url($client->photos->first()->path) : null];
    }

    private function animalPayload(Animal $animal): array
    {
        return ['id' => $animal->id, 'name' => $animal->name, 'category_id' => $animal->category_id, 'client_id' => $animal->client_id, 'x' => $animal->map_x, 'y' => $animal->map_y,
            'photo' => $animal->photos->first()?->path ? Storage::url($animal->photos->first()->path) : null];
    }
}
