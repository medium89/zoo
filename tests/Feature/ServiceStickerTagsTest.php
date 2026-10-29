<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceStickerTagsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        parent::setUp();
    }

    public function test_multiple_tags_are_saved_and_shown_when_editing_again(): void
    {
        $service = Service::create([
            'image' => 'services/example.webp',
            'title' => 'Уход за кошками',
            'text' => '<p>Текст услуги</p>',
            'active' => true,
            'order' => 1,
        ]);
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->put(route('admin.services.update', $service), [
            'title' => $service->title,
            'text' => $service->text,
            'tags_text' => "Первый тег\r\nВторой тег",
            'active' => '1',
            'order' => '1',
        ])->assertRedirect(route('admin.services.index'));

        $this->assertSame(['Первый тег', 'Второй тег'], $service->fresh()->tags);
        $this->get(route('admin.services.edit', $service))
            ->assertOk()
            ->assertSee('Первый тег')
            ->assertSee('Второй тег');
    }

    public function test_reordered_tags_keep_their_order_on_the_homepage(): void
    {
        $service = Service::create([
            'image' => 'services/example.webp',
            'title' => 'Тестовая услуга',
            'text' => '<p>Описание услуги</p>',
            'active' => true,
            'order' => 1,
        ]);
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->put(route('admin.services.update', $service), [
            'title' => $service->title,
            'text' => $service->text,
            'tags_text' => "Тег третий\nТег первый\nТег второй",
            'active' => '1',
            'order' => '1',
        ])->assertRedirect(route('admin.services.index'));

        $this->assertSame(['Тег третий', 'Тег первый', 'Тег второй'], $service->fresh()->tags);
        $this->get('/')->assertOk()->assertSeeInOrder(['Тег третий', 'Тег первый', 'Тег второй']);
    }
}
