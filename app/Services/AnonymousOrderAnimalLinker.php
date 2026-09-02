<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\ServiceOrderAnimal;

class AnonymousOrderAnimalLinker
{
    public function link(ServiceOrderAnimal $position): Animal
    {
        if ($position->animal) {
            return $position->animal;
        }

        $category = $position->category;
        $animal = Animal::create([
            'category_id' => $position->category_id,
            'name' => $position->label ?: 'Питомец',
            'species' => $category?->name,
            'order' => (int) Animal::query()->max('order') + 1,
        ]);

        $position->update(['animal_id' => $animal->id]);

        return $animal;
    }
}
