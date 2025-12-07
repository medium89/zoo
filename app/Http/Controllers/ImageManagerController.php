<?php

namespace App\Http\Controllers;

use App\Models\About;
use App\Models\Advantage;
use App\Models\Article;
use App\Models\ArticleImage;
use App\Models\Gallery;
use App\Models\Service;
use App\Models\Slider;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use App\Support\ImageProcessor;

class ImageManagerController extends Controller
{
    public function index(Request $request)
    {
        $items = $this->collectImages();
        $perPage = 20;
        $page = (int)$request->query('page', 1);

        $paginator = new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => url()->current(), 'query' => $request->query()]
        );

        return view('admin.images.index', [
            'images' => $paginator,
        ]);
    }

    public function refresh(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|string',
            'id' => 'required',
            'field' => 'required|string',
            'path' => 'required|string',
            'scale' => 'required|integer|min:10|max:100',
            'quality' => 'required|integer|min:40|max:100',
        ]);

        $path = $data['path'];
        $fullPath = Storage::disk('public')->path($path);
        if (!Storage::disk('public')->exists($path)) {
            return back()->with('error', 'Файл не найден на диске');
        }

        $target = $this->findModel($data['type'], $data['id']);
        if (!$target) {
            return back()->with('error', 'Запись не найдена');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'img');
        copy($fullPath, $tmp);
        $uploaded = new UploadedFile($tmp, basename($path), mime_content_type($fullPath) ?: null, null, true);
        $dir = trim(dirname($path), '.');
        $newPath = ImageProcessor::processAndStore($uploaded, ltrim($dir, '/'), (int)$data['scale'], (int)$data['quality']);

        $this->updateModelPath($target, $data['field'], $newPath);

        if ($path !== $newPath && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        return back()->with('success', 'Изображение обновлено');
    }

    private function collectImages(): Collection
    {
        $items = collect();

        Slider::orderBy('id')->get()->each(function($slider) use ($items){
            $items->push($this->mapItem('slider', $slider->id, 'image', $slider->image, 'Слайдер #'.$slider->id));
            if ($slider->text_bg) {
                $items->push($this->mapItem('slider_bg', $slider->id, 'text_bg', $slider->text_bg, 'Слайдер фон текста #'.$slider->id));
            }
        });

        Advantage::orderBy('id')->get()->each(function($adv) use ($items){
            if ($adv->image) {
                $items->push($this->mapItem('advantage', $adv->id, 'image', $adv->image, 'Преимущество #'.$adv->id));
            }
        });

        Service::orderBy('id')->get()->each(function($service) use ($items){
            if ($service->image) {
                $items->push($this->mapItem('service', $service->id, 'image', $service->image, 'Услуга #'.$service->id));
            }
        });

        Gallery::orderBy('id')->get()->each(function($gallery) use ($items){
            if ($gallery->image) {
                $items->push($this->mapItem('gallery', $gallery->id, 'image', $gallery->image, 'Галерея #'.$gallery->id));
            }
        });

        Article::orderBy('id')->get()->each(function($article) use ($items){
            if ($article->cover_path) {
                $items->push($this->mapItem('article_cover', $article->id, 'cover_path', $article->cover_path, 'Обложка статьи #'.$article->id));
            }
            if ($article->hero_image_path) {
                $items->push($this->mapItem('article_hero', $article->id, 'hero_image_path', $article->hero_image_path, 'Hero статьи #'.$article->id));
            }
        });

        ArticleImage::orderBy('id')->get()->each(function($img) use ($items){
            if ($img->path) {
                $items->push($this->mapItem('article_image', $img->id, 'path', $img->path, 'Изображение статьи #'.$img->article_id.' ('.$img->id.')'));
            }
        });

        $about = About::first();
        if ($about && $about->image) {
            $items->push($this->mapItem('about', $about->id, 'image', $about->image, 'Блок «Обо мне»'));
        }

        return $items->filter(fn($item) => !empty($item['path']));
    }

    private function mapItem(string $type, $id, string $field, string $path, string $label): array
    {
        $sizeBytes = Storage::disk('public')->exists($path) ? Storage::disk('public')->size($path) : null;
        $sizeKb = $sizeBytes ? round($sizeBytes / 1024, 1) : null;
        $sizeMb = $sizeBytes ? round($sizeBytes / 1024 / 1024, 2) : null;

        return [
            'type' => $type,
            'id' => $id,
            'field' => $field,
            'path' => $path,
            'label' => $label,
            'url' => asset('storage/'.$path),
            'size_kb' => $sizeKb,
            'size_mb' => $sizeMb,
        ];
    }

    private function findModel(string $type, $id)
    {
        return match($type){
            'slider', 'slider_bg' => Slider::find($id),
            'advantage' => Advantage::find($id),
            'service' => Service::find($id),
            'gallery' => Gallery::find($id),
            'article_cover', 'article_hero' => Article::find($id),
            'article_image' => ArticleImage::find($id),
            'about' => About::find($id),
            default => null,
        };
    }

    private function updateModelPath($model, string $field, string $newPath): void
    {
        $model->{$field} = $newPath;
        $model->save();
    }
}
