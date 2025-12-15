<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avito_reviews', function (Blueprint $table) {
            $table->integer('order')->default(0)->after('status');
        });

        // Инициализируем порядок по id
        $rows = DB::table('avito_reviews')->orderBy('id')->get(['id']);
        $order = 1;
        foreach ($rows as $row) {
            DB::table('avito_reviews')->where('id', $row->id)->update(['order' => $order++]);
        }
    }

    public function down(): void
    {
        Schema::table('avito_reviews', function (Blueprint $table) {
            $table->dropColumn('order');
        });
    }
};

