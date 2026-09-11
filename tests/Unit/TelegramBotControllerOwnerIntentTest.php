<?php

namespace Tests\Unit;

use App\Http\Controllers\Telegram\TelegramBotController;
use Carbon\Carbon;
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

    public function test_it_recognizes_pet_and_client_rename_phrases(): void
    {
        $controller = (new ReflectionClass(TelegramBotController::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod($controller, 'renameIntentFromText');
        $method->setAccessible(true);

        $petIntent = $method->invoke($controller, 'Кошку «1 кошка» зовут Тумсис');
        $this->assertSame('rename_pet', $petIntent['intent']);
        $this->assertSame('1 кошка', $petIntent['animal']['name']);
        $this->assertSame('Тумсис', $petIntent['new_name']);

        $clientIntent = $method->invoke($controller, 'Переименуй клиента Анастасия в Настя');
        $this->assertSame('rename_client', $clientIntent['intent']);
        $this->assertSame('Анастасия', $clientIntent['client']['name']);
        $this->assertSame('Настя', $clientIntent['new_name']);
    }

    public function test_it_recognizes_a_compact_booking_without_ai(): void
    {
        Carbon::setTestNow('2026-09-10 12:00:00');

        try {
            $controller = (new ReflectionClass(TelegramBotController::class))->newInstanceWithoutConstructor();
            $method = new \ReflectionMethod($controller, 'compactBookingIntentFromText');
            $method->setAccessible(true);

            $intent = $method->invoke($controller, 'С 11 по 12 уход Мия');

            $this->assertSame('create_booking', $intent['intent']);
            $this->assertSame('уход', $intent['service_type']);
            $this->assertSame('Мия', $intent['animal']['name']);
            $this->assertSame('2026-09-11', $intent['start_date']);
            $this->assertSame('2026-09-12', $intent['end_date']);
        } finally {
            Carbon::setTestNow();
        }
    }
}
