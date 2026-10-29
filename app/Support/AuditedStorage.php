<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class AuditedStorage
{
    public static function deletePublic(string|array $paths): bool
    {
        $paths = array_values(array_filter((array) $paths));
        $public = Storage::disk('public');
        $token = request()->attributes->get('audit_token');
        if ($token) {
            $backups = request()->attributes->get('audit_media_backups', []);
            foreach ($paths as $path) {
                if (!$public->exists($path) || isset($backups[$path])) continue;
                $backup = 'audit-media/' . $token . '/' . sha1($path);
                $source = $public->readStream($path);
                try {
                    if (!$source || !Storage::disk('local')->writeStream($backup, $source)) {
                        throw new \RuntimeException('Не удалось сохранить копию файла перед удалением.');
                    }
                } finally {
                    if (is_resource($source)) fclose($source);
                }
                $backups[$path] = $backup;
            }
            request()->attributes->set('audit_media_backups', $backups);
        }
        return $public->delete($paths);
    }
}
