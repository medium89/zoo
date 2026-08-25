<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientAnimalLinkingTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_attach_existing_animal_and_create_a_new_one(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $client = Client::create(['name' => 'Анастасия']);
        $animal = Animal::create(['name' => 'Дейзи', 'order' => 1]);

        $this->actingAs($admin)
            ->post(route('admin.clients.animals.attach', $client), ['animal_id' => $animal->id])
            ->assertRedirect();
        $this->assertDatabaseHas('animals', ['id' => $animal->id, 'client_id' => $client->id]);

        $this->actingAs($admin)
            ->post(route('admin.clients.animals.attach', $client), ['new_animal_name' => 'Рекс'])
            ->assertRedirect();
        $this->assertDatabaseHas('animals', ['name' => 'Рекс', 'client_id' => $client->id]);
    }

    public function test_animal_can_receive_existing_or_new_owner(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $animal = Animal::create(['name' => 'Дейзи', 'order' => 1]);
        $client = Client::create(['name' => 'Анастасия']);

        $this->actingAs($admin)
            ->post(route('admin.animals.client.assign', $animal), ['client_id' => $client->id])
            ->assertRedirect();
        $this->assertDatabaseHas('animals', ['id' => $animal->id, 'client_id' => $client->id]);

        $this->actingAs($admin)
            ->post(route('admin.animals.client.assign', $animal), ['new_client_name' => 'Сергей'])
            ->assertRedirect();
        $this->assertDatabaseHas('clients', ['name' => 'Сергей']);
        $this->assertDatabaseHas('animals', ['id' => $animal->id, 'client_id' => Client::where('name', 'Сергей')->value('id')]);
    }
}
