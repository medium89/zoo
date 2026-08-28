<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Gallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{
    public function index()
    {
        $galleries = Gallery::orderBy('number')->get();
        return view('admin.galleries.index', compact('galleries'));
    }

    public function create()
    {
        return view('admin.galleries.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'images.*'   => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:12288',
            'scales'     => 'nullable|array',
            'scales.*'   => 'nullable|integer|min:10|max:100',
            'qualities'  => 'nullable|array',
            'qualities.*'=> 'nullable|integer|min:40|max:100',
        ]);

        $scale = (int)($request->input('scale', 100));
        $quality = (int)($request->input('quality', 85));

        if ($request->hasFile('images')) {
            $nextNumber = (int)Gallery::max('number') + 1;
            $scales = $request->input('scales', []);
            $qualities = $request->input('qualities', []);
            foreach ($request->file('images') as $i => $uploaded) {
                $scale = (int)($scales[$i] ?? 100);
                $scale = max(10, min(100, $scale));
                $quality = (int)($qualities[$i] ?? 85);
                $quality = max(40, min(100, $quality));
                $origPath = $uploaded->getRealPath();
                $origExt = strtolower($uploaded->getClientOriginalExtension());
                $mime = $uploaded->getMimeType();

                // Decide if we can process via GD
                $processable = preg_match('/jpeg|jpg|png|gif|webp/i', (string)$mime);

                $filenameBase = pathinfo($uploaded->getClientOriginalName(), PATHINFO_FILENAME);
                $safeBase = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $filenameBase) ?: 'img';
                $unique = $safeBase . '_' . time() . '_' . substr(uniqid('', true), -6);

                $storePath = 'galleries/' . $unique . '.' . ($origExt ?: 'jpg');
                $thumbPath = 'galleries/thumbs/' . $unique . '.' . ($origExt ?: 'jpg');

                if (!$processable) {
                    // Store original as-is (e.g., SVG)
                    $stored = $uploaded->storeAs('galleries', $unique . '.' . $origExt, 'public');
                    // No thumbnail generation for non-raster formats
                    Gallery::create([
                        'image'   => $stored,
                        'active'  => true,
                        'number'  => $nextNumber++,
                    ]);
                    continue;
                }

                // Load image via GD
                $imgInfo = @getimagesize($origPath);
                if (!$imgInfo) {
                    // Fallback to raw store if cannot read
                    $stored = $uploaded->storeAs('galleries', $unique . '.' . $origExt, 'public');
                    Gallery::create([
                        'image'   => $stored,
                        'active'  => true,
                        'number'  => $nextNumber++,
                    ]);
                    continue;
                }

                [$w, $h] = [$imgInfo[0], $imgInfo[1]];
                $targetW = max(1, (int)round($w * $scale / 100));
                $targetH = max(1, (int)round($h * $scale / 100));

                $src = null;
                $type = $imgInfo[2]; // IMAGETYPE_*
                if ($type === IMAGETYPE_JPEG) $src = @imagecreatefromjpeg($origPath);
                elseif ($type === IMAGETYPE_PNG) $src = @imagecreatefrompng($origPath);
                elseif ($type === IMAGETYPE_GIF) $src = @imagecreatefromgif($origPath);
                elseif (defined('IMAGETYPE_WEBP') && $type === IMAGETYPE_WEBP && function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($origPath);

                if (!$src) {
                    $stored = $uploaded->storeAs('galleries', $unique . '.' . $origExt, 'public');
                    Gallery::create([
                        'image'   => $stored,
                        'active'  => true,
                        'number'  => $nextNumber++,
                    ]);
                    continue;
                }

                // Prepare destination canvas
                $dst = imagecreatetruecolor($targetW, $targetH);
                if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
                    imagealphablending($dst, false);
                    imagesavealpha($dst, true);
                    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                    imagefilledrectangle($dst, 0, 0, $targetW, $targetH, $transparent);
                }
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetW, $targetH, $w, $h);

                // Encode main image
                ob_start();
                if ($type === IMAGETYPE_JPEG) {
                    imagejpeg($dst, null, max(0, min(100, $quality)));
                    $ext = ($origExt ?: 'jpg');
                } elseif ($type === IMAGETYPE_PNG) {
                    $level = (int)max(0, min(9, round((100 - $quality) / 10)));
                    imagepng($dst, null, $level);
                    $ext = ($origExt ?: 'png');
                } elseif (defined('IMAGETYPE_WEBP') && $type === IMAGETYPE_WEBP && function_exists('imagewebp')) {
                    imagewebp($dst, null, max(0, min(100, $quality)));
                    $ext = 'webp';
                } else { // GIF
                    imagegif($dst);
                    $ext = ($origExt ?: 'gif');
                }
                $data = ob_get_clean();
                imagedestroy($dst);
                imagedestroy($src);

                $storePath = 'galleries/' . $unique . '.' . $ext;
                Storage::disk('public')->put($storePath, $data);

                // Generate thumbnail (width >= 443px)
                try {
                    $thumbW = 443;
                    $ratio = $w > 0 ? $h / $w : 1;
                    $thumbH = max(1, (int)round($thumbW * $ratio));

                    $src2 = null;
                    if ($type === IMAGETYPE_JPEG) $src2 = @imagecreatefromstring($data);
                    elseif ($type === IMAGETYPE_PNG) $src2 = @imagecreatefromstring($data);
                    elseif ($type === IMAGETYPE_GIF) $src2 = @imagecreatefromstring($data);
                    if ($src2) {
                        $dst2 = imagecreatetruecolor($thumbW, $thumbH);
                        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
                            imagealphablending($dst2, false);
                            imagesavealpha($dst2, true);
                            $transparent = imagecolorallocatealpha($dst2, 0, 0, 0, 127);
                            imagefilledrectangle($dst2, 0, 0, $thumbW, $thumbH, $transparent);
                        }
                        imagecopyresampled($dst2, $src2, 0, 0, 0, 0, $thumbW, $thumbH, imagesx($src2), imagesy($src2));
                        ob_start();
                        if ($type === IMAGETYPE_JPEG) {
                            imagejpeg($dst2, null, 80);
                            $thumbExt = 'jpg';
                        } elseif ($type === IMAGETYPE_PNG) {
                            imagepng($dst2, null, 4);
                            $thumbExt = 'png';
                        } else {
                            imagegif($dst2);
                            $thumbExt = 'gif';
                        }
                        $thumbData = ob_get_clean();
                        imagedestroy($dst2);
                        imagedestroy($src2);
                        $thumbPath = 'galleries/thumbs/' . $unique . '.' . $thumbExt;
                        Storage::disk('public')->put($thumbPath, $thumbData);
                    }
                } catch (\Throwable $e) {
                    // ignore thumbnail errors
                }

                Gallery::create([
                    'image'   => $storePath,
                    'active'  => true,
                    'number'  => $nextNumber++,
                ]);
            }
        }
        return redirect()->route('admin.galleries.index')->with('success', 'Фото добавлены');
    }

    public function destroy(Gallery $gallery)
    {
        if ($gallery->image && Storage::disk('public')->exists($gallery->image)) {
            Storage::disk('public')->delete($gallery->image);
        }
        $gallery->delete();
        return redirect()->route('admin.galleries.index')->with('success', 'Фото удалено');
    }

    public function updateStatus(Request $request)
    {
        foreach ($request->input('statuses', []) as $id => $status) {
            if ($gallery = Gallery::find($id)) {
                $gallery->active = (bool)$status;
                $gallery->save();
            }
        }
        foreach ($request->input('numbers', []) as $id => $number) {
            if ($gallery = Gallery::find($id)) {
                $gallery->number = (int)$number;
                $gallery->save();
            }
        }
        return back()->with('success', 'Данные обновлены');
    }
}
