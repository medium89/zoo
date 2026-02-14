<?php

namespace App\Http\Controllers;

use App\Models\AvitoReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class AvitoReviewController extends Controller
{
    public function index()
    {
        $reviews = AvitoReview::orderBy('order')->orderBy('id')->paginate(50);

        return view('admin.avito_reviews.index', compact('reviews'));
    }

    public function create()
    {
        $review = new AvitoReview([
            'status' => 'published',
        ]);
        return view('admin.avito_reviews.create', compact('review'));
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
            'photos_upload' => 'nullable|array',
            'photos_upload.*' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:12288',
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

        if ($request->hasFile('photos_upload')) {
            foreach ($request->file('photos_upload') as $file) {
                if (!$file) {
                    continue;
                }
                $path = $file->store('avito_reviews', 'public');
                if ($path && !in_array($path, $photos, true)) {
                    $photos[] = $path;
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

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'review_date' => 'nullable|date',
            'text' => 'nullable|string',
            'status' => 'required|string|max:50',
            'photos_raw' => 'nullable|string',
            'photos_upload' => 'nullable|array',
            'photos_upload.*' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:12288',
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

        if ($request->hasFile('photos_upload')) {
            foreach ($request->file('photos_upload') as $file) {
                if (!$file) {
                    continue;
                }
                $path = $file->store('avito_reviews', 'public');
                if ($path && !in_array($path, $photos, true)) {
                    $photos[] = $path;
                }
            }
        }

        $hashBase = mb_strtolower(
            trim(($data['name'] ?? '') . '|' . ($data['review_date'] ?? '') . '|' . ($data['text'] ?? ''))
        );
        if ($hashBase === '') {
            $hashBase = 'manual|' . microtime(true);
        }
        $sourceHash = hash('sha256', $hashBase);

        $order = (int)AvitoReview::max('order') + 1;

        AvitoReview::create([
            'name' => $data['name'] ?? null,
            'review_date' => $data['review_date'] ?? null,
            'text' => $data['text'] ?? null,
            'status' => $data['status'],
            'photos' => $photos ?: null,
            'source_hash' => $sourceHash,
            'order' => $order,
        ]);

        return redirect()
            ->route('admin.avito-reviews.index')
            ->with('success', 'Отзыв создан');
    }

    public function destroy(AvitoReview $avitoReview)
    {
        $avitoReview->delete();

        return redirect()
            ->route('admin.avito-reviews.index')
            ->with('success', 'Отзыв удален');
    }

    public function updateStatus(Request $request, AvitoReview $avitoReview)
    {
        $data = $request->validate([
            'status' => 'required|string|max:50',
        ]);

        $avitoReview->status = $data['status'];
        $avitoReview->save();

        return back()->with('success', 'Статус отзыва обновлен');
    }

    public function reorder(Request $request)
    {
        foreach ($request->input('orders', []) as $id => $order) {
            if ($model = AvitoReview::find($id)) {
                $model->order = (int)$order;
                $model->save();
            }
        }

        return back()->with('success', 'Порядок отзывов обновлен');
    }

    public function refresh()
    {
        $url = config('avito.reviews_url');
        if (!$url) {
            return redirect()
                ->route('admin.avito-reviews.index')
                ->with('error', 'URL страницы Avito не задан');
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) '
                    . 'AppleWebKit/537.36 (KHTML, like Gecko) '
                    . 'Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
            ])->get($url);
        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.avito-reviews.index')
                ->with('error', 'Ошибка соединения с Avito: ' . $e->getMessage());
        }

        if (!$response->ok()) {
            return redirect()
                ->route('admin.avito-reviews.index')
                ->with('error', 'Не удалось загрузить страницу Avito (HTTP ' . $response->status() . ')');
        }

        $html = $response->body();

        return $this->importFromHtml($html, 'Страница Avito');
    }

    public function import(Request $request)
    {
        $data = $request->validate([
            'html_file' => 'required|file|mimes:html,htm,txt',
        ]);

        $html = file_get_contents($request->file('html_file')->getRealPath());

        return $this->importFromHtml($html, 'Загруженный файл');
    }

    private function importFromHtml(string $html, string $sourceLabel)
    {
        $parsed = $this->parseReviewsFromHtml($html);

        if (empty($parsed)) {
            if ($this->looksLikeAvitoAccessBlocked($html)) {
                return redirect()
                    ->route('admin.avito-reviews.index')
                    ->with('error', $sourceLabel . ': Avito ограничил доступ к странице (капча/антибот). Попробуйте позже или импортируйте сохранённый HTML вручную.');
            }

            return redirect()
                ->route('admin.avito-reviews.index')
                ->with('error', $sourceLabel . ': не удалось найти отзывы. Проверьте, что загружена правильная страница объявлений.');
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
            $order = (int)AvitoReview::max('order') + 1;

            AvitoReview::create([
                'name' => $item['name'] ?? null,
                'review_date' => $item['date'] ?? null,
                'text' => $item['text'] ?? null,
                'photos' => $photos ?: null,
                'status' => 'new',
                'source_hash' => $hash,
                'order' => $order,
            ]);

            $added++;
        }

        if ($added === 0) {
            return redirect()
                ->route('admin.avito-reviews.index')
                ->with('success', $sourceLabel . ': новых отзывов не найдено');
        }

        return redirect()
            ->route('admin.avito-reviews.index')
            ->with('success', $sourceLabel . ': добавлено новых отзывов: ' . $added);
    }

    private function parseReviewsFromHtml(string $html): array
    {
        if (trim($html) === '') {
            return [];
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML($html);
        libxml_clear_errors();

        if (!$loaded) {
            return [];
        }

        $xpath = new \DOMXPath($dom);
        $parsed = array_merge(
            $this->parseReviewsFromMicrodata($xpath),
            $this->parseReviewsFromDataMarkers($xpath)
        );

        return $this->deduplicateParsedReviews($parsed);
    }

    private function parseReviewsFromMicrodata(\DOMXPath $xpath): array
    {
        $result = [];
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

    private function parseReviewsFromDataMarkers(\DOMXPath $xpath): array
    {
        $groups = [];
        $markerNodes = $xpath->query('//*[@data-marker]');
        if (!$markerNodes || $markerNodes->length === 0) {
            return [];
        }

        foreach ($markerNodes as $node) {
            $markerAttr = $node->attributes?->getNamedItem('data-marker');
            if (!$markerAttr) {
                continue;
            }
            $marker = trim($markerAttr->nodeValue);
            if ($marker === '') {
                continue;
            }

            if ($prefix = $this->extractMarkerPrefix($marker, '/text-section/text')) {
                $text = trim($node->textContent);
                if ($text !== '') {
                    $groups[$prefix]['text'] = $text;
                }
            }

            if (
                ($prefix = $this->extractMarkerPrefix($marker, '/header/title'))
                || ($prefix = $this->extractMarkerPrefix($marker, '/author/name'))
                || ($prefix = $this->extractMarkerPrefix($marker, '/user/name'))
                || ($prefix = $this->extractMarkerPrefix($marker, '/name'))
            ) {
                $name = trim($node->textContent);
                if ($name !== '') {
                    $groups[$prefix]['name'] = $name;
                }
            }

            if (
                ($prefix = $this->extractMarkerPrefix($marker, '/header/subtitle'))
                || ($prefix = $this->extractMarkerPrefix($marker, '/header/date'))
                || ($prefix = $this->extractMarkerPrefix($marker, '/date'))
            ) {
                $rawDate = trim($node->textContent);
                if ($rawDate !== '') {
                    $parsedDate = $this->parseAvitoDateString($rawDate);
                    $groups[$prefix]['date'] = $parsedDate ?? $rawDate;
                }
            }

            if (strtolower($node->nodeName) === 'img' && preg_match('~^(.*?)/image\(\d+\)/image(?:/.*)?$~', $marker, $m)) {
                $prefix = trim($m[1]);
                if ($prefix !== '') {
                    $url = $this->extractImageUrl($node);
                    if ($url !== null) {
                        $groups[$prefix]['photos'][] = $url;
                    }
                }
            }
        }

        $result = [];
        foreach ($groups as $group) {
            $name = $group['name'] ?? null;
            $date = $group['date'] ?? null;
            $text = $group['text'] ?? null;
            $photos = $group['photos'] ?? [];

            if (!$text) {
                continue;
            }

            if (!is_array($photos)) {
                $photos = [];
            }
            $photos = array_values(array_unique(array_filter($photos, fn ($url) => is_string($url) && trim($url) !== '')));

            $result[] = [
                'name' => $name,
                'date' => $date,
                'text' => $text,
                'photos' => $photos,
            ];
        }

        return $result;
    }

    private function deduplicateParsedReviews(array $parsed): array
    {
        $unique = [];

        foreach ($parsed as $item) {
            if (!is_array($item)) {
                continue;
            }

            $name = isset($item['name']) ? trim((string)$item['name']) : '';
            $date = isset($item['date']) ? trim((string)$item['date']) : '';
            $text = isset($item['text']) ? trim((string)$item['text']) : '';
            $photos = $item['photos'] ?? [];
            if (!is_array($photos)) {
                $photos = [];
            }
            $photos = array_values(array_unique(array_filter($photos, fn ($url) => is_string($url) && trim($url) !== '')));

            if ($name === '' && $date === '' && $text === '') {
                continue;
            }

            $hashBase = mb_strtolower($name . '|' . $date . '|' . $text);
            $hash = hash('sha256', $hashBase);

            if (!isset($unique[$hash])) {
                $unique[$hash] = [
                    'name' => $name !== '' ? $name : null,
                    'date' => $date !== '' ? $date : null,
                    'text' => $text !== '' ? $text : null,
                    'photos' => $photos,
                ];
                continue;
            }

            if (!empty($photos)) {
                $merged = array_merge($unique[$hash]['photos'] ?? [], $photos);
                $unique[$hash]['photos'] = array_values(array_unique($merged));
            }
        }

        return array_values($unique);
    }

    private function extractMarkerPrefix(string $marker, string $suffix): ?string
    {
        $pos = strpos($marker, $suffix);
        if ($pos === false || $pos === 0) {
            return null;
        }

        $prefix = trim(substr($marker, 0, $pos), '/');
        return $prefix !== '' ? $prefix : null;
    }

    private function extractImageUrl(\DOMNode $node): ?string
    {
        if (!$node->attributes) {
            return null;
        }

        $src = $node->attributes->getNamedItem('src')?->nodeValue;
        $dataSrc = $node->attributes->getNamedItem('data-src')?->nodeValue;
        $srcset = $node->attributes->getNamedItem('srcset')?->nodeValue;

        foreach ([$src, $dataSrc] as $candidate) {
            $url = is_string($candidate) ? trim($candidate) : '';
            if ($url !== '') {
                return $url;
            }
        }

        if (is_string($srcset) && trim($srcset) !== '') {
            $first = trim(explode(',', $srcset)[0]);
            $url = trim(explode(' ', $first)[0]);
            if ($url !== '') {
                return $url;
            }
        }

        return null;
    }

    private function looksLikeAvitoAccessBlocked(string $html): bool
    {
        $haystack = mb_strtolower($html, 'UTF-8');

        foreach ([
            'captcha',
            '/captcha',
            'доступ ограничен',
            'подозрительная активность',
            'введите символы с картинки',
            'проверьте, что вы не робот',
        ] as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
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
