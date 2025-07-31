<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['sliders', 'advantages', 'services', 'galleries', 'socials'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->boolean('active')->default(true);
            });
        }
    }

    public function down(): void
    {
        foreach (['sliders', 'advantages', 'services', 'galleries', 'socials'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('active');
            });
        }
    }
};
