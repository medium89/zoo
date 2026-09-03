<?php

namespace Tests\Unit;

use App\Http\Controllers\Telegram\TelegramBotController;
use ReflectionClass;
use Tests\TestCase;

class TelegramBotControllerOwnerIntentTest extends TestCase
{
    public function test_it_recognizes_a_plain_language_pet_owner_update(): void
    {
        $controller = (new ReflectionClass(TelegramBotController::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod($controller, 'ownerUpdateIntentFromText');
        $method->setAccessible(true);

        $intent = $method->invoke($controller, 'Хозяйку Дейзи зовут Анастасия');

        $this->assertSame('update_pet_owner', $intent['intent']);
        $this->assertSame('Дейзи', $intent['animal']['name']);
        $this->assertSame('Анастасия', $intent['client']['name']);
    }

    public function test_it_extracts_pet_name_from_photo_caption(): void
    {
        $controller = (new ReflectionClass(TelegramBotController::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod($controller, 'animalNameFromPhotoCaption');
        $method->setAccessible(true);

        $this->assertSame('Дейзи', $method->invoke($controller, 'Это фото Дейзи'));
    }

    public function test_named_pet_is_not_replaced_with_an_anonymous_order_group(): void
    {
        $controller = (new ReflectionClass(TelegramBotController::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod($controller, 'anonymousOrderIntentFromText');
        $method->setAccessible(true);

        $this->assertNull($method->invoke($controller, 'Запиши кота Тумсиса на уход с 10 по 11 сентября'));
        $this->assertNull($method->invoke($controller, 'Собаку Рекса на уход с 10 по 11 сентября'));
    }

    public function test_unnamed_group_still_uses_the_anonymous_order_flow(): void
    {
        $controller = (new ReflectionClass(TelegramBotController::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod($controller, 'anonymousOrderIntentFromText');
        $method->setAccessible(true);

        $intent = $method->invoke($controller, '22 и 23 уход за тремя котами и собакой');

        $this->assertSame('create_service_order', $intent['intent']);
        $this->assertSame(2, count($intent['animals']));
    }
}
