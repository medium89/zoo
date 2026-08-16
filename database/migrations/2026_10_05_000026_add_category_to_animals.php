<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->foreignId('category_id')
                ->nullable()
                ->after('client_id')
                ->constrained('categories')
                ->nullOnDelete();
        });

        $categoryIds = DB::table('categories')->pluck('id', 'slug');
        $categoryBySpecies = [
            'кот' => 'koshki',
            'кошка' => 'koshki',
            'кошки' => 'koshki',
            'собака' => 'sobaki',
            'собаки' => 'sobaki',
            'пёс' => 'sobaki',
            'пес' => 'sobaki',
            'грызун' => 'gryzuny',
            'грызуны' => 'gryzuny',
            'птица' => 'pticy',
            'птицы' => 'pticy',
            'рыбка' => 'rybki',
            'рыбки' => 'rybki',
        ];

        DB::table('animals')->select(['id', 'species'])->orderBy('id')->each(function ($animal) use ($categoryBySpecies, $categoryIds): void {
            $species = mb_strtolower(trim((string) $animal->species));
            $slug = $categoryBySpecies[$species] ?? null;

            if ($slug && isset($categoryIds[$slug])) {
                DB::table('animals')->where('id', $animal->id)->update(['category_id' => $categoryIds[$slug]]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }
};
