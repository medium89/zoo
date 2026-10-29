<?php

namespace Tests\Feature;

use App\Models\Advantage;
use App\Models\AvitoReview;
use App\Models\SiteReview;
use App\Models\ChangeLog;
use App\Models\HomepageOrder;
use App\Models\NavLink;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VisualEditorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        parent::setUp();
    }

    public function test_only_admin_can_see_and_use_editor(): void
    {
        $this->get('/')->assertOk()->assertDontSee('id="visualEditor"', false);
        $this->get(route('admin.visual.field', ['type' => 'label', 'field' => 'home_label', 'key' => 'about']))->assertRedirect();
        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->get('/')->assertOk()->assertDontSee('id="visualEditor"', false);
        $this->get(route('admin.visual.field', ['type' => 'label', 'field' => 'home_label', 'key' => 'about']))->assertForbidden();
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get('/?edit=1')->assertOk()->assertSee('id="visualEditor"', false);
    }

    public function test_field_edit_is_logged_and_can_be_reverted(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $advantage = Advantage::create(['image' => 'advantages/test.webp', 'title' => 'Старый заголовок', 'text' => '<p>Исходный текст</p>', 'short_text' => '<p>Коротко</p>', 'active' => true, 'order' => 1]);
        $this->actingAs($admin);
        $version = $this->getJson(route('admin.visual.field', ['type' => 'advantage', 'id' => $advantage->id, 'field' => 'text']))
            ->assertOk()->json('version');
        $this->patchJson(route('admin.visual.field.save'), [
            'type' => 'advantage', 'id' => $advantage->id, 'field' => 'text',
            'value' => '<p><strong>Новый текст</strong></p>', 'version' => $version,
        ])->assertOk();
        $this->assertSame('<p><strong>Новый текст</strong></p>', $advantage->fresh()->text);
        $log = ChangeLog::latest('id')->firstOrFail();
        $this->assertSame('admin.visual.field.save', $log->route);
        $this->post(route('admin.logs.revert', $log))->assertRedirect();
        $this->assertSame('<p>Исходный текст</p>', $advantage->fresh()->text);
        $this->assertNotNull($log->fresh()->reverted_at);
    }

    public function test_revert_refuses_to_erase_later_edits(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        SiteSetting::create(['hero_text' => 'Первый вариант']);
        $url = route('admin.visual.field.save');
        $version = $this->getJson(route('admin.visual.field', ['type' => 'settings', 'field' => 'hero_text']))->json('version');
        $this->patchJson($url, ['type' => 'settings', 'field' => 'hero_text', 'value' => 'Второй вариант', 'version' => $version])->assertOk();
        $firstLog = ChangeLog::latest('id')->firstOrFail();
        $this->patchJson($url, ['type' => 'settings', 'field' => 'hero_text', 'value' => 'Третий вариант', 'version' => $this->getJson(route('admin.visual.field', ['type' => 'settings', 'field' => 'hero_text']))->json('version')])->assertOk();
        $this->post(route('admin.logs.revert', $firstLog))->assertRedirect();
        $this->assertSame('Третий вариант', SiteSetting::first()->hero_text);
        $this->assertNull($firstLog->fresh()->reverted_at);
    }

    public function test_reordering_services_and_reviews_persists(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $a = Service::create(['image' => 'services/a.webp', 'title' => 'Первая услуга', 'text' => '<p>Одна</p>', 'active' => true, 'order' => 1]);
        $b = Service::create(['image' => 'services/b.webp', 'title' => 'Вторая услуга', 'text' => '<p>Две</p>', 'active' => true, 'order' => 2]);
        $this->postJson(route('admin.visual.reorder'), ['orders' => ['services' => [(string) $b->id, (string) $a->id]]])->assertOk();
        $this->assertSame([$b->id, $a->id], Service::orderBy('order')->pluck('id')->all());
        $this->assertDatabaseHas('change_logs', ['route' => 'admin.visual.reorder']);
        $this->assertSame(0, HomepageOrder::count());
    }
    public function test_multiple_groups_can_be_sorted_and_review_order_is_rendered(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $a = Service::create(['image' => 'services/a.webp', 'title' => 'Услуга А', 'text' => '<p>А</p>', 'active' => true, 'order' => 1]);
        $b = Service::create(['image' => 'services/b.webp', 'title' => 'Услуга Б', 'text' => '<p>Б</p>', 'active' => true, 'order' => 2]);
        $one = Advantage::create(['image' => 'advantages/a.webp', 'title' => 'Преимущество А', 'text' => '<p>А</p>', 'active' => true, 'order' => 1]);
        $two = Advantage::create(['image' => 'advantages/b.webp', 'title' => 'Преимущество Б', 'text' => '<p>Б</p>', 'active' => true, 'order' => 2]);
        $this->postJson(route('admin.visual.reorder'), ['orders' => [
            'services' => [(string) $b->id, (string) $a->id],
            'advantages' => [(string) $two->id, (string) $one->id],
        ]])->assertOk();
        $this->assertSame([$b->id, $a->id], Service::orderBy('order')->pluck('id')->all());
        $this->assertSame([$two->id, $one->id], Advantage::orderBy('order')->pluck('id')->all());

        $avito = AvitoReview::create(['name' => 'Авито автор', 'text' => 'Отзыв Авито', 'status' => 'published', 'source_hash' => hash('sha256', 'avito')]);
        $site = SiteReview::create(['name' => 'Сайт автор', 'text' => 'Отзыв сайта', 'rating' => 5, 'status' => 'published']);
        $this->postJson(route('admin.visual.reorder'), ['orders' => ['reviews' => ['site:' . $site->id, 'avito:' . $avito->id]]])->assertOk();
        $this->get('/')->assertOk()->assertSeeInOrder(['Сайт автор', 'Авито автор']);
    }
    public function test_deleted_service_image_is_backed_up_and_restored(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $service = Service::create([
            'image' => 'services/main.webp', 'sticker_image' => 'services/stickers/old.webp',
            'title' => 'Передержка', 'text' => '<p>Уход</p>', 'active' => true, 'order' => 1,
        ]);
        Storage::disk('public')->put('services/stickers/old.webp', 'image bytes');
        $this->put(route('admin.services.update', $service), [
            'title' => 'Передержка', 'text' => '<p>Уход</p>', 'active' => '1', 'order' => '1',
            'remove_sticker_image' => '1',
        ])->assertRedirect();
        Storage::disk('public')->assertMissing('services/stickers/old.webp');
        $log = ChangeLog::latest('id')->firstOrFail();
        $this->assertArrayHasKey('services/stickers/old.webp', $log->media_backups);
        $this->post(route('admin.logs.revert', $log))->assertRedirect();
        $this->assertSame('services/stickers/old.webp', $service->fresh()->sticker_image);
        $this->assertSame('image bytes', Storage::disk('public')->get('services/stickers/old.webp'));
    }
    public function test_menu_can_be_edited_created_and_reordered_from_editor(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $about = NavLink::where('key', 'about')->firstOrFail();
        $version = $this->getJson(route('admin.visual.field', ['type' => 'nav_link', 'id' => $about->id, 'field' => 'label']))->assertOk()->json('version');
        $this->patchJson(route('admin.visual.field.save'), ['type' => 'nav_link', 'id' => $about->id, 'field' => 'label', 'value' => 'О Екатерине', 'version' => $version])->assertOk();
        $this->get('/')->assertOk()->assertSee('О Екатерине');
        $this->postJson(route('admin.visual.nav-link.create'), ['label' => 'Доставка', 'href' => '/delivery'])->assertCreated();
        $this->assertDatabaseHas('nav_links', ['label' => 'Доставка', 'href' => '/delivery']);
        $version = $this->getJson(route('admin.visual.field', ['type' => 'nav_link', 'id' => $about->id, 'field' => 'href']))->json('version');
        $this->patchJson(route('admin.visual.field.save'), ['type' => 'nav_link', 'id' => $about->id, 'field' => 'href', 'value' => 'javascript:alert(1)', 'version' => $version])->assertUnprocessable();
        $links = NavLink::where('active', true)->orderByDesc('order')->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->postJson(route('admin.visual.reorder'), ['orders' => ['nav_links' => $links]])->assertOk();
        $this->assertSame((int) $links[0], NavLink::orderBy('order')->first()->id);
    }

    public function test_service_and_advantage_colors_are_independent(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $service = Service::create(['image' => 'services/a.webp', 'title' => 'Передержка', 'text' => '<p>Уход</p>', 'active' => true, 'order' => 1]);
        $this->put(route('admin.services.update', $service), [
            'title' => 'Передержка', 'text' => '<p>Уход</p>', 'active' => '1', 'order' => '1',
            'sticker_color' => '#c1d2e3', 'tape_color' => '#fca123',
            'custom_sticker_color' => '1', 'custom_tape_color' => '1',
        ])->assertRedirect();
        $this->assertSame('#c1d2e3', $service->fresh()->sticker_color);
        $this->assertSame('#fca123', $service->fresh()->tape_color);
        $this->post(route('admin.advantages.background'), ['advantages_bg_color' => '#543278'])->assertRedirect();
        $this->assertSame('#543278', SiteSetting::first()->advantages_bg_color);
        $this->get('/')->assertOk()->assertSee('--sticker-base:#c1d2e3', false)
            ->assertSee('--tape-override:#fca123', false)->assertSee('--advantages-bg:#543278', false);
    }

    public function test_image_replacement_appears_on_page_and_can_be_undone(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $service = Service::create(['image' => 'services/main.webp', 'sticker_image' => 'services/stickers/old.svg', 'title' => 'Передержка', 'text' => '<p>Уход</p>', 'active' => true, 'order' => 1]);
        Storage::disk('public')->put('services/stickers/old.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>');
        $this->post(route('admin.visual.image.save'), [
            'type' => 'service', 'id' => $service->id, 'field' => 'sticker_image',
            'image' => UploadedFile::fake()->createWithContent('new.svg', '<svg xmlns="http://www.w3.org/2000/svg"><circle r="1"/></svg>'),
        ])->assertOk();
        $newPath = $service->fresh()->sticker_image;
        $this->assertNotSame('services/stickers/old.svg', $newPath);
        Storage::disk('public')->assertExists($newPath);
        Storage::disk('public')->assertMissing('services/stickers/old.svg');
        $log = ChangeLog::latest('id')->firstOrFail();
        $this->post(route('admin.logs.revert', $log))->assertRedirect();
        $this->assertSame('services/stickers/old.svg', $service->fresh()->sticker_image);
        Storage::disk('public')->assertExists('services/stickers/old.svg');
    }
    public function test_color_forms_render(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $service = Service::create(['image' => 'services/a.webp', 'title' => 'Услуга', 'text' => '<p>Текст</p>', 'active' => true, 'order' => 1]);
        $this->get(route('admin.services.create'))->assertOk()->assertSee('Фон стикера');
        $this->get(route('admin.services.edit', $service))->assertOk()->assertSee('Липучка');
        $this->get(route('admin.advantages.index'))->assertOk()->assertSee('Фон секции преимуществ');
    }
}
