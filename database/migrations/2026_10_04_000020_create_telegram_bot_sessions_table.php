<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_bot_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('telegram_user_id')->index();
            $table->unsignedBigInteger('chat_id')->index();
            $table->string('state');
            $table->json('payload')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_bot_sessions');
    }
};
