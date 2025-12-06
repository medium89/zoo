<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('order')->constrained('categories')->nullOnDelete();
        });

        $defaults = [
            'Кошки','Собаки','Грызуны','Птицы','Рептилии','Рыбки','Насекомые','Пауки','Другие'
        ];
        $now = now();
        foreach ($defaults as $name) {
            DB::table('categories')->updateOrInsert(
                ['slug' => \Illuminate\Support\Str::slug($name)],
                ['name' => $name, 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
        Schema::dropIfExists('categories');
    }
};
