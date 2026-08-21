<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('service_type');
            $table->unsignedSmallInteger('units_per_day')->default(1);
            $table->unsignedInteger('daily_price');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('address')->nullable();
            $table->text('note')->nullable();
            $table->string('source')->default('admin');
            $table->string('status')->default('active');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('archived_at')->nullable()->index();
            $table->timestamps();

            $table->index(['start_date', 'end_date']);
        });

        Schema::create('service_order_animals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('animal_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label')->nullable();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_order_animals');
        Schema::dropIfExists('service_orders');
    }
};
