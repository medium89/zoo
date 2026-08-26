<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_saves_positions_and_changes_animal_connection(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $client = Client::create(['name' => 'Анастасия']);
        $animal = Animal::create(['name' => 'Дейзи', 'order' => 1]);

        $this->actingAs($admin)
            ->get(route('admin.client-map.index'))
            ->assertOk()
            ->assertSee('csrf-token')
            ->assertSee('Анастасия')
            ->assertSee('Дейзи');

        $this->actingAs($admin)->postJson(route('admin.client-map.positions.save'), [
            'nodes' => [
                ['type' => 'client', 'id' => $client->id, 'x' => 180, 'y' => 240],
                ['type' => 'animal', 'id' => $animal->id, 'x' => 640, 'y' => 480],
            ],
        ])->assertOk();
        $this->assertDatabaseHas('clients', ['id' => $client->id, 'map_x' => 180, 'map_y' => 240]);
        $this->assertDatabaseHas('animals', ['id' => $animal->id, 'map_x' => 640, 'map_y' => 480]);

        $this->actingAs($admin)
            ->postJson(route('admin.client-map.animals.attach', [$animal, $client]))
            ->assertOk();
        $this->assertDatabaseHas('animals', ['id' => $animal->id, 'client_id' => $client->id]);

        $this->actingAs($admin)
            ->deleteJson(route('admin.client-map.animals.detach', $animal))
            ->assertOk();
        $this->assertDatabaseHas('animals', ['id' => $animal->id, 'client_id' => null]);

        $this->actingAs($admin)
            ->patchJson(route('admin.client-map.clients.update', $client), ['name' => 'Анна', 'phone' => '+79990000000'])
            ->assertOk()
            ->assertJsonPath('name', 'Анна');
        $this->actingAs($admin)
            ->patchJson(route('admin.client-map.animals.update', $animal), ['name' => 'Дейзи-2'])
            ->assertOk()
            ->assertJsonPath('name', 'Дейзи-2');
    }
}
