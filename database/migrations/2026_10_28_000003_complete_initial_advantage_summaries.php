<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        DB::table('advantages')->select('id', 'text', 'short_text')->orderBy('id')->get()
            ->each(function ($advantage) {
                $plain = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($advantage->text ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
                $autoSummary = Str::words($plain, 26, '');
                if ($advantage->short_text !== $autoSummary) {
                    return;
                }

                $sentence = trim(preg_split('/(?<=[.!?])\s+/u', $plain, 2)[0] ?? '');
                if ($sentence !== '') {
                    DB::table('advantages')->where('id', $advantage->id)
                        ->update(['short_text' => $sentence]);
                }
            });
    }

    public function down(): void
    {
        // Existing short summaries may have been edited after deployment.
    }
};
