<?php

use App\Models\Article;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('id');
            $table->string('seo_title')->nullable()->after('title');
            $table->string('seo_description')->nullable()->after('seo_title');
            $table->string('seo_robots')->default('index, follow')->after('published_at');
            $table->string('seo_charset')->default('UTF-8')->after('seo_robots');
        });

        $this->populateSlugs();
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['slug', 'seo_title', 'seo_description', 'seo_robots', 'seo_charset']);
        });
    }

    private function populateSlugs(): void
    {
        Article::query()->whereNull('slug')->orWhere('slug', '')->chunk(100, function ($articles) {
            foreach ($articles as $article) {
                $base = Str::slug($article->title) ?: 'article-' . $article->id;
                $slug = $base;
                $i = 1;
                while (Article::where('slug', $slug)->where('id', '!=', $article->id)->exists()) {
                    $slug = $base . '-' . $i++;
                }
                $article->slug = $slug;
                if (!$article->seo_robots) {
                    $article->seo_robots = 'index, follow';
                }
                if (!$article->seo_charset) {
                    $article->seo_charset = 'UTF-8';
                }
                $article->saveQuietly();
            }
        });
    }
};
