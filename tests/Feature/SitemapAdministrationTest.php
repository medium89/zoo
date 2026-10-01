<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SitemapGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapAdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_an_article_rebuilds_the_sitemap(): void
    {
        $this->mock(SitemapGenerator::class, function ($mock): void {
            $mock->shouldReceive('rebuild')->once();
        });

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->post(route('admin.articles.store'), [
                'title' => 'Новая статья',
                'content' => 'Содержимое статьи',
                'active' => '1',
            ])
            ->assertRedirect(route('admin.articles.index'));
    }

    public function test_settings_show_sitemap_and_robots_management(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(route('admin.settings'))
            ->assertOk()
            ->assertSee('robots.txt')
            ->assertSee('sitemap.xml')
            ->assertSee(route('admin.settings.seo-files'), false)
            ->assertSee(route('admin.settings.sitemap.rebuild'), false);
    }
}