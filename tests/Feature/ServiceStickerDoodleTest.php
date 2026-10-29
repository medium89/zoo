<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ServiceStickerDoodleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        parent::setUp();
        Storage::fake('public');
    }

    public function test_admin_can_replace_and_reset_a_gif_corner_image(): void
    {
        $service = Service::create([
            'image' => 'services/example.webp',
            'title' => 'Выгул собак',
            'text' => '<p>Уход за питомцем</p>',
            'tags' => [],
            'active' => true,
            'order' => 1,
        ]);
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->put(route('admin.services.update', $service), [
            'title' => $service->title,
            'text' => $service->text,
            'active' => '1',
            'order' => '1',
            'sticker_doodle' => UploadedFile::fake()->createWithContent('doodle.gif', base64_decode('R0lGODlhAQABAAD/ACwAAAAAAQABAAACAUwAOw==')),
        ])->assertRedirect(route('admin.services.index'));

        $custom = $service->fresh()->sticker_doodle;
        $this->assertStringStartsWith('services/sticker-doodles/', $custom);
        $this->assertStringEndsWith('.gif', $custom);
        Storage::disk('public')->assertExists($custom);
        $this->assertSame(base64_decode('R0lGODlhAQABAAD/ACwAAAAAAQABAAACAUwAOw=='), Storage::disk('public')->get($custom));
        $this->get('/')->assertOk()->assertSee('storage/' . $custom);

        $this->put(route('admin.services.update', $service), [
            'title' => $service->title,
            'text' => $service->text,
            'active' => '1',
            'order' => '1',
            'remove_sticker_doodle' => '1',
        ])->assertRedirect(route('admin.services.index'));

        $this->assertNull($service->fresh()->sticker_doodle);
        Storage::disk('public')->assertMissing($custom);
        $this->get('/')->assertOk()->assertSee('images/v2/services/paw-trail.svg');
    }
}
