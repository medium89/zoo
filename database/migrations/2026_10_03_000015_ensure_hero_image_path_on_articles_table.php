<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('articles', 'hero_image_path')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->string('hero_image_path')->nullable()->after('cover_path');
            });

            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE `articles` MODIFY `hero_image_path` VARCHAR(255) NULL DEFAULT NULL');
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE articles ALTER COLUMN hero_image_path DROP NOT NULL');
            DB::statement('ALTER TABLE articles ALTER COLUMN hero_image_path DROP DEFAULT');
        }
    }

    public function down(): void
    {
        // Intentionally left empty: we cannot reliably restore the previous state,
        // because the column may already exist in older deployments.
    }
};
