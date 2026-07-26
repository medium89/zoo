<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if ($this->indexExists('animals', 'animals_name_unique')) {
            Schema::table('animals', function (Blueprint $table) {
                $table->dropUnique('animals_name_unique');
            });
        }

        if (!Schema::hasColumn('animals', 'client_id')) {
            Schema::table('animals', function (Blueprint $table) {
                $table->foreignId('client_id')->nullable()->after('id')->constrained()->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('animals', 'species')) {
            Schema::table('animals', function (Blueprint $table) {
                $table->string('species')->nullable()->after('name');
            });
        }

        if (!Schema::hasColumn('animals', 'note')) {
            Schema::table('animals', function (Blueprint $table) {
                $table->text('note')->nullable()->after('description');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('animals', 'client_id')) {
            Schema::table('animals', function (Blueprint $table) {
                $table->dropForeign(['client_id']);
            });
        }

        Schema::table('animals', function (Blueprint $table) {
            foreach (['client_id', 'species', 'note'] as $column) {
                if (Schema::hasColumn('animals', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if (!$this->indexExists('animals', 'animals_name_unique')) {
            Schema::table('animals', function (Blueprint $table) {
                $table->unique('name');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return collect(DB::select("SHOW INDEX FROM `{$table}`"))
            ->contains(fn ($row): bool => ($row->Key_name ?? null) === $index);
    }
};
