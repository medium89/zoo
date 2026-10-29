<?php

namespace Tests\Feature;

use App\Models\AvitoReview;
use App\Models\SiteReview;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SiteReviewFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        parent::setUp();
        SiteSetting::create(['personal_data_consent_text' => '<p>Согласие на обработку данных</p>']);
    }

    public function test_review_is_moderated_and_telegram_is_notified(): void
    {
        config([
            'services.recaptcha.secret' => 'test-secret',
            'services.telegram.bot_token' => 'test-token',
            'services.telegram.chat_ids' => ['123'],
        ]);
        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true]),
            'https://api.telegram.org/*' => Http::response(['ok' => true]),
        ]);

        $this->from('/v2')->post(route('site-reviews.store'), [
            'name' => 'Мария',
            'text' => 'Отличный уход за питомцем, спасибо!',
            'rating' => '5',
            'personal_data_consent' => '1',
            'g-recaptcha-response' => 'test-response',
            'website' => '',
        ])->assertRedirect('/v2');

        $review = SiteReview::firstOrFail();
        $this->assertSame('new', $review->status);
        $this->assertSame(5, $review->rating);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && str_contains((string) $request['text'], 'Новый отзыв с сайта'));

        $this->get('/v2')->assertDontSee('Отличный уход за питомцем, спасибо!');

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);
        $this->get(route('admin.site-reviews.index'))->assertOk()->assertSee('Отличный уход за питомцем');
        $this->patch(route('admin.site-reviews.status', $review), ['status' => 'published'])->assertRedirect();

        $this->get('/')->assertOk()->assertSee('Отличный уход за питомцем, спасибо!');
    }

    public function test_avito_original_link_is_only_shown_when_stored(): void
    {
        AvitoReview::create([
            'name' => 'Ксения',
            'text' => 'Первый отзыв с Avito',
            'status' => 'published',
            'source_hash' => hash('sha256', 'first'),
        ]);
        AvitoReview::create([
            'name' => 'Софья',
            'text' => 'Второй отзыв с Avito',
            'status' => 'published',
            'source_hash' => hash('sha256', 'second'),
            'original_url' => 'https://www.avito.ru/example/review/123',
        ]);

        $response = $this->get('/');
        $response->assertOk()->assertSee('https://www.avito.ru/example/review/123');
        $this->assertSame(1, substr_count($response->getContent(), 'Оригинал на Avito'));
    }

    public function test_admin_can_save_direct_avito_review_url(): void
    {
        $review = AvitoReview::create([
            'name' => 'Ксения',
            'text' => 'Отзыв',
            'status' => 'published',
            'source_hash' => hash('sha256', 'editable'),
        ]);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->put(route('admin.avito-reviews.update', $review), [
            'name' => 'Ксения',
            'text' => 'Отзыв',
            'status' => 'published',
            'original_url' => 'https://www.avito.ru/example/review/456',
        ])->assertRedirect(route('admin.avito-reviews.index'));

        $this->assertDatabaseHas('avito_reviews', [
            'id' => $review->id,
            'original_url' => 'https://www.avito.ru/example/review/456',
        ]);
    }
}
