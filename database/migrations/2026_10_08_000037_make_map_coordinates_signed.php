<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE clients MODIFY map_x INT NULL, MODIFY map_y INT NULL');
        DB::statement('ALTER TABLE animals MODIFY map_x INT NULL, MODIFY map_y INT NULL');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE clients MODIFY map_x INT UNSIGNED NULL, MODIFY map_y INT UNSIGNED NULL');
        DB::statement('ALTER TABLE animals MODIFY map_x INT UNSIGNED NULL, MODIFY map_y INT UNSIGNED NULL');
    }
};
