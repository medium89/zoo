<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Category;
use App\Models\Client;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
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

    public function test_service_orders_pagination_footer_is_outside_the_table_shell(): void
    {
        $view = File::get(resource_path('views/admin/service-orders/index.blade.php'));
        $shellStart = strpos($view, '<section class="orders-list-shell">');
        $footer = strpos($view, '<footer class="orders-list-footer"');
        $shellClose = strpos($view, '    </section>'.PHP_EOL.'        @if($orders->total() > 0)', $shellStart ?: 0);

        $this->assertNotFalse($shellStart);
        $this->assertNotFalse($footer);
        $this->assertNotFalse($shellClose);
        $this->assertLessThan($footer, $shellClose, 'The orders pagination footer must not be inside the white list shell.');
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

    public function test_entity_lists_render_context_actions_as_one_accessible_menu(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $category = Category::create(['name' => 'Тестовое действие', 'slug' => 'test-action-category']);
        $client = Client::create(['name' => 'Анна']);
        Animal::create(['name' => 'Пушок', 'category_id' => $category->id, 'client_id' => $client->id]);

        $this->actingAs($admin)
            ->get(route('admin.animals.index'))
            ->assertOk()
            ->assertSee('data-admin-actions-menu', false)
            ->assertSee('aria-haspopup="menu"', false)
            ->assertSeeText('Просмотреть')
            ->assertSeeText('Редактировать')
            ->assertSeeText('Удалить');

        $this->actingAs($admin)
            ->get(route('admin.clients.index'))
            ->assertOk()
            ->assertSee('data-admin-actions-menu', false)
            ->assertSee('aria-haspopup="menu"', false)
            ->assertSeeText('Просмотреть')
            ->assertSeeText('Редактировать')
            ->assertSeeText('Удалить');
    }

    public function test_contextual_action_inventory_uses_the_shared_menu_component(): void
    {
        $views = [
            'admin/advantages/index.blade.php',
            'admin/animals/index.blade.php',
            'admin/animals/show.blade.php',
            'admin/articles/comments.blade.php',
            'admin/articles/index.blade.php',
            'admin/avito_reviews/index.blade.php',
            'admin/boarding/archive.blade.php',
            'admin/boarding/tasks.blade.php',
            'admin/categories/index.blade.php',
            'admin/clients/index.blade.php',
            'admin/clients/show.blade.php',
            'admin/feedbacks/index.blade.php',
            'admin/galleries/index.blade.php',
            'admin/images/index.blade.php',
            'admin/service-orders/archive.blade.php',
            'admin/service-orders/index.blade.php',
            'admin/services/index.blade.php',
            'admin/sliders/index.blade.php',
            'admin/socials/index.blade.php',
            'admin/users/index.blade.php',
        ];

        foreach ($views as $view) {
            $this->assertStringContainsString(
                '<x-admin.actions-menu',
                File::get(resource_path('views/'.$view)),
                "{$view} must keep row or card actions inside the shared menu."
            );
        }

        $component = File::get(resource_path('views/components/admin/actions-menu.blade.php'));
        $this->assertStringContainsString('aria-haspopup="menu"', $component);
        $this->assertStringContainsString('role="menu"', $component);

        $clientMap = File::get(resource_path('views/admin/client-map/index.blade.php'));
        $this->assertStringContainsString('client-node__menu-toggle', $clientMap);
        $this->assertStringContainsString('fa-ellipsis-vertical', $clientMap);
        $this->assertStringContainsString('.client-node{overflow:visible}', $clientMap);
        $this->assertStringContainsString("event.key === 'ArrowDown'", $clientMap);
        $this->assertStringContainsString("event.key === 'Escape'", $clientMap);
    }

    public function test_creation_fabs_cover_primary_creation_pages_and_defer_trigger_clicks(): void
    {
        $layout = File::get(resource_path('views/admin/index.blade.php'));
        $fab = File::get(resource_path('views/components/admin/fab.blade.php'));

        $this->assertStringContainsString("window.setTimeout(() => target.click(), 0)", $layout);
        $this->assertStringContainsString('data-fab-target', $fab);

        foreach ([
            'admin/clients/index.blade.php' => 'Добавить клиента',
            'admin/animals/index.blade.php' => 'Добавить питомца',
            'admin/service-orders/index.blade.php' => 'Новый заказ',
            'admin/client-map/index.blade.php' => 'Добавить клиента',
        ] as $view => $label) {
            $contents = File::get(resource_path('views/'.$view));
            $this->assertStringContainsString('<x-admin.fab', $contents, $view.' must expose a creation FAB.');
            $this->assertStringContainsString($label, $contents, $view.' must keep its creation label.');
        }

        foreach ([
            'admin/clients/index.blade.php',
            'admin/animals/index.blade.php',
            'admin/service-orders/index.blade.php',
        ] as $view) {
            $contents = File::get(resource_path('views/'.$view));
            $this->assertStringNotContainsString('class="btn btn-primary js-new-service-order"', $contents, $view.' must not expose a visible empty-state create button.');
        }
    }
}
