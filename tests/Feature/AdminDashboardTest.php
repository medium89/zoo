<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderAnimal;
use App\Models\ServiceOrderService;
use App\Models\ServiceTariff;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-09-04 10:00:00'));
        $this->category = Category::create([
            'name' => 'Тестовые кошки',
            'slug' => 'test-cats',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_invalid_period_falls_back_to_month(): void
    {
        $response = $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(route('admin.dashboard', ['period' => 'not-a-period']));

        $response->assertOk()
            ->assertViewHas('period', 'month')
            ->assertSee('за месяц');
    }

    public function test_dashboard_shows_honest_empty_state_when_there_is_no_work(): void
    {
        $response = $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(route('admin.dashboard'));

        $summary = $response->viewData('summary');

        $response->assertOk()
            ->assertSee('В этом периоде нет передержек.')
            ->assertSee('В выбранном периоде нет питомцев в активных заказах.')
            ->assertSee('На ближайшие семь дней заездов и выездов нет.')
            ->assertDontSee('<div id="workloadChart"', false);
        $this->assertSame(0, array_sum($summary['active']));
        $this->assertSame(0, $summary['working_days']);
        $this->assertSame(0, $summary['pet_days']);
        $this->assertSame(0, $summary['revenue']);
    }

    public function test_dashboard_excludes_archived_orders_from_active_metrics_and_revenue(): void
    {
        $this->createOrder(['archived_at' => now()], 9, 900);
        $this->createOrder([], 2, 500);

        $response = $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(route('admin.dashboard'));

        $summary = $response->viewData('summary');

        $response->assertOk();
        $this->assertSame(2, array_sum($summary['active']));
        $this->assertSame(2, $summary['pet_days']);
        $this->assertSame(1000, $summary['revenue']);
    }

    public function test_same_day_order_shows_both_arrival_and_departure_in_upcoming_events(): void
    {
        $this->createOrder(['start_date' => now()->toDateString(), 'end_date' => now()->toDateString()], 1, 500, 'Пушок');

        $response = $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('Пушок')
            ->assertSee('Заезд')
            ->assertSee('Выезд')
            ->assertSee('4 сентября');
        $this->assertSame(['Заезд', 'Выезд'], $response->viewData('upcoming')->pluck('type')->all());
    }

    public function test_non_boarding_service_adds_revenue_but_not_pet_days_or_working_days(): void
    {
        $this->createOrder([], 3, 100, 'Снежок', 'уход', 2);

        $response = $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(route('admin.dashboard'));

        $summary = $response->viewData('summary');

        $this->assertSame(600, $summary['revenue']);
        $this->assertSame(0, $summary['pet_days']);
        $this->assertSame(0, $summary['working_days']);
    }

    public function test_tariff_update_persists_values_and_keeps_selected_period(): void
    {
        $payload = ['period' => 'week', 'tariffs' => []];
        foreach (['передержка', 'выгул', 'уход'] as $service) {
            foreach (['cat', 'dog_small', 'dog_large', 'small_pet', 'other'] as $group) {
                $payload['tariffs'][$service][$group] = 500;
            }
        }
        $payload['tariffs']['передержка']['cat'] = 777;

        $response = $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->post(route('admin.dashboard.tariffs.update'), $payload);

        $response->assertRedirect(route('admin.dashboard', ['period' => 'week']));
        $this->assertSame(777, ServiceTariff::query()
            ->where('service_type', 'передержка')
            ->where('animal_group', 'cat')
            ->value('amount'));
    }

    private function createOrder(
        array $attributes,
        int $quantity,
        int $price,
        string $label = 'Мурка',
        string $serviceType = 'передержка',
        int $unitsPerDay = 1,
    ): ServiceOrder
    {
        $order = ServiceOrder::create(array_merge([
            'service_type' => $serviceType,
            'units_per_day' => $unitsPerDay,
            'daily_price' => $price,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'source' => 'test',
            'status' => 'active',
        ], $attributes));

        $animal = ServiceOrderAnimal::create([
            'service_order_id' => $order->id,
            'category_id' => $this->category->id,
            'label' => $label,
            'quantity' => $quantity,
        ]);

        ServiceOrderService::create([
            'service_order_id' => $order->id,
            'service_order_animal_id' => $animal->id,
            'service_type' => $serviceType,
            'units_per_day' => $unitsPerDay,
            'unit_price' => $price,
        ]);

        return $order;
    }
}
