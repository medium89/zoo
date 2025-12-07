<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageProcessor
{
    public static function processAndStore(UploadedFile $uploaded, string $dir, int $scale = 100, int $quality = 85, ?int $cropWidth = null, ?int $cropHeight = null, ?int $cropX = null, ?int $cropY = null): string
    {
        $scale = max(10, min(100, (int)$scale));
        $quality = max(40, min(100, (int)$quality));

        $origPath = $uploaded->getRealPath();
        $origExt = strtolower($uploaded->getClientOriginalExtension());
        $mime = (string)$uploaded->getMimeType();

        // Only raster formats supported for processing
        $processable = (bool)preg_match('/jpeg|jpg|png|gif|webp/i', $mime);
        if (!$processable) {
            return $uploaded->store($dir, 'public');
        }

        $imgInfo = @getimagesize($origPath);
        if (!$imgInfo) {
            return $uploaded->store($dir, 'public');
        }

        [$w, $h] = [$imgInfo[0], $imgInfo[1]];
        $targetW = max(1, (int)round($w * $scale / 100));
        $targetH = max(1, (int)round($h * $scale / 100));

        $type = $imgInfo[2]; // IMAGETYPE_*
        $src = null;
        if ($type === IMAGETYPE_JPEG) $src = @imagecreatefromjpeg($origPath);
        elseif ($type === IMAGETYPE_PNG) $src = @imagecreatefrompng($origPath);
        elseif ($type === IMAGETYPE_GIF) $src = @imagecreatefromgif($origPath);
        elseif (defined('IMAGETYPE_WEBP') && $type === IMAGETYPE_WEBP && function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($origPath);

        if (!$src) {
            return $uploaded->store($dir, 'public');
        }

        // Optional crop (custom position, defaults to center)
        if ($cropWidth || $cropHeight) {
            $cropW = $cropWidth ? min($cropWidth, $w) : $w;
            $cropH = $cropHeight ? min($cropHeight, $h) : $h;
            $srcX = $cropX !== null ? max(0, min($w - $cropW, $cropX)) : max(0, (int)(($w - $cropW) / 2));
            $srcY = $cropY !== null ? max(0, min($h - $cropH, $cropY)) : max(0, (int)(($h - $cropH) / 2));
            $crop = imagecreatetruecolor($cropW, $cropH);
            if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF || (defined('IMAGETYPE_WEBP') && $type === IMAGETYPE_WEBP)) {
                imagealphablending($crop, false);
                imagesavealpha($crop, true);
                $transparent = imagecolorallocatealpha($crop, 0, 0, 0, 127);
                imagefilledrectangle($crop, 0, 0, $cropW, $cropH, $transparent);
            }
            imagecopy($crop, $src, 0, 0, $srcX, $srcY, $cropW, $cropH);
            imagedestroy($src);
            $src = $crop;
            [$w, $h] = [$cropW, $cropH];
            $targetW = max(1, (int)round($w * $scale / 100));
            $targetH = max(1, (int)round($h * $scale / 100));
        }

        $dst = imagecreatetruecolor($targetW, $targetH);
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF || (defined('IMAGETYPE_WEBP') && $type === IMAGETYPE_WEBP)) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, $targetW, $targetH, $transparent);
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetW, $targetH, $w, $h);

        ob_start();
        if ($type === IMAGETYPE_JPEG) {
            imagejpeg($dst, null, $quality);
            $ext = $origExt ?: 'jpg';
        } elseif ($type === IMAGETYPE_PNG) {
            $level = (int)max(0, min(9, round((100 - $quality) / 10)));
            imagepng($dst, null, $level);
            $ext = $origExt ?: 'png';
        } elseif (defined('IMAGETYPE_WEBP') && $type === IMAGETYPE_WEBP && function_exists('imagewebp')) {
            imagewebp($dst, null, $quality);
            $ext = 'webp';
        } else { // GIF
            imagegif($dst);
            $ext = $origExt ?: 'gif';
        }
        $data = ob_get_clean();
        imagedestroy($dst);
        imagedestroy($src);

        $base = pathinfo($uploaded->getClientOriginalName(), PATHINFO_FILENAME);
        $safeBase = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $base) ?: 'img';
        $unique = $safeBase . '_' . time() . '_' . substr(uniqid('', true), -6);
        $storePath = rtrim($dir, '/').'/'.$unique.'.'.$ext;
        Storage::disk('public')->put($storePath, $data);
        return $storePath;
    }
}
