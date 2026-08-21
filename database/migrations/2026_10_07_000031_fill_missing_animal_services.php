<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('service_order_animals')->orderBy('id')->each(function (object $animal): void {
            $hasService = DB::table('service_order_services')->where('service_order_animal_id', $animal->id)->exists();
            if ($hasService) {
                return;
            }

            $template = DB::table('service_order_services')->where('service_order_id', $animal->service_order_id)->orderBy('id')->first();
            if (!$template) {
                return;
            }

            DB::table('service_order_services')->insert([
                'service_order_id' => $animal->service_order_id,
                'service_order_animal_id' => $animal->id,
                'service_type' => $template->service_type,
                'units_per_day' => $template->units_per_day,
                'unit_price' => $template->unit_price,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        // This data repair is intentionally retained on rollback to avoid deleting real work.
    }
};
