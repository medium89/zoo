<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Client;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderAnimal;
use App\Models\ServiceOrderService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminServiceOrdersTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->category = Category::query()->firstOrCreate(['name' => 'Кошки']);
    }

    public function test_index_omits_archived_orders_and_renders_total_period_and_boarding_without_frequency(): void
    {
        $active = $this->makeOrder('Мурка', 'передержка', now()->addDays(2)->toDateString(), now()->addDays(4)->toDateString());
        $archived = $this->makeOrder('Скрытая', 'уход', now()->addDays(2)->toDateString(), now()->addDays(3)->toDateString(), ['archived_at' => now(), 'status' => 'archived']);

        $response = $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(route('admin.service-orders.index'));

        $response->assertOk()
            ->assertViewHas('orders', fn ($orders) => $orders->pluck('id')->all() === [$active->id])
            ->assertSeeText($active->start_date->locale('ru')->translatedFormat('j M').' — '.$active->end_date->locale('ru')->translatedFormat('j M'))
            ->assertDontSee('1 раз в день');
    }

    public function test_index_applies_search_status_service_and_date_filters(): void
    {
        $planned = $this->makeOrder('Тумсис', 'уход', now()->addDays(4)->toDateString(), now()->addDays(5)->toDateString());
        $this->makeOrder('Бобик', 'выгул', now()->addDays(8)->toDateString(), now()->addDays(9)->toDateString());
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.service-orders.index', [
                'search' => 'Тумсис',
                'status' => 'planned',
                'service' => 'уход',
                'from' => $planned->start_date->toDateString(),
                'to' => $planned->end_date->toDateString(),
            ]))
            ->assertOk()
            ->assertViewHas('orders', fn ($orders) => $orders->pluck('id')->all() === [$planned->id])
            ->assertSee('2 раз в день');
    }

    private function makeOrder(string $animalName, string $serviceType, string $start, string $end, array $attributes = []): ServiceOrder
    {
        $client = Client::create(['name' => 'Клиент '.$animalName]);
        $order = ServiceOrder::create(array_merge([
            'client_id' => $client->id,
            'service_type' => $serviceType,
            'units_per_day' => $serviceType === 'передержка' ? 1 : 2,
            'daily_price' => 1000,
            'start_date' => $start,
            'end_date' => $end,
            'status' => 'active',
            'source' => 'test',
        ], $attributes));
        $position = ServiceOrderAnimal::create([
            'service_order_id' => $order->id,
            'category_id' => $this->category->id,
            'label' => $animalName,
            'quantity' => 1,
        ]);
        ServiceOrderService::create([
            'service_order_id' => $order->id,
            'service_order_animal_id' => $position->id,
            'service_type' => $serviceType,
            'units_per_day' => $serviceType === 'передержка' ? 1 : 2,
            'unit_price' => 500,
        ]);

        return $order;
    }
}
