<?php

namespace Tests\Feature;

use App\Models\Feedback;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FeedbackConsentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');

        parent::setUp();
    }

    public function test_feedback_requires_personal_data_consent(): void
    {
        SiteSetting::create([
            'personal_data_consent_text' => '<p>Согласие на обработку персональных данных</p>',
        ]);

        config(['services.recaptcha.secret' => 'test-recaptcha-secret']);
        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true], 200),
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $response = $this->postJson(route('feedback.store'), [
            'name' => 'Иван',
            'phone' => '+7(999)111-22-33',
            'message' => 'Тестовая заявка',
            'g-recaptcha-response' => 'test-token',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['personal_data_consent']);
    }

    public function test_feedback_stores_consent_snapshot_hash_and_timestamp(): void
    {
        $consentHtml = '<p><strong>Согласие</strong> на обработку персональных данных</p>';
        SiteSetting::create([
            'personal_data_consent_text' => $consentHtml,
        ]);

        config(['services.recaptcha.secret' => 'test-recaptcha-secret']);
        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true], 200),
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $response = $this->postJson(route('feedback.store'), [
            'name' => 'Мария',
            'phone' => '+7(999)222-33-44',
            'message' => 'Нужна консультация',
            'personal_data_consent' => '1',
            'g-recaptcha-response' => 'test-token',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Ваше сообщение успешно отправлено!',
        ]);

        $feedback = Feedback::query()->first();
        $this->assertNotNull($feedback);
        $this->assertTrue((bool)$feedback->personal_data_consent);
        $this->assertNotNull($feedback->personal_data_consent_at);
        $this->assertSame($consentHtml, $feedback->personal_data_consent_text);
        $this->assertSame(hash('sha256', $consentHtml), $feedback->personal_data_consent_hash);
    }

    public function test_admin_can_save_personal_data_consent_document(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $this->actingAs($user);

        $response = $this->post(route('admin.personal-data-consent.update'), [
            'personal_data_consent_text' => '<p>Новая редакция документа</p>',
        ]);

        $response->assertRedirect(route('admin.personal-data-consent.edit'));
        $this->assertDatabaseHas('site_settings', [
            'personal_data_consent_text' => '<p>Новая редакция документа</p>',
        ]);
    }
}
