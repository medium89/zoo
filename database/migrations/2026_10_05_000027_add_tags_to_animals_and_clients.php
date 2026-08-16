<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->json('tags')->nullable()->after('note');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->json('tags')->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropColumn('tags');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('tags');
        });
    }
};
