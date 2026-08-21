<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->foreignId('legacy_boarding_id')->nullable()->unique()->after('id')->constrained('boardings')->nullOnDelete();
        });

        Schema::create('service_order_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_order_id')->constrained()->cascadeOnDelete();
            $table->string('service_type');
            $table->unsignedSmallInteger('units_per_day')->default(1);
            $table->unsignedInteger('unit_price')->default(0);
            $table->timestamps();
            $table->unique(['service_order_id', 'service_type']);
        });

        DB::table('service_orders')->orderBy('id')->each(function (object $order): void {
            DB::table('service_order_services')->insert([
                'service_order_id' => $order->id,
                'service_type' => $order->service_type,
                'units_per_day' => $order->units_per_day ?: 1,
                'unit_price' => max(0, (int) floor(($order->daily_price ?: 0) / max(1, $order->units_per_day ?: 1))),
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
            ]);
        });

        DB::table('boardings')->orderBy('id')->each(function (object $boarding): void {
            $exists = DB::table('service_orders')->where('legacy_boarding_id', $boarding->id)->exists();
            if ($exists) {
                return;
            }

            $orderId = DB::table('service_orders')->insertGetId([
                'legacy_boarding_id' => $boarding->id,
                'client_id' => $boarding->client_id,
                'service_type' => $boarding->service_type,
                'units_per_day' => $boarding->units_per_day ?: 1,
                'daily_price' => max(0, (int) ($boarding->unit_price ?: 0)) * max(1, (int) ($boarding->units_per_day ?: 1)),
                'start_date' => $boarding->start_date,
                'end_date' => $boarding->end_date,
                'note' => $boarding->note ?: $boarding->description,
                'source' => $boarding->source ?: 'admin',
                'status' => $boarding->status ?: 'active',
                'confirmed_at' => $boarding->confirmed_at,
                'archived_at' => $boarding->archived_at,
                'created_at' => $boarding->created_at,
                'updated_at' => $boarding->updated_at,
            ]);

            DB::table('service_order_services')->insert([
                'service_order_id' => $orderId,
                'service_type' => $boarding->service_type,
                'units_per_day' => $boarding->units_per_day ?: 1,
                'unit_price' => $boarding->unit_price ?: 0,
                'created_at' => $boarding->created_at,
                'updated_at' => $boarding->updated_at,
            ]);

            $categoryId = $boarding->animal_id
                ? DB::table('animals')->where('id', $boarding->animal_id)->value('category_id')
                : null;
            DB::table('service_order_animals')->insert([
                'service_order_id' => $orderId,
                'animal_id' => $boarding->animal_id,
                'category_id' => $categoryId,
                'label' => $boarding->name,
                'quantity' => 1,
                'note' => $boarding->description,
                'created_at' => $boarding->created_at,
                'updated_at' => $boarding->updated_at,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_order_services');
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('legacy_boarding_id');
        });
    }
};
