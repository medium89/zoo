<?php

namespace Tests\Unit;

use App\Http\Controllers\AvitoReviewController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class AvitoReviewControllerParserTest extends TestCase
{
    public function test_it_parses_reviews_from_data_markers_without_microdata(): void
    {
        $html = <<<HTML
<!doctype html>
<html>
<body>
    <div>
        <div data-marker="reviews/review(0)/header/title">Ирина</div>
        <p data-marker="reviews/review(0)/header/subtitle">12 декабря · Клиент</p>
        <p data-marker="reviews/review(0)/text-section/text">Все понравилось, спасибо!</p>
        <img data-marker="reviews/review(0)/image(0)/image" src="https://example.com/review-1.jpg" />
    </div>
</body>
</html>
HTML;

        $parsed = $this->parseReviewsFromHtml($html);

        $this->assertCount(1, $parsed);
        $this->assertSame('Ирина', $parsed[0]['name']);
        $this->assertMatchesRegularExpression('/^\d{4}-12-12 00:00:00$/', (string)$parsed[0]['date']);
        $this->assertSame('Все понравилось, спасибо!', $parsed[0]['text']);
        $this->assertSame(['https://example.com/review-1.jpg'], $parsed[0]['photos']);
    }

    public function test_it_deduplicates_same_review_from_microdata_and_data_marker_blocks(): void
    {
        $html = <<<HTML
<!doctype html>
<html>
<body>
    <article itemprop="review">
        <span itemprop="author"><span itemprop="name">Анна</span></span>
        <time itemprop="datePublished" content="2026-02-13">13 февраля 2026</time>
        <p itemprop="reviewBody">Отличный сервис</p>
        <img data-marker="reviews/review(1)/image(0)/image" src="https://example.com/p1.jpg" />
    </article>

    <div>
        <div data-marker="reviews/review(1)/header/title">Анна</div>
        <p data-marker="reviews/review(1)/header/date">2026-02-13</p>
        <p data-marker="reviews/review(1)/text-section/text">Отличный сервис</p>
        <img data-marker="reviews/review(1)/image(1)/image" src="https://example.com/p2.jpg" />
    </div>
</body>
</html>
HTML;

        $parsed = $this->parseReviewsFromHtml($html);

        $this->assertCount(1, $parsed);
        $this->assertSame('Анна', $parsed[0]['name']);
        $this->assertSame('2026-02-13', $parsed[0]['date']);
        $this->assertSame('Отличный сервис', $parsed[0]['text']);
        $this->assertSame(
            ['https://example.com/p1.jpg', 'https://example.com/p2.jpg'],
            $parsed[0]['photos']
        );
    }

    public function test_it_detects_avito_access_block_page(): void
    {
        $html = <<<HTML
<!doctype html>
<html>
<body>
    <h1>Доступ ограничен</h1>
    <p>Проверьте, что вы не робот</p>
    <form action="/captcha"></form>
</body>
</html>
HTML;

        $controller = new AvitoReviewController();
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('looksLikeAvitoAccessBlocked');
        $method->setAccessible(true);

        $isBlocked = $method->invoke($controller, $html);
        $this->assertTrue($isBlocked);
    }

    private function parseReviewsFromHtml(string $html): array
    {
        $controller = new AvitoReviewController();
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('parseReviewsFromHtml');
        $method->setAccessible(true);

        return $method->invoke($controller, $html);
    }
}
