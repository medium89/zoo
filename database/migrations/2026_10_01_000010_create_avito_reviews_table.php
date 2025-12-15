<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avito_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->dateTime('review_date')->nullable();
            $table->text('text')->nullable();
            $table->json('photos')->nullable();
            $table->string('status')->default('new');
            $table->string('source_hash')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avito_reviews');
    }
};

