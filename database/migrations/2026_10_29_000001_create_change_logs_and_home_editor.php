<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('change_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('route', 180)->nullable();
            $table->string('method', 12);
            $table->string('summary', 255);
            $table->longText('changes');
            $table->longText('media_backups')->nullable();
            $table->timestamp('reverted_at')->nullable();
            $table->unsignedBigInteger('reverted_by')->nullable();
            $table->timestamps();
        });
        Schema::create('homepage_orders', function (Blueprint $table) {
            $table->id();
            $table->string('section', 40);
            $table->string('item_key', 90);
            $table->unsignedInteger('position');
            $table->timestamps();
            $table->unique(['section', 'item_key']);
        });
        Schema::table('site_settings', function (Blueprint $table) {
            $table->json('home_labels')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('home_labels');
        });
        Schema::dropIfExists('homepage_orders');
        Schema::dropIfExists('change_logs');
    }
};
