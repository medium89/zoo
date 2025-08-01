<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('socials', function (Blueprint $table) {
            $table->string('link_text')->default('')->after('link');
        });
    }

    public function down(): void
    {
        Schema::table('socials', function (Blueprint $table) {
            $table->dropColumn('link_text');
        });
    }
};
