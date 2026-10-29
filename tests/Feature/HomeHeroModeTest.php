<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\Slider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeHeroModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        parent::setUp();
    }

    public function test_v2_is_the_main_page_and_original_is_preserved(): void
    {
        $this->get('/')->assertOk()->assertSee('v2-page');
        $this->get('/v1')->assertOk()->assertSee('noindex, follow')->assertSee('slider-container');
        $this->get('/v2')->assertRedirect('/');
    }

    public function test_admin_tabs_and_single_image_settings(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get(route('admin.sliders.index'))
            ->assertOk()
            ->assertSee('Показывать слайдер на главной')
            ->assertSee('Показывать единую картинку на главной');

        $this->post(route('admin.sliders.hero-image'), [
            'hero_text' => 'Забота о питомцах',
        ])->assertRedirect(route('admin.sliders.index', ['tab' => 'image']));
        $this->assertSame('Забота о питомцах', SiteSetting::first()->hero_text);
        $this->get('/')->assertSee('Забота о питомцах');
    }

    public function test_hero_mode_has_only_one_active_choice(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->post(route('admin.sliders.hero-mode'), ['mode' => 'slider'])
            ->assertSessionHasErrors('mode');
        $this->assertDatabaseCount('site_settings', 0);

        Slider::create([
            'image' => 'sliders/example.webp',
            'text' => 'Слайд для проверки',
            'position' => 'center',
            'order' => 1,
            'active' => true,
        ]);
        $this->post(route('admin.sliders.hero-mode'), ['mode' => 'slider'])
            ->assertRedirect(route('admin.sliders.index', ['tab' => 'slider']));
        $this->assertSame('slider', SiteSetting::first()->hero_mode);
        $this->get('/')->assertSee('data-hero-mode="slider"', false)
            ->assertSee('Слайд для проверки');

        $this->post(route('admin.sliders.hero-mode'), ['mode' => 'image'])
            ->assertRedirect(route('admin.sliders.index', ['tab' => 'image']));
        $this->assertSame('image', SiteSetting::first()->hero_mode);
        $this->get('/')->assertSee('data-hero-mode="image"', false);
    }
}
