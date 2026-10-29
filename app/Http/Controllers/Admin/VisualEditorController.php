<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\About;
use App\Models\Advantage;
use App\Models\AvitoReview;
use App\Models\Gallery;
use App\Models\HomepageOrder;
use App\Models\NavLink;
use App\Models\Service;
use App\Models\SiteReview;
use App\Models\SiteSetting;
use App\Models\Slider;
use App\Models\Social;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Support\ImageProcessor;
use App\Support\AuditedStorage;
use Illuminate\Validation\ValidationException;

class VisualEditorController extends Controller
{
    private const FIELDS = [
        'about' => ['model' => About::class, 'rich' => ['text'], 'plain' => []],
        'advantage' => ['model' => Advantage::class, 'rich' => ['short_text', 'text'], 'plain' => ['title']],
        'service' => ['model' => Service::class, 'rich' => ['text'], 'plain' => ['title'], 'color' => ['sticker_color', 'tape_color']],
        'social' => ['model' => Social::class, 'rich' => ['text'], 'plain' => ['title']],
        'slider' => ['model' => Slider::class, 'rich' => [], 'plain' => ['text']],
        'avito_review' => ['model' => AvitoReview::class, 'rich' => [], 'plain' => ['name', 'text']],
        'site_review' => ['model' => SiteReview::class, 'rich' => [], 'plain' => ['name', 'text']],
        'settings' => ['model' => SiteSetting::class, 'rich' => [], 'plain' => ['hero_text'], 'color' => ['advantages_bg_color']],
        'nav_link' => ['model' => NavLink::class, 'rich' => [], 'plain' => ['label', 'href']],
    ];

    private const LABELS = [
        'about' => 'Обо мне', 'advantages' => 'Преимущества',
        'services' => 'Услуги', 'gallery' => 'Фотоальбом',
        'reviews' => 'Отзывы клиентов', 'contacts' => 'Контакты',
        'contact_form' => 'Оставить заявку', 'socials' => 'Или напишите напрямую',
    ];
    public function field(Request $request): JsonResponse
    {
        [$model, $field, $mode] = $this->resolve($request);
        $value = $field === 'home_label'
            ? (($model->home_labels ?? [])[$request->input('key')] ?? self::LABELS[$request->input('key')])
            : (str_starts_with($field, 'tag:') ? ($model->tags[(int) substr($field, 4)] ?? '') : ($model->$field ?? ''));
        return response()->json([
            'value' => $value,
            'mode' => $mode,
            'version' => $this->versionFor($model, $field, $request->input('key')),
        ]);
    }

    public function saveField(Request $request): JsonResponse
    {
        [$model, $field, $mode] = $this->resolve($request);
        $data = $request->validate(['value' => 'present|string|max:20000', 'version' => 'nullable|string|max:64']);
        if (($data['version'] ?? null) !== $this->versionFor($model, $field, $request->input('key'))) {
            throw ValidationException::withMessages(['version' => 'Этот текст уже изменён в другой вкладке. Обновите страницу.']);
        }
        $value = trim($data['value']);
        if ($mode === 'plain') {
            $value = trim(strip_tags($value));
            if (mb_strlen($value) > ($field === 'text' ? 5000 : 255)) {
                throw ValidationException::withMessages(['value' => 'Текст слишком длинный.']);
            }
        }
        if ($mode === 'color') {
            if ($value !== '' && !preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
                throw ValidationException::withMessages(['value' => 'Укажите цвет в формате #RRGGBB.']);
            }
            $value = $value === '' ? null : strtolower($value);
        }
        if ($field === 'href' && !self::safeHref($value)) {
            throw ValidationException::withMessages(['value' => 'Используйте якорь #..., адрес /... или ссылку https://...']);
        }
        if ($field === 'home_label') {
            if ($value === '') throw ValidationException::withMessages(['value' => 'Заголовок не может быть пустым.']);
            $labels = $model->home_labels ?? [];
            $labels[$request->input('key')] = $value;
            $model->home_labels = $labels;
        } elseif (str_starts_with($field, 'tag:')) {
            if ($value === '') throw ValidationException::withMessages(['value' => 'Тег не может быть пустым.']);
            $tags = $model->tags ?? [];
            $tags[(int) substr($field, 4)] = $value;
            $model->tags = $tags;
        } else {
            if ($value === '' && !in_array($field, ['hero_text', 'short_text', 'name', 'sticker_color', 'tape_color', 'advantages_bg_color'], true)) {
                throw ValidationException::withMessages(['value' => 'Поле не может быть пустым.']);
            }
            $model->$field = $value;
        }
        $model->save();
        return response()->json(['value' => $value, 'mode' => $mode, 'version' => $this->versionFor($model, $field, $request->input('key'))]);
    }
    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'orders' => 'required|array|min:1|max:7',
            'orders.*' => 'required|array|min:1|max:300',
            'orders.*.*' => 'required|string|max:90',
        ]);
        $allowed = ['advantages', 'services', 'galleries', 'socials', 'sliders', 'reviews', 'nav_links'];
        DB::transaction(function () use ($data, $allowed) {
            foreach ($data['orders'] as $group => $items) {
                if (!in_array($group, $allowed, true)) abort(422);
                $this->applyOrder($group, $items);
            }
        });
        return response()->json(['ok' => true]);
    }

    private function applyOrder(string $group, array $items): void
    {
        if ($group === 'reviews') {
            $expected = AvitoReview::where('status', 'published')->pluck('id')->map(fn ($id) => 'avito:' . $id)
                ->concat(SiteReview::where('status', 'published')->pluck('id')->map(fn ($id) => 'site:' . $id))->all();
            if (count($items) !== count($expected) || array_diff($items, $expected) || array_diff($expected, $items)) {
                throw ValidationException::withMessages(['orders' => 'Список отзывов изменился. Обновите страницу.']);
            }
            foreach ($items as $position => $key) {
                HomepageOrder::updateOrCreate(['section' => 'reviews', 'item_key' => $key], ['position' => $position]);
            }
            return;
        }
        [$class, $column] = [
            'advantages' => [Advantage::class, 'order'],
            'services' => [Service::class, 'order'],
            'galleries' => [Gallery::class, 'number'],
            'socials' => [Social::class, 'order'],
            'sliders' => [Slider::class, 'order'],
            'nav_links' => [NavLink::class, 'order'],
        ][$group];
        $records = $class::where('active', true)->orderBy($column)->orderBy('id')->get()->keyBy('id');
        if (count($items) !== count(array_unique($items)) || ($group !== 'galleries' && count($items) !== $records->count())) {
            throw ValidationException::withMessages(['orders' => 'Список элементов изменился. Обновите страницу.']);
        }
        foreach ($items as $id) {
            if (!ctype_digit((string) $id) || !$records->has((int) $id)) {
                throw ValidationException::withMessages(['orders' => 'Список элементов изменился. Обновите страницу.']);
            }
        }
        $ordered = collect($items)->map(fn ($id) => $records->get((int) $id))
            ->concat($records->except(array_map('intval', $items))->values());
        foreach ($ordered->values() as $position => $record) {
            $record->$column = $position + 1;
            $record->save();
        }
    }
    private static function safeHref(?string $value): bool
    {
        return (bool) preg_match('~^(#[A-Za-z0-9_-]+|/(?!/)[^\s]*|https?://[^\s]+)$~i', (string) $value);
    }

    public function createNavLink(Request $request): JsonResponse
    {
        $data = $request->validate(['label' => 'required|string|max:80', 'href' => 'required|string|max:255']);
        if (!self::safeHref($data['href'])) {
            throw ValidationException::withMessages(['href' => 'Используйте якорь #..., адрес /... или ссылку https://...']);
        }
        $link = NavLink::create([
            'key' => 'custom_' . Str::lower(Str::random(12)),
            'label' => trim(strip_tags($data['label'])),
            'href' => $data['href'],
            'active' => true,
            'order' => ((int) NavLink::max('order')) + 1,
        ]);
        return response()->json(['id' => $link->id, 'label' => $link->label, 'href' => $link->href], 201);
    }

    public function saveImage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => 'required|string|max:30', 'id' => 'nullable|integer|min:1',
            'field' => 'required|string|max:40',
            'image' => 'required|file|mimetypes:image/jpeg,image/png,image/gif,image/webp,image/avif,image/bmp,image/x-ms-bmp,image/svg+xml|max:12288',
        ]);
        $config = [
            'settings' => [SiteSetting::class, 'hero_image' => 'hero'],
            'about' => [About::class, 'image' => 'about'],
            'advantage' => [Advantage::class, 'image' => 'advantages'],
            'service' => [Service::class, 'sticker_image' => 'services/stickers', 'sticker_doodle' => 'services/sticker-doodles'],
            'gallery' => [Gallery::class, 'image' => 'galleries'],
            'slider' => [Slider::class, 'image' => 'sliders'],
        ][$data['type']] ?? null;
        if (!$config || !isset($config[$data['field']])) abort(422);
        $model = in_array($data['type'], ['settings', 'about'], true)
            ? ($config[0]::first() ?? new $config[0]())
            : $config[0]::findOrFail($data['id']);
        $old = $model->{$data['field']};
        $path = $data['type'] === 'service' && $data['field'] === 'sticker_doodle'
            ? $request->file('image')->store($config[$data['field']], 'public')
            : ImageProcessor::processAndStore($request->file('image'), $config[$data['field']]);
        $model->{$data['field']} = $path;
        $model->save();
        if ($old && $old !== $path && Storage::disk('public')->exists($old)) AuditedStorage::deletePublic($old);
        return response()->json(['url' => asset('storage/' . $path), 'path' => $path]);
    }

    private function versionFor(Model $model, string $field, ?string $key): string
    {
        $value = $field === 'home_label'
            ? (($model->home_labels ?? [])[$key] ?? self::LABELS[$key])
            : (str_starts_with($field, 'tag:') ? ($model->tags[(int) substr($field, 4)] ?? '') : ($model->$field ?? ''));
        return hash('sha256', json_encode([$model->getKey(), $field, $key, $value], JSON_UNESCAPED_UNICODE));
    }
    private function resolve(Request $request): array
    {
        $data = $request->validate([
            'type' => 'required|string|max:30',
            'id' => 'nullable|integer|min:1',
            'field' => 'required|string|max:40',
            'key' => 'nullable|string|max:40',
        ]);
        if ($data['type'] === 'label') {
            if (!isset(self::LABELS[$data['key'] ?? '']) || $data['field'] !== 'home_label') abort(422);
            return [SiteSetting::first() ?? new SiteSetting(), 'home_label', 'plain'];
        }
        $config = self::FIELDS[$data['type']] ?? null;
        if (!$config) abort(422);
        $field = $data['field'];
        if ($data['type'] === 'service' && preg_match('/^tag:(\d+)$/', $field, $match)) {
            $model = Service::findOrFail($data['id']);
            if (!array_key_exists((int) $match[1], $model->tags ?? [])) abort(404);
            return [$model, $field, 'plain'];
        }
        $mode = in_array($field, $config['rich'], true) ? 'rich' : (in_array($field, $config['plain'], true) ? 'plain' : (in_array($field, $config['color'] ?? [], true) ? 'color' : null));
        if (!$mode) abort(422);
        $model = $data['type'] === 'settings'
            ? (SiteSetting::first() ?? new SiteSetting())
            : $config['model']::findOrFail($data['id']);
        return [$model, $field, $mode];
    }
}
