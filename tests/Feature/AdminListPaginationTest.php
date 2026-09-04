<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Category;
use App\Models\Client;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminListPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_animals_list_uses_requested_page_size_and_preserves_filters(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $category = Category::create(['name' => 'Тестовые кошки', 'slug' => 'test-cats']);
        foreach (range(1, 11) as $index) {
            Animal::create(['name' => "Питомец {$index}", 'category_id' => $category->id, 'order' => $index]);
        }

        $this->actingAs($admin)
            ->get(route('admin.animals.index', ['category_id' => $category->id, 'owner' => 'without', 'per_page' => 10]))
            ->assertOk()
            ->assertSee('Показано 1–10 из 11')
            ->assertSee('name="per_page"', false)
            ->assertSee('per_page=10', false)
            ->assertSee('category_id='.$category->id, false)
            ->assertSee('owner=without', false)
            ->assertSee('data-auto-filters', false)
            ->assertDontSee('>Применить</button>', false);
    }

    public function test_categories_list_is_searchable_and_paginated(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        foreach (range(1, 11) as $index) {
            Category::create(['name' => "Вид {$index}", 'slug' => "species-{$index}"]);
        }

        $this->actingAs($admin)
            ->get(route('admin.categories.index', ['search' => 'Вид', 'per_page' => 10]))
            ->assertOk()
            ->assertSee('Показано 1–10 из 11')
            ->assertSee('name="per_page"', false)
            ->assertSee('per_page=10', false)
            ->assertSee('name="search"', false)
            ->assertSee('value="Вид"', false);
    }

    public function test_service_orders_list_is_paginated_and_keeps_search_filter(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $client = Client::create(['name' => 'Клиент']);
        foreach (range(1, 11) as $index) {
            ServiceOrder::create([
                'client_id' => $client->id,
                'service_type' => 'передержка',
                'daily_price' => 500,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDay()->toDateString(),
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.service-orders.index', ['search' => 'Клиент', 'per_page' => 10]))
            ->assertOk()
            ->assertSee('Показано 1–10 из 11')
            ->assertSee('name="per_page"', false)
            ->assertSee('per_page=10', false)
            ->assertSee('name="search"', false)
            ->assertSee('value="Клиент"', false)
            ->assertSeeInOrder(['orders-create-region', 'orders-create js-new-service-order'], false);
    }

    public function test_page_size_alone_does_not_turn_empty_lists_into_filter_misses(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.clients.index', ['per_page' => 10]))
            ->assertOk()
            ->assertSee('Клиентов пока нет')
            ->assertDontSee('Ничего не нашли');

        $this->actingAs($admin)
            ->get(route('admin.animals.index', ['per_page' => 10]))
            ->assertOk()
            ->assertSee('Питомцев пока нет')
            ->assertDontSee('Ничего не нашли');

        $this->actingAs($admin)
            ->get(route('admin.categories.index', ['per_page' => 10]))
            ->assertOk()
            ->assertDontSee('Ничего не нашли');

        $this->actingAs($admin)
            ->get(route('admin.service-orders.index', ['per_page' => 10]))
            ->assertOk()
            ->assertSee('Заказов пока нет')
            ->assertDontSee('Нет заказов по этим фильтрам');
    }
}
