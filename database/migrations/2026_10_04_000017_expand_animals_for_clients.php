<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropUnique('animals_name_unique');
            $table->foreignId('client_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('species')->nullable()->after('name');
            $table->text('note')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn(['client_id', 'species', 'note']);
            $table->unique('name');
        });
    }
};
