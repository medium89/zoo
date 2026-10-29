<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ServiceStickerImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        parent::setUp();
        Storage::fake('public');
    }

    public function test_v2_image_can_be_replaced_without_changing_the_original_service_image(): void
    {
        $service = Service::create([
            'image' => 'services/original.webp',
            'sticker_image' => 'services/stickers/boarding.webp',
            'title' => 'Передержка',
            'text' => '<p>Уход за питомцем</p>',
            'active' => true,
            'order' => 1,
        ]);
        Storage::disk('public')->put('services/original.webp', 'old image');
        Storage::disk('public')->put('services/stickers/boarding.webp', 'old sticker');
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->put(route('admin.services.update', $service), [
            'title' => $service->title,
            'text' => $service->text,
            'active' => '1',
            'order' => '1',
            'sticker_image' => UploadedFile::fake()->createWithContent('sticker.svg', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 2 2"><circle cx="1" cy="1" r="1"/></svg>'),
        ])->assertRedirect(route('admin.services.index'));

        $updated = $service->fresh();
        $this->assertSame('services/original.webp', $updated->image);
        Storage::disk('public')->assertExists($updated->image);
        $this->assertStringStartsWith('services/stickers/', $updated->sticker_image);
        Storage::disk('public')->assertExists($updated->sticker_image);
        Storage::disk('public')->assertMissing('services/stickers/boarding.webp');

        $this->get('/')->assertOk()->assertSee('storage/'.$updated->sticker_image);
        $this->get('/v1')->assertOk()->assertSee('storage/services/original.webp');
        $this->get(route('admin.services.edit', $service))->assertOk()
            ->assertSee('storage/'.$updated->sticker_image)
            ->assertSee('storage/services/original.webp');

        $this->put(route('admin.services.update', $service), [
            'title' => $service->title,
            'text' => $service->text,
            'active' => '1',
            'order' => '1',
            'remove_sticker_image' => '1',
        ])->assertRedirect(route('admin.services.index'));

        $this->assertNull($service->fresh()->sticker_image);
        $this->assertSame('services/original.webp', $service->fresh()->image);
        Storage::disk('public')->assertMissing($updated->sticker_image);
        $this->get('/')->assertOk()->assertSee('images/image-placeholder.svg');
    }
}
