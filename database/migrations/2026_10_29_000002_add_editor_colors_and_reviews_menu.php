<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('sticker_color', 7)->nullable();
            $table->string('tape_color', 7)->nullable();
        });
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('advantages_bg_color', 7)->nullable();
        });
        if (!DB::table('nav_links')->where('key', 'reviews')->exists()) {
            DB::table('nav_links')->where('order', '>=', 6)->increment('order');
            DB::table('nav_links')->insert([
                'key' => 'reviews', 'label' => 'Отзывы', 'href' => '#reviews',
                'active' => true, 'order' => 6, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('nav_links')->where('key', 'reviews')->delete();
        Schema::table('site_settings', fn (Blueprint $table) => $table->dropColumn('advantages_bg_color'));
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['sticker_color', 'tape_color']);
        });
    }
};
