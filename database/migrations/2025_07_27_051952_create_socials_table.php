<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('socials', function (Blueprint $table) {
            $table->id();
            $table->string('icon');
            $table->string('title');
            $table->string('link');
            $table->string('text');
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('socials');
    }
}; 