<?php

namespace Tests\Feature;

use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthSessionExpiryTest extends TestCase
{
    protected function setUp(): void
    {
        $appKey = 'base64:'.base64_encode(str_repeat('a', 32));

        putenv("APP_KEY={$appKey}");
        $_ENV['APP_KEY'] = $appKey;
        $_SERVER['APP_KEY'] = $appKey;

        parent::setUp();
    }

    public function test_expired_login_token_redirects_to_login_with_message(): void
    {
        Route::middleware('web')->post('/zooadmin/_test/expired-page', function () {
            throw new TokenMismatchException('expired');
        });

        $response = $this
            ->from('/zooadmin')
            ->post('/zooadmin/_test/expired-page', [
                'email' => 'admin@example.com',
                'password' => 'secret',
            ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['session']);
    }

    public function test_no_cache_middleware_adds_headers(): void
    {
        Route::middleware('no.cache')->get('/_test/no-cache', fn () => response('ok'));

        $response = $this->get('/_test/no-cache');
        $cacheControl = $response->headers->get('Cache-Control');

        $response->assertOk();
        $response->assertHeader('Pragma', 'no-cache');
        $response->assertHeader('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
        $this->assertNotNull($cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
    }
}
