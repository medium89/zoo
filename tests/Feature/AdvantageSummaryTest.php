<?php

namespace Tests\Feature;

use App\Models\Advantage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvantageSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        parent::setUp();
    }

    public function test_short_and_full_advantage_texts_are_edited_and_rendered_separately(): void
    {
        $advantage = Advantage::create([
            'image' => 'advantages/example.webp',
            'title' => 'Забота',
            'short_text' => '<p>Короткое описание</p>',
            'text' => '<p>Полное исходное описание</p>',
            'active' => true,
            'order' => 1,
        ]);

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->put(route('admin.advantages.update', $advantage), [
                'title' => 'Забота',
                'short_text' => '<p>Новая короткая версия</p>',
                'text' => '<p>Полная версия с подробностями</p>',
                'active' => '1',
                'order' => '1',
            ])->assertRedirect(route('admin.advantages.index'));

        $this->assertSame('<p>Новая короткая версия</p>', $advantage->fresh()->short_text);
        $this->assertSame('<p>Полная версия с подробностями</p>', $advantage->fresh()->text);

        $this->get('/')->assertOk()
            ->assertSee('<p>Новая короткая версия</p>', false)
            ->assertSee('v2-advantage__full')->assertSee('<p>Полная версия с подробностями</p>', false);
        $this->get('/v1')->assertOk()->assertSee('<p>Полная версия с подробностями</p>', false);
    }
}
