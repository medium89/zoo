<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
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

        return response(
            "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n{$items}\n</urlset>\n",
            200,
            ['Content-Type' => 'application/xml; charset=UTF-8']
        );
    }
}