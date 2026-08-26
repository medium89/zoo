<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Category;
use App\Models\Client;
use Illuminate\Http\Request;

class LinkedDataController extends Controller
{
    public function index()
    {
        $clients = Client::with(['animals.photos', 'animals.category'])->orderBy('name')->get();
        $unlinkedAnimals = Animal::with(['photos', 'category'])->whereNull('client_id')->orderBy('name')->get();
        $categories = Category::orderBy('name')->get(['id', 'name']);

        return view('admin.linked-data.index', compact('clients', 'unlinkedAnimals', 'categories'));
    }

    public function storeClient(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
        ]);

        Client::create($data);

        return redirect()->route('admin.linked-data.index')->with('success', 'Клиент добавлен. Теперь можно сразу добавить ему питомца.');
    }

    public function storeAnimal(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'photo' => 'nullable|image|max:10240',
        ]);
        $category = Category::find($data['category_id'] ?? null);
        $animal = Animal::create([
            'client_id' => $data['client_id'],
            'name' => $data['name'],
            'category_id' => $category?->id,
            'species' => $category?->name,
            'order' => (int) Animal::max('order') + 1,
        ]);

        if ($request->hasFile('photo')) {
            $animal->photos()->create(['path' => $request->file('photo')->store('animals/'.$animal->id, 'public')]);
        }

        return redirect()->route('admin.linked-data.index')->with('success', 'Питомец добавлен и привязан к клиенту.');
    }
}
