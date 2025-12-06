<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('advantages', function (Blueprint $table) {
            $table->integer('order')->default(0)->after('text');
        });
        Schema::table('services', function (Blueprint $table) {
            $table->integer('order')->default(0)->after('text');
        });
    }

    public function down(): void
    {
        Schema::table('advantages', function (Blueprint $table) {
            $table->dropColumn('order');
        });
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('order');
        });
    }
};
