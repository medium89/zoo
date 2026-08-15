<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boarding_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boarding_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->time('scheduled_time');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('boarding_task_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boarding_task_id')->constrained()->cascadeOnDelete();
            $table->date('notification_date');
            $table->string('status')->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->string('responded_by')->nullable();
            $table->timestamps();

            $table->unique(['boarding_task_id', 'notification_date']);
        });

        Schema::create('boarding_task_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boarding_task_run_id')->constrained()->cascadeOnDelete();
            $table->string('chat_id');
            $table->unsignedBigInteger('message_id');
            $table->timestamps();

            $table->unique(['boarding_task_run_id', 'chat_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boarding_task_messages');
        Schema::dropIfExists('boarding_task_runs');
        Schema::dropIfExists('boarding_tasks');
    }
};
