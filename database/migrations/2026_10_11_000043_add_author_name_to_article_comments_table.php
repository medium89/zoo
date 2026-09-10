<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('article_comments', function (Blueprint $table) {
            $table->string('author_name', 100)->nullable()->after('email');
        });

        $toDisplayName = static function (string $email): string {
            $localPart = mb_strtolower(trim((string) strtok($email, '@')));
            $localPart = preg_replace('/[._+\-]+/u', ' ', $localPart) ?? '';
            $localPart = preg_replace('/\d+/u', '', $localPart) ?? '';
            $words = array_values(array_filter(
                preg_split('/\s+/u', trim($localPart)) ?: [],
                static fn (string $word): bool => mb_strlen($word) > 1
            ));

            if ($words === [] || in_array($words[0], ['admin', 'email', 'guest', 'info', 'mail', 'test', 'user'], true)) {
                return 'Гость';
            }

            return mb_convert_case(implode(' ', array_slice($words, 0, 2)), MB_CASE_TITLE, 'UTF-8');
        };

        DB::table('article_comments')->orderBy('id')->chunkById(100, function ($comments) use ($toDisplayName) {
            foreach ($comments as $comment) {
                DB::table('article_comments')->where('id', $comment->id)->update([
                    'author_name' => $toDisplayName((string) $comment->email),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('article_comments', function (Blueprint $table) {
            $table->dropColumn('author_name');
        });
    }
};
