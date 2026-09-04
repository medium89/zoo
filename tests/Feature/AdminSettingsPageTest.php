<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_site_settings_as_a_standalone_page(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(route('admin.settings'))
            ->assertOk()
            ->assertSee('Настройки сайта')
            ->assertSee(route('admin.settings.site'), false);
    }

    public function test_admin_can_open_telegram_settings_as_a_standalone_page(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(route('admin.telegram-bot-settings.edit'))
            ->assertOk()
            ->assertSee('Telegram-бот')
            ->assertSee(route('admin.telegram-bot-settings.update'), false);
    }
}
