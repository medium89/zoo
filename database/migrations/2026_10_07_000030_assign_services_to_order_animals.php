<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('service_order_services', 'service_order_animal_id')) {
            Schema::table('service_order_services', function (Blueprint $table) {
                $table->foreignId('service_order_animal_id')->nullable()->after('service_order_id')->constrained()->cascadeOnDelete();
            });
        }

        $indexes = collect(DB::select('SHOW INDEX FROM service_order_services'))->pluck('Key_name');
        if (!$indexes->contains('service_order_services_order_id_index')) {
            Schema::table('service_order_services', function (Blueprint $table) {
                $table->index('service_order_id', 'service_order_services_order_id_index');
            });
        }
        if ($indexes->contains('service_order_services_service_order_id_service_type_unique')) {
            Schema::table('service_order_services', function (Blueprint $table) {
                $table->dropUnique('service_order_services_service_order_id_service_type_unique');
            });
        }

        DB::table('service_order_services')->orderBy('id')->each(function (object $service): void {
            $animalId = DB::table('service_order_animals')->where('service_order_id', $service->service_order_id)->orderBy('id')->value('id');
            if ($animalId) {
                DB::table('service_order_services')->where('id', $service->id)->update(['service_order_animal_id' => $animalId]);
            }
        });

        $indexes = collect(DB::select('SHOW INDEX FROM service_order_services'))->pluck('Key_name');
        if (!$indexes->contains('sos_animal_service_unique')) {
            Schema::table('service_order_services', function (Blueprint $table) {
                $table->unique(['service_order_animal_id', 'service_type'], 'sos_animal_service_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('service_order_services', function (Blueprint $table) {
            $table->dropUnique('sos_animal_service_unique');
            $table->dropConstrainedForeignId('service_order_animal_id');
            $table->unique(['service_order_id', 'service_type']);
        });
    }
};
