<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pets', function (Blueprint $table) {
            $table->string('owner_name')->nullable()->after('name');
            $table->string('owner_phone')->nullable()->after('owner_name');
            $table->string('animal_type')->nullable()->after('owner_phone');
            $table->json('services')->nullable()->after('animal_type');
        });
    }

    public function down(): void
    {
        Schema::table('pets', function (Blueprint $table) {
            $table->dropColumn(['owner_name','owner_phone','animal_type','services']);
        });
    }
};

