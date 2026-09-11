<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->uniqueNameIndexes() as $index) {
            if (DB::connection()->getDriverName() === 'sqlite') {
                Schema::table('animals', function (Blueprint $table) use ($index) {
                    $table->dropUnique($index);
                });

                continue;
            }

            $quote = chr(96);
            $escapedIndex = str_replace($quote, $quote.$quote, $index);
            DB::statement("ALTER TABLE {$quote}animals{$quote} DROP INDEX {$quote}{$escapedIndex}{$quote}");
        }
    }

    public function down(): void
    {
        $duplicatesExist = DB::table('animals')
            ->select('name')
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if (! $duplicatesExist && $this->uniqueNameIndexes() === []) {
            Schema::table('animals', function (Blueprint $table) {
                $table->unique('name');
            });
        }
    }

    /** @return array<int, string> */
    private function uniqueNameIndexes(): array
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('animals')");

            return collect($indexes)
                ->filter(fn (object $index): bool => (bool) ($index->unique ?? false))
                ->filter(function (object $index): bool {
                    $name = str_replace("'", "''", (string) $index->name);
                    $columns = collect(DB::select("PRAGMA index_info('{$name}')"))
                        ->pluck('name')
                        ->values()
                        ->all();

                    return $columns === ['name'];
                })
                ->pluck('name')
                ->values()
                ->all();
        }

        return collect(DB::select('SHOW INDEX FROM animals'))
            ->filter(fn (object $index): bool => (int) $index->Non_unique === 0 && $index->Key_name !== 'PRIMARY')
            ->groupBy('Key_name')
            ->filter(fn ($indexes): bool => $indexes->pluck('Column_name')->values()->all() === ['name'])
            ->keys()
            ->values()
            ->all();
    }
};
