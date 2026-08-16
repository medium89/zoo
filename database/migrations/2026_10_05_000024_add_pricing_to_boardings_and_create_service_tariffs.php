<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boardings', function (Blueprint $table) {
            $table->unsignedSmallInteger('units_per_day')->default(1)->after('service_type');
            $table->unsignedInteger('unit_price')->nullable()->after('units_per_day');
        });

        Schema::create('service_tariffs', function (Blueprint $table) {
            $table->id();
            $table->string('service_type');
            $table->string('animal_group');
            $table->unsignedInteger('amount');
            $table->timestamps();

            $table->unique(['service_type', 'animal_group']);
        });

        $now = now();
        DB::table('service_tariffs')->insert([
            ['service_type' => 'передержка', 'animal_group' => 'cat', 'amount' => 500, 'created_at' => $now, 'updated_at' => $now],
            ['service_type' => 'передержка', 'animal_group' => 'dog', 'amount' => 1000, 'created_at' => $now, 'updated_at' => $now],
            ['service_type' => 'передержка', 'animal_group' => 'other', 'amount' => 500, 'created_at' => $now, 'updated_at' => $now],
            ['service_type' => 'выгул', 'animal_group' => 'cat', 'amount' => 500, 'created_at' => $now, 'updated_at' => $now],
            ['service_type' => 'выгул', 'animal_group' => 'dog', 'amount' => 500, 'created_at' => $now, 'updated_at' => $now],
            ['service_type' => 'выгул', 'animal_group' => 'other', 'amount' => 500, 'created_at' => $now, 'updated_at' => $now],
            ['service_type' => 'уход', 'animal_group' => 'cat', 'amount' => 500, 'created_at' => $now, 'updated_at' => $now],
            ['service_type' => 'уход', 'animal_group' => 'dog', 'amount' => 500, 'created_at' => $now, 'updated_at' => $now],
            ['service_type' => 'уход', 'animal_group' => 'other', 'amount' => 500, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('service_tariffs');

        Schema::table('boardings', function (Blueprint $table) {
            $table->dropColumn(['units_per_day', 'unit_price']);
        });
    }
};
