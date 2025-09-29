<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pet_calendar_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pet_id')->constrained('pets')->onDelete('cascade');
            $table->date('date');
            $table->string('service');
            $table->enum('slot', ['утро','день','вечер']);
            $table->timestamps();
            $table->unique(['pet_id','date','service','slot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pet_calendar_entries');
    }
};

