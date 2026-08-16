<?php

namespace Tests\Unit;

use App\Services\BoardingPricingService;
use PHPUnit\Framework\TestCase;

class BoardingPricingServiceTest extends TestCase
{
    public function test_it_uses_the_price_list_for_species_and_dog_size(): void
    {
        $pricing = new BoardingPricingService();
        $tariffs = $pricing->defaultTariffs();

        $this->assertSame(500, $pricing->defaultRate('передержка', 'кот', null, $tariffs));
        $this->assertSame(800, $pricing->defaultRate('передержка', 'собака', 'small', $tariffs));
        $this->assertSame(1000, $pricing->defaultRate('передержка', 'собака', 'large', $tariffs));
        $this->assertSame(450, $pricing->defaultRate('выгул', 'собака', 'small', $tariffs));
        $this->assertSame(500, $pricing->defaultRate('выгул', 'собака', 'large', $tariffs));
        $this->assertSame(350, $pricing->defaultRate('уход', 'попугай', null, $tariffs));
    }
}
