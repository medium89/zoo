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
            $table->string('dog_size')->nullable()->after('species');
        });

        DB::table('service_tariffs')->delete();
        $now = now();
        $rows = [
            'передержка' => ['cat' => 500, 'dog_small' => 800, 'dog_large' => 1000, 'small_pet' => 300, 'other' => 500],
            'выгул' => ['cat' => 500, 'dog_small' => 450, 'dog_large' => 500, 'small_pet' => 500, 'other' => 500],
            'уход' => ['cat' => 450, 'dog_small' => 450, 'dog_large' => 450, 'small_pet' => 350, 'other' => 500],
        ];
        foreach ($rows as $serviceType => $groups) {
            foreach ($groups as $animalGroup => $amount) {
                DB::table('service_tariffs')->insert([
                    'service_type' => $serviceType,
                    'animal_group' => $animalGroup,
                    'amount' => $amount,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropColumn('dog_size');
        });
    }
};
