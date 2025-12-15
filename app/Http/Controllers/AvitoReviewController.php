<?php

namespace App\Http\Controllers;

use App\Models\AvitoReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AvitoReviewController extends Controller
{
    public function index()
    {
        $reviews = AvitoReview::orderByDesc('review_date')->latest()->paginate(15);

        return view('admin.avito_reviews.index', compact('reviews'));
    }

    public function edit(AvitoReview $avitoReview)
    {
        return view('admin.avito_reviews.edit', ['review' => $avitoReview]);
    }

    public function update(Request $request, AvitoReview $avitoReview)
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'review_date' => 'nullable|date',
            'text' => 'nullable|string',
            'status' => 'required|string|max:50',
            'photos_raw' => 'nullable|string',
        ]);

        $photos = [];
        if (!empty($data['photos_raw'])) {
            foreach (preg_split('/\r\n|\r|\n/', $data['photos_raw']) as $line) {
                $url = trim($line);
                if ($url !== '') {
                    $photos[] = $url;
                }
            }
        }

        $avitoReview->update([
            'name' => $data['name'] ?? null,
            'review_date' => $data['review_date'] ?? null,
            'text' => $data['text'] ?? null,
            'status' => $data['status'],
            'photos' => $photos ?: null,
        ]);

        return redirect()
            ->route('admin.avito-reviews.index')
            ->with('success', 'Отзыв обновлен');
    }

    public function destroy(AvitoReview $avitoReview)
    {
        $avitoReview->delete();

        return redirect()
            ->route('admin.avito-reviews.index')
            ->with('success', 'Отзыв удален');
    }

    public function refresh()
    {
        $url = config('avito.reviews_url');
        if (!$url) {
            return redirect()
                ->route('admin.avito-reviews.index')
                ->with('error', 'URL страницы Avito не задан');
        }

        $response = Http::get($url);
        if (!$response->ok()) {
            return redirect()
                ->route('admin.avito-reviews.index')
                ->with('error', 'Не удалось загрузить страницу Avito');
        }

        $html = $response->body();
        $parsed = $this->parseReviewsFromHtml($html);

        if (empty($parsed)) {
            return redirect()
                ->route('admin.avito-reviews.index')
                ->with('error', 'Не удалось найти отзывы на странице Avito. Проверьте структуру страницы.');
        }

        $added = 0;
        foreach ($parsed as $item) {
            $hashBase = mb_strtolower(
                trim(($item['name'] ?? '') . '|' . ($item['date'] ?? '') . '|' . ($item['text'] ?? ''))
            );
            if ($hashBase === '') {
                continue;
            }
            $hash = hash('sha256', $hashBase);

            $existing = AvitoReview::where('source_hash', $hash)->first();
            if ($existing) {
                continue;
            }

            $photos = $item['photos'] ?? [];
            if (!is_array($photos)) {
                $photos = [];
            }

            AvitoReview::create([
                'name' => $item['name'] ?? null,
                'review_date' => $item['date'] ?? null,
                'text' => $item['text'] ?? null,
                'photos' => $photos ?: null,
                'status' => 'new',
                'source_hash' => $hash,
            ]);

            $added++;
        }

        if ($added === 0) {
            return redirect()
                ->route('admin.avito-reviews.index')
                ->with('success', 'Новых отзывов не найдено');
        }

        return redirect()
            ->route('admin.avito-reviews.index')
            ->with('success', 'Добавлено новых отзывов: ' . $added);
    }

    private function parseReviewsFromHtml(string $html): array
    {
        $result = [];

        if (trim($html) === '') {
            return $result;
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML($html);
        libxml_clear_errors();

        if (!$loaded) {
            return $result;
        }

        $xpath = new \DOMXPath($dom);

        $reviewNodes = $xpath->query('//*[@itemprop="review" or @itemtype="http://schema.org/Review" or @itemtype="https://schema.org/Review"]');
        if (!$reviewNodes || $reviewNodes->length === 0) {
            return $result;
        }

        foreach ($reviewNodes as $node) {
            // Имя автора
            $name = null;
            $authorNode = $xpath->query('.//*[@itemprop="author"]', $node)->item(0);
            if ($authorNode) {
                $nameNode = $xpath->query('.//*[@itemprop="name"]', $authorNode)->item(0);
                if ($nameNode) {
                    $name = trim($nameNode->textContent);
                } else {
                    $name = trim($authorNode->textContent);
                }
            }

            // Дата
            $date = null;
            $dateNode = $xpath->query('.//*[@itemprop="datePublished"]', $node)->item(0);

            if ($dateNode) {
                $contentAttr = $dateNode->attributes->getNamedItem('content');
                if ($contentAttr) {
                    $date = $contentAttr->nodeValue;
                } else {
                    $date = trim($dateNode->textContent);
                }
            } else {
                // Avito: "12 декабря · Клиент" в подзаголовке
                $subtitleNode = $xpath->query('.//p[@data-marker and contains(@data-marker, "/header/subtitle")]', $node)->item(0);
                if ($subtitleNode) {
                    $subtitle = trim($subtitleNode->textContent);
                    $parsedDate = $this->parseAvitoDateString($subtitle);
                    if ($parsedDate !== null) {
                        $date = $parsedDate;
                    }
                }
            }

            // Текст отзыва
            $textNode = $xpath->query('.//*[@itemprop="reviewBody"]', $node)->item(0);
            if (!$textNode) {
                $textNode = $xpath->query('.//p[@data-marker and contains(@data-marker, "/text-section/text")]', $node)->item(0);
            }

            $text = $textNode ? trim($textNode->textContent) : null;

            // Фото отзыва (не аватарки) — img с data-marker ".../image(N)/image"
            $photos = [];
            $imageNodes = $xpath->query('.//img[@data-marker and contains(@data-marker, "/image(")]', $node);
            if ($imageNodes && $imageNodes->length > 0) {
                foreach ($imageNodes as $img) {
                    $src = $img->attributes->getNamedItem('src');
                    $dataSrc = $img->attributes->getNamedItem('data-src');
                    $url = $src ? $src->nodeValue : ($dataSrc ? $dataSrc->nodeValue : null);
                    if ($url && !in_array($url, $photos, true)) {
                        $photos[] = $url;
                    }
                }
            }

            if ($name || $date || $text) {
                $result[] = [
                    'name' => $name,
                    'date' => $date,
                    'text' => $text,
                    'photos' => $photos,
                ];
            }
        }

        return $result;
    }

    private function parseAvitoDateString(string $subtitle): ?string
    {
        // "12 декабря · Клиент" или "17 ноября 2024 · Покупатель"
        $parts = explode('·', $subtitle, 2);
        $datePart = trim($parts[0] ?? '');
        if ($datePart === '') {
            return null;
        }

        if (!preg_match('/^(\d{1,2})\s+([А-Яа-яЁё]+)(?:\s+(\d{4}))?$/u', $datePart, $m)) {
            return null;
        }

        $day = (int)$m[1];
        $monthName = mb_strtolower($m[2], 'UTF-8');
        $year = isset($m[3]) ? (int)$m[3] : (int)date('Y');

        $months = [
            'января' => 1,
            'февраля' => 2,
            'марта' => 3,
            'апреля' => 4,
            'мая' => 5,
            'июня' => 6,
            'июля' => 7,
            'августа' => 8,
            'сентября' => 9,
            'октября' => 10,
            'ноября' => 11,
            'декабря' => 12,
        ];

        if (!isset($months[$monthName])) {
            return null;
        }

        $month = $months[$monthName];

        if ($day < 1 || $day > 31) {
            return null;
        }

        return sprintf('%04d-%02d-%02d 00:00:00', $year, $month, $day);
    }
}
