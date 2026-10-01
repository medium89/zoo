<?php

namespace App\Services;

use App\Models\Article;
use Illuminate\Support\Facades\File;

class SitemapGenerator
{
    public function rebuild(): void
    {
        File::put(public_path('sitemap.xml'), $this->generate());
    }

    public function generate(): string
    {
        $urls = collect([
            ['loc' => url('/')],
            ['loc' => route('calendar.index')],
            ['loc' => route('articles.index')],
        ])->merge(
            Article::query()
                ->where('active', true)
                ->whereNotNull('slug')
                ->orderBy('id')
                ->get(['slug', 'updated_at'])
                ->map(fn (Article $article) => [
                    'loc' => route('articles.show', $article),
                    'lastmod' => $article->updated_at?->toDateString(),
                ])
        );

        $items = $urls->map(function (array $item): string {
            $location = htmlspecialchars($item['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $lastModified = isset($item['lastmod'])
                ? "\n    <lastmod>{$item['lastmod']}</lastmod>"
                : '';

            return "  <url>\n    <loc>{$location}</loc>{$lastModified}\n  </url>";
        })->implode("\n");

        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n{$items}\n</urlset>\n";
    }
}