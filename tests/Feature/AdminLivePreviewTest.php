<?php

namespace Tests\Feature;

use App\Models\Advantage;
use App\Models\Service;
use App\Models\Slider;
use App\Models\Social;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLivePreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        parent::setUp();
    }

    public function test_site_forms_include_their_live_preview_column(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        foreach ([
            ['admin.sliders.create', 'slider'],
            ['admin.about.edit', 'about'],
            ['admin.advantages.create', 'advantage'],
            ['admin.services.create', 'service'],
            ['admin.galleries.create', 'gallery'],
            ['admin.socials.create', 'social'],
        ] as [$route, $kind]) {
            $this->get(route($route))
                ->assertOk()
                ->assertSee('class="admin-live-preview-layout"', false)
                ->assertSee('data-admin-live-preview="' . $kind . '"', false);
        }

        $this->get(route('admin.sliders.index'))->assertOk()->assertSee('data-admin-live-preview="hero_image"', false);

        $slider = Slider::create(['image' => 'sliders/test.webp', 'text' => 'Слайд', 'position' => 'center', 'order' => 1, 'active' => true]);
        $advantage = Advantage::create(['image' => 'advantages/test.webp', 'title' => 'Забота', 'text' => '<p>Полный текст</p>', 'order' => 1, 'active' => true]);
        $service = Service::create(['image' => 'services/test.webp', 'title' => 'Передержка', 'text' => '<p>Описание</p>', 'order' => 1, 'active' => true]);
        $social = Social::create(['icon' => 'fab fa-whatsapp', 'title' => 'WhatsApp', 'link' => 'https://wa.me/1', 'link_text' => 'Напишите', 'text' => '<p>Контакт</p>', 'order' => 1, 'active' => true]);

        foreach ([
            [route('admin.sliders.edit', $slider), 'slider'],
            [route('admin.advantages.edit', $advantage), 'advantage'],
            [route('admin.services.edit', $service), 'service'],
            [route('admin.socials.edit', $social), 'social'],
        ] as [$url, $kind]) {
            $this->get($url)
                ->assertOk()
                ->assertSee('data-admin-live-preview="' . $kind . '"', false);
        }
    }
}