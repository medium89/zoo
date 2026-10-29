<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('advantages', function (Blueprint $table) {
            $table->text('short_text')->nullable()->after('title');
        });

        DB::table('advantages')->select('id', 'text')->orderBy('id')->get()
            ->each(function ($advantage) {
                $plain = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($advantage->text ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
                DB::table('advantages')->where('id', $advantage->id)
                    ->update(['short_text' => Str::words($plain, 26, '')]);
            });
    }

    public function down(): void
    {
        Schema::table('advantages', function (Blueprint $table) {
            $table->dropColumn('short_text');
        });
    }
};
