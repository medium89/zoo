<?php

namespace App\Http\Middleware;

use App\Models\ChangeLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RecordAdminChanges
{
    private const TABLES = [
        'site_settings', 'settings', 'sliders', 'about', 'advantages', 'services',
        'galleries', 'socials', 'nav_links', 'avito_reviews', 'site_reviews',
        'articles', 'article_images', 'article_comments', 'categories', 'feedback',
        'users', 'clients', 'animals', 'client_photos', 'animal_photos',
        'boardings', 'boarding_tasks', 'boarding_task_runs', 'boarding_task_messages',
        'service_orders', 'service_order_animals', 'service_order_services',
        'service_tariffs', 'telegram_bot_settings', 'homepage_orders',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') || $request->isMethod('HEAD')
            || $request->routeIs('admin.logs.*') || !Schema::hasTable('change_logs')) {
            return $next($request);
        }

        return DB::transaction(function () use ($request, $next) {
            $before = $this->snapshot();
            $filesBefore = $this->siteFiles();
            $request->attributes->set('audit_token', (string) Str::uuid());
            $request->attributes->set('audit_media_backups', []);
            $response = $next($request);
            $after = $this->snapshot();
            $changes = $this->diff($before, $after);
            foreach ($this->siteFiles() as $path => $content) {
                if ($filesBefore[$path] !== $content) {
                    $changes[] = [
                        'kind' => 'file', 'path' => $path,
                        'before' => $filesBefore[$path], 'after' => $content,
                    ];
                }
            }
            $media = $request->attributes->get('audit_media_backups', []);
            if ($changes || $media) {
                ChangeLog::create([
                    'user_id' => $request->user()?->id,
                    'route' => $request->route()?->getName(),
                    'method' => $request->method(),
                    'summary' => $this->summary($request, $changes, $media),
                    'changes' => $changes,
                    'media_backups' => $media,
                ]);
            }
            return $response;
        });
    }

    private function snapshot(): array
    {
        $snapshot = [];
        foreach (self::TABLES as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'id')) continue;
            $snapshot[$table] = DB::table($table)->get()->mapWithKeys(
                fn ($row) => [(string) $row->id => (array) $row]
            )->all();
        }
        return $snapshot;
    }

    private function siteFiles(): array
    {
        $files = [];
        foreach (['robots.txt', 'sitemap.xml'] as $name) {
            $path = public_path($name);
            $files[$name] = File::exists($path) ? base64_encode(File::get($path)) : null;
        }
        return $files;
    }

    private function diff(array $before, array $after): array
    {
        $changes = [];
        foreach (self::TABLES as $table) {
            $oldRows = $before[$table] ?? [];
            $newRows = $after[$table] ?? [];
            foreach (array_unique(array_merge(array_keys($oldRows), array_keys($newRows))) as $id) {
                $old = $oldRows[$id] ?? null;
                $new = $newRows[$id] ?? null;
                if ($old === $new) continue;
                $changes[] = [
                    'kind' => 'row', 'table' => $table, 'id' => $id,
                    'before' => $old, 'after' => $new,
                ];
            }
        }
        return $changes;
    }

    private function summary(Request $request, array $changes, array $media): string
    {
        $route = $request->route()?->getName() ?: $request->path();
        $count = count($changes);
        return Str::limit($route . ' — ' . $count . ' изменений' . ($media ? ', файлы: ' . count($media) : ''), 255, '');
    }
}
