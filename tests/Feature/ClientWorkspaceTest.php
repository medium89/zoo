<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_clients_workspace_shows_rows_and_preserves_filters(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $client = Client::create([
            'name' => 'Анастасия',
            'phone' => '+7 999 123-45-67',
            'address' => 'Барнаул, Лазурная, 29',
            'tags' => [['name' => 'Постоянный', 'type' => 'positive']],
        ]);
        Animal::create(['name' => 'Дейзи', 'client_id' => $client->id, 'order' => 1]);
        Client::create(['name' => 'Без питомцев']);

        $this->actingAs($admin)
            ->get(route('admin.clients.index', ['search' => 'Анастасия', 'animals' => 'with', 'address' => 'with']))
            ->assertOk()
            ->assertSee('clients-workspace')
            ->assertSee('admin-grid')
            ->assertSee('Анастасия')
            ->assertSee('Питомцы')
            ->assertSee('+7 999 123-45-67')
            ->assertSee('js-edit-client-with-pets', false)
            ->assertSee('name="animals"', false)
            ->assertSee('value="with" selected', false)
            ->assertSee('name="address"', false)
            ->assertSee('value="with" selected', false)
            ->assertDontSee('class="client-workspace-card__name">Без питомцев', false);

        $this->actingAs($admin)
            ->get(route('admin.clients.index'))
            ->assertOk()
            ->assertSee('Питомцев пока нет');
    }

    public function test_clients_workspace_has_distinct_empty_state_for_active_filters(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.clients.index', ['search' => 'Несуществующий']))
            ->assertOk()
            ->assertSee('Ничего не нашли')
            ->assertSee('Сбросить фильтры');

        $this->actingAs($admin)
            ->get(route('admin.clients.index'))
            ->assertOk()
            ->assertSee('Клиентов пока нет')
            ->assertSee('Новый клиент');
    }

    public function test_clients_workspace_paginates_and_keeps_filters_in_footer_links(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        foreach (range(1, 11) as $index) {
            Client::create(['name' => "client {$index}"]);
        }

        $this->actingAs($admin)
            ->get(route('admin.clients.index', ['search' => 'client', 'animals' => 'without', 'address' => 'without', 'per_page' => 10]))
            ->assertOk()
            ->assertSee('Показано 1–10 из 11')
            ->assertSee('name="per_page"', false)
            ->assertSee('per_page=10', false)
            ->assertSee('animals=without', false)
            ->assertSee('address=without', false);
    }

    public function test_clients_auto_filter_reset_clears_filters_and_keeps_page_size_only_in_the_form(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.clients.index', ['search' => 'Несуществующий', 'per_page' => 50, 'page' => 2]))
            ->assertOk()
            ->assertSee('data-auto-filters', false)
            ->assertSee('name="per_page" value="50"', false)
            ->assertSee('<a href="'.route('admin.clients.index').'" class="btn btn-light admin-filter-bar__reset"', false)
            ->assertDontSee('name="page"', false);
    }
}
