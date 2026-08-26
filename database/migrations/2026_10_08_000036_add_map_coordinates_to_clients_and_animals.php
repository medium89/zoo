<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->unsignedInteger('map_x')->nullable()->after('address');
            $table->unsignedInteger('map_y')->nullable()->after('map_x');
        });
        Schema::table('animals', function (Blueprint $table) {
            $table->unsignedInteger('map_x')->nullable()->after('order');
            $table->unsignedInteger('map_y')->nullable()->after('map_x');
        });
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) { $table->dropColumn(['map_x', 'map_y']); });
        Schema::table('clients', function (Blueprint $table) { $table->dropColumn(['map_x', 'map_y']); });
    }
};
