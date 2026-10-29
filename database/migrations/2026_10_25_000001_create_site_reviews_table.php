<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('site_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->text('text');
            $table->unsignedTinyInteger('rating');
            $table->string('status', 20)->default('new');
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_reviews');
    }
};
