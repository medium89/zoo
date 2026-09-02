<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('service_order_animals')
            ->whereNull('animal_id')
            ->orderBy('id')
            ->each(function (object $position): void {
                $categoryName = $position->category_id
                    ? DB::table('categories')->where('id', $position->category_id)->value('name')
                    : null;
                $animalId = DB::table('animals')->insertGetId([
                    'category_id' => $position->category_id,
                    'name' => $position->label ?: 'Питомец',
                    'species' => $categoryName,
                    'order' => (int) DB::table('animals')->max('order') + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('service_order_animals')
                    ->where('id', $position->id)
                    ->update(['animal_id' => $animalId, 'updated_at' => now()]);
            });
    }

    public function down(): void
    {
        // The created cards can already contain user edits, so rollback must not delete them.
    }
};
