<?php

use App\Models\Animal;
use App\Models\Boarding;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boardings', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('animal_id')->nullable()->after('client_id')->constrained()->nullOnDelete();
            $table->string('source')->default('admin')->after('service_type');
            $table->string('status')->default('active')->after('source');
            $table->text('note')->nullable()->after('end_date');
            $table->timestamp('confirmed_at')->nullable()->after('note');
        });

        Boarding::query()
            ->whereNull('animal_id')
            ->orderBy('id')
            ->chunkById(100, function ($boardings) {
                foreach ($boardings as $boarding) {
                    $animal = Animal::whereRaw('LOWER(name) = ?', [mb_strtolower($boarding->name)])->first();

                    if (!$animal) {
                        $animal = Animal::create([
                            'name' => $boarding->name,
                            'description' => $boarding->description,
                        ]);
                    }

                    $boarding->animal_id = $animal->id;
                    $boarding->client_id = $animal->client_id;
                    $boarding->save();
                }
            });
    }

    public function down(): void
    {
        Schema::table('boardings', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropForeign(['animal_id']);
            $table->dropColumn(['client_id', 'animal_id', 'source', 'status', 'note', 'confirmed_at']);
        });
    }
};
