<?php

namespace Tests\Feature;

use App\Models\Social;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaptchaInlineViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        parent::setUp();
        config(['services.recaptcha.site_key' => 'test-site-key']);
    }

    public function test_current_and_saved_homepages_show_captcha_inside_each_form(): void
    {
        Social::create(['icon' => 'test', 'title' => 'Test', 'link' => 'https://example.com', 'text' => 'Test', 'active' => true]);

        foreach (['/', '/v1'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();
            $this->assertSame(2, substr_count($html, 'data-captcha-form'));
            $this->assertSame(2, substr_count($html, 'class="g-recaptcha"'));
            $this->assertStringNotContainsString('id="captchaStepModal"', $html);
            $this->assertStringContainsString('data-sitekey="test-site-key"', $html);
        }
    }
}
