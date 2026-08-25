<?php

namespace Tests\Unit;

use App\Http\Controllers\TelegramBotController;
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
}
