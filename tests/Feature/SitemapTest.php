<?php

namespace Tests\Feature;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_lists_public_pages_and_active_articles_only(): void
    {
        Article::create([
            'title' => 'Опубликованная статья',
            'slug' => 'published-article',
            'content' => 'Текст',
            'active' => true,
        ]);
        Article::create([
            'title' => 'Черновик',
            'slug' => 'draft-article',
            'content' => 'Текст',
            'active' => false,
        ]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(url('/'), false)
            ->assertSee(route('calendar.index'), false)
            ->assertSee(route('articles.index'), false)
            ->assertDontSee(url('/v2'), false)
            ->assertSee(route('articles.show', ['article' => 'published-article']), false)
            ->assertDontSee('draft-article')
            ->assertDontSee('zooadmin');
    }
}