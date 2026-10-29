<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChangeLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ChangeLogController extends Controller
{
    public function index()
    {
        $logs = ChangeLog::with(['user', 'revertedBy'])->latest('id')->paginate(30);
        return view('admin.change-logs.index', compact('logs'));
    }

    public function show(ChangeLog $changeLog)
    {
        return view('admin.change-logs.show', compact('changeLog'));
    }

    public function revert(ChangeLog $changeLog): RedirectResponse
    {
        if ($changeLog->reverted_at) {
            return back()->with('error', 'Это изменение уже отменено.');
        }

        try {
            DB::transaction(function () use ($changeLog) {
                $changes = $changeLog->changes ?? [];
                $media = $changeLog->media_backups ?? [];
                $this->assertCurrentState($changes, $media);
                Schema::disableForeignKeyConstraints();
                try {
                    foreach ($changes as $change) {
                        if ($change['kind'] !== 'row') continue;
                        $table = $change['table'];
                        $id = $change['id'];
                        if ($change['before'] === null) {
                            DB::table($table)->where('id', $id)->delete();
                        } else {
                            DB::table($table)->updateOrInsert(['id' => $id], $change['before']);
                        }
                    }
                } finally {
                    Schema::enableForeignKeyConstraints();
                }

                foreach ($changes as $change) {
                    if ($change['kind'] !== 'file') continue;
                    $path = public_path($change['path']);
                    if ($change['before'] === null) {
                        File::delete($path);
                    } else {
                        File::put($path, base64_decode($change['before'], true));
                    }
                }
                foreach ($media as $original => $backup) {
                    $source = Storage::disk('local')->readStream($backup);
                    try {
                        if (!$source || !Storage::disk('public')->writeStream($original, $source)) {
                            throw new RuntimeException('Не удалось восстановить изображение: ' . $original);
                        }
                    } finally {
                        if (is_resource($source)) fclose($source);
                    }
                }
                $changeLog->reverted_at = now();
                $changeLog->reverted_by = auth()->id();
                $changeLog->save();
            });
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.logs.show', $changeLog)
            ->with('success', 'Изменение отменено, прежнее состояние восстановлено.');
    }

    private function assertCurrentState(array $changes, array $media): void
    {
        foreach ($changes as $change) {
            if ($change['kind'] === 'row') {
                $row = DB::table($change['table'])->where('id', $change['id'])->first();
                $actual = $row ? (array) $row : null;
                if ($actual !== $change['after']) {
                    throw new RuntimeException('Эти данные уже изменились после записи в журнал. Автоматическая отмена остановлена, чтобы не затереть новые правки.');
                }
            } elseif ($change['kind'] === 'file') {
                $path = public_path($change['path']);
                $actual = File::exists($path) ? base64_encode(File::get($path)) : null;
                if ($actual !== $change['after']) {
                    throw new RuntimeException('Файл ' . $change['path'] . ' уже изменился. Автоматическая отмена остановлена.');
                }
            }
        }
        foreach ($media as $original => $backup) {
            if (!Storage::disk('local')->exists($backup)) {
                throw new RuntimeException('Резервная копия изображения недоступна: ' . $original);
            }
            if (Storage::disk('public')->exists($original)) {
                throw new RuntimeException('Исходный путь изображения уже занят: ' . $original);
            }
        }
    }
}
