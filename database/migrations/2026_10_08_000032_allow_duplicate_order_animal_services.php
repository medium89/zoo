<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_order_services', function (Blueprint $table) {
            $table->index('service_order_animal_id', 'sos_animal_id_index');
        });

        Schema::table('service_order_services', function (Blueprint $table) {
            $table->dropUnique('sos_animal_service_unique');
        });
    }

    public function down(): void
    {
        Schema::table('service_order_services', function (Blueprint $table) {
            $table->unique(['service_order_animal_id', 'service_type'], 'sos_animal_service_unique');
            $table->dropIndex('sos_animal_id_index');
        });
    }
};
