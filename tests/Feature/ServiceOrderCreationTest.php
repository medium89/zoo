<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceOrderCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_client_and_order_are_saved_together_with_animal_services(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $category = Category::query()->where('name', 'Собаки')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.service-orders.store'), [
                'new_client' => [
                    'name' => 'Анастасия',
                    'phone' => '+79990000000',
                    'note' => 'Позвонить перед визитом',
                ],
                'start_date' => '2026-11-10',
                'end_date' => '2026-11-12',
                'address' => 'ул. Тестовая, 1',
                'note' => 'Ключи у консьержа',
                'animals' => [[
                    'name' => 'Дейзи',
                    'category_id' => $category->id,
                    'quantity' => 1,
                    'note' => 'Не любит громкие звуки',
                    'services' => [[
                        'service_type' => 'выгул',
                        'units_per_day' => 2,
                        'unit_price' => 500,
                    ]],
                ]],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('clients', ['name' => 'Анастасия', 'phone' => '+79990000000']);
        $this->assertDatabaseHas('animals', ['name' => 'Дейзи', 'category_id' => $category->id]);
        $this->assertDatabaseHas('service_orders', [
            'service_type' => 'выгул',
            'units_per_day' => 2,
            'daily_price' => 1000,
            'start_date' => '2026-11-10 00:00:00',
            'end_date' => '2026-11-12 00:00:00',
        ]);
        $this->assertDatabaseHas('service_order_services', [
            'service_type' => 'выгул',
            'units_per_day' => 2,
            'unit_price' => 500,
        ]);
    }
}
