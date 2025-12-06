<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use Illuminate\Http\Request;

class AnimalAdminController extends Controller
{
    public function index()
    {
        $animals = Animal::orderBy('name')->paginate(20);
        return view('admin.animals.index', compact('animals'));
    }

    public function create()
    {
        return view('admin.animals.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:animals,name',
            'description' => 'nullable|string|max:255',
        ]);

        Animal::create($data);

        return redirect()->route('admin.animals.index')->with('success', 'Питомец добавлен');
    }

    public function edit(Animal $animal)
    {
        return view('admin.animals.edit', compact('animal'));
    }

    public function update(Request $request, Animal $animal)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:animals,name,' . $animal->id,
            'description' => 'nullable|string|max:255',
        ]);

        $animal->update($data);

        return redirect()->route('admin.animals.index')->with('success', 'Питомец обновлен');
    }

    public function destroy(Animal $animal)
    {
        $animal->delete();

        return redirect()->route('admin.animals.index')->with('success', 'Питомец удален');
    }
}
