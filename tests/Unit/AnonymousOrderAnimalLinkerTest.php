<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\ServiceOrder;
use App\Services\AnonymousOrderAnimalLinker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnonymousOrderAnimalLinkerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_and_attaches_a_pet_card_for_an_anonymous_order_position(): void
    {
        $category = Category::query()->where('name', 'Кошки')->firstOrFail();
        $order = ServiceOrder::create([
            'service_type' => 'уход',
            'units_per_day' => 1,
            'daily_price' => 500,
            'start_date' => '2026-09-03',
            'end_date' => '2026-09-05',
        ]);
        $position = $order->animals()->create([
            'category_id' => $category->id,
            'label' => '1 кошка',
            'quantity' => 1,
        ]);

        $animal = app(AnonymousOrderAnimalLinker::class)->link($position);

        $this->assertSame('1 кошка', $animal->name);
        $this->assertSame($category->id, $animal->category_id);
        $this->assertDatabaseHas('service_order_animals', [
            'id' => $position->id,
            'animal_id' => $animal->id,
        ]);
    }
}
