<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_bot_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_webhook_update_id')->nullable()->constrained()->nullOnDelete();
            $table->string('direction', 16)->index();
            $table->string('event', 64)->index();
            $table->string('chat_id', 32)->nullable()->index();
            $table->string('telegram_user_id', 32)->nullable()->index();
            $table->text('text')->nullable();
            $table->json('context')->nullable();
            $table->string('level', 16)->default('info')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_bot_logs');
    }
};
