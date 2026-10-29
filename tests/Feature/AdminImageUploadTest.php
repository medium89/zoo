<?php

namespace Tests\Feature;

use App\Models\Advantage;
use App\Models\Client;
use App\Models\Service;
use App\Models\Slider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminImageUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        parent::setUp();
        Storage::fake('public');
        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    public function test_primary_images_can_be_removed_without_breaking_public_blocks(): void
    {
        foreach (['services/old.webp', 'advantages/old.webp', 'sliders/old.webp'] as $path) {
            Storage::disk('public')->put($path, 'image');
        }
        $service = Service::create([
            'image' => 'services/old.webp', 'title' => 'Уход за кошками',
            'text' => '<p>Описание</p>', 'active' => true, 'order' => 1,
        ]);
        $advantage = Advantage::create([
            'image' => 'advantages/old.webp', 'title' => 'Забота',
            'text' => '<p>Описание</p>', 'active' => true, 'order' => 1,
        ]);
        $slider = Slider::create([
            'image' => 'sliders/old.webp', 'text' => 'Текст',
            'position' => 'center', 'active' => true, 'order' => 1,
        ]);

        $this->put(route('admin.services.update', $service), [
            'title' => $service->title, 'text' => $service->text,
            'active' => '1', 'remove_image' => '1',
        ])->assertRedirect(route('admin.services.index'));
        $this->put(route('admin.advantages.update', $advantage), [
            'title' => $advantage->title, 'text' => $advantage->text,
            'active' => '1', 'remove_image' => '1',
        ])->assertRedirect(route('admin.advantages.index'));
        $this->put(route('admin.sliders.update', $slider), [
            'position' => 'center', 'active' => '1', 'order' => '1', 'remove_image' => '1',
        ])->assertRedirect(route('admin.sliders.index'));

        $this->assertSame('', $service->fresh()->image);
        $this->assertSame('', $advantage->fresh()->image);
        $this->assertSame('', $slider->fresh()->image);
        Storage::disk('public')->assertMissing('services/old.webp');
        Storage::disk('public')->assertMissing('advantages/old.webp');
        Storage::disk('public')->assertMissing('sliders/old.webp');
        $this->assertStringContainsString('images/image-placeholder.svg', view('sections.services', ['services' => collect([$service->fresh()])])->render());
        $this->assertStringContainsString('images/image-placeholder.svg', view('sections.advantages', ['advantages' => collect([$advantage->fresh()])])->render());
        $this->assertStringContainsString('images/image-placeholder.svg', view('sections.slider', ['sliders' => collect([$slider->fresh()])])->render());
    }

    public function test_client_photo_cross_deletes_only_its_own_photo(): void
    {
        $client = Client::create(['name' => 'Клиент']);
        $photo = $client->photos()->create(['path' => 'clients/photo.webp']);
        Storage::disk('public')->put($photo->path, 'image');

        $this->delete(route('admin.clients.photos.destroy', [$client, $photo]))->assertRedirect();
        $this->assertDatabaseMissing('client_photos', ['id' => $photo->id]);
        Storage::disk('public')->assertMissing('clients/photo.webp');
    }
}
