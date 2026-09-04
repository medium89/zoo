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
            ->assertSee('Добавить клиента');
    }
}
