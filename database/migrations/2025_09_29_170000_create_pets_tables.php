<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('service_type')->default('передержка');
            $table->text('description')->nullable();
            $table->json('pluses')->nullable();
            $table->json('minuses')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('pet_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pet_id')->constrained('pets')->onDelete('cascade');
            $table->string('path');
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pet_photos');
        Schema::dropIfExists('pets');
    }
};

