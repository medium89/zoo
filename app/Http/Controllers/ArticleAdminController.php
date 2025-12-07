<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleImage;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Support\ImageProcessor;
use Illuminate\Support\Str;

class ArticleAdminController extends Controller
{
    public function index()
    {
        $articles = Article::with('category')->orderBy('order')->latest()->paginate(10);
        return view('admin.articles.index', compact('articles'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        return view('admin.articles.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'published_at' => 'nullable|date',
            'slug' => 'nullable|string|max:255|unique:articles,slug',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:255',
            'seo_robots' => 'nullable|string|max:255',
            'seo_charset' => 'nullable|string|max:50',
            'cover' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:12288',
            'image_scale' => 'nullable|integer|min:10|max:100',
            'image_quality' => 'nullable|integer|min:40|max:100',
            'active' => 'nullable|boolean',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:12288',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        if (empty($data['published_at'])) {
            $data['published_at'] = Carbon::now();
        }
        $data['active'] = $request->boolean('active', true);
        $data['slug'] = $this->makeSlug($data['slug'] ?? null, $data['title']);
        $data['seo_robots'] = $data['seo_robots'] ?? 'index, follow';
        $data['seo_charset'] = $data['seo_charset'] ?? 'UTF-8';
        $data['order'] = (int)Article::max('order') + 1;

        $article = Article::create($data);
        $scale = (int)$request->input('image_scale', 100);
        $quality = (int)$request->input('image_quality', 85);

        if ($request->hasFile('cover')) {
            $article->cover_path = ImageProcessor::processAndStore($request->file('cover'), 'articles/covers', $scale, $quality);
            $article->save();
        }

        $this->storeImages($request, $article, $scale, $quality);

        return redirect()->route('admin.articles.index')->with('success', 'Статья добавлена');
    }

    public function edit(Article $article)
    {
        $article->load('images','category');
        $categories = Category::orderBy('name')->get();
        return view('admin.articles.edit', compact('article','categories'));
    }

    public function update(Request $request, Article $article)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'published_at' => 'nullable|date',
            'slug' => 'nullable|string|max:255|unique:articles,slug,' . $article->id,
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:255',
            'seo_robots' => 'nullable|string|max:255',
            'seo_charset' => 'nullable|string|max:50',
            'cover' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:12288',
            'image_scale' => 'nullable|integer|min:10|max:100',
            'image_quality' => 'nullable|integer|min:40|max:100',
            'active' => 'nullable|boolean',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:12288',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $data['active'] = $request->boolean('active', true);
        $data['slug'] = $this->makeSlug($data['slug'] ?? $article->slug, $data['title'], $article->id);
        $data['seo_robots'] = $data['seo_robots'] ?? 'index, follow';
        $data['seo_charset'] = $data['seo_charset'] ?? 'UTF-8';
        $article->update($data);
        $scale = (int)$request->input('image_scale', 100);
        $quality = (int)$request->input('image_quality', 85);

        if ($request->hasFile('cover')) {
            if ($article->cover_path && Storage::disk('public')->exists($article->cover_path)) {
                Storage::disk('public')->delete($article->cover_path);
            }
            $article->cover_path = ImageProcessor::processAndStore($request->file('cover'), 'articles/covers', $scale, $quality);
            $article->save();
        }

        $this->storeImages($request, $article, $scale, $quality);

        return redirect()->route('admin.articles.edit', $article)->with('success', 'Статья обновлена');
    }

    public function destroy(Article $article)
    {
        foreach ($article->images as $img) {
            if ($img->path && Storage::disk('public')->exists($img->path)) {
                Storage::disk('public')->delete($img->path);
            }
        }
        $article->delete();
        return redirect()->route('admin.articles.index')->with('success', 'Статья удалена');
    }

    private function storeImages(Request $request, Article $article, int $scale = 100, int $quality = 85): void
    {
        if (!$request->hasFile('images')) return;
        $order = (int)($article->images()->max('order') ?? 0) + 1;
        foreach ($request->file('images') as $file) {
            if (!$file) continue;
            $path = ImageProcessor::processAndStore($file, 'articles', $scale, $quality);
            ArticleImage::create([
                'article_id' => $article->id,
                'path' => $path,
                'order' => $order++,
            ]);
        }
    }

    public function uploadImage(Request $request)
    {
        // CKEditor 5 can send the file as "upload" (ckfinder/simpleUpload) or "file" (custom adapters)
        $field = $request->file('upload') ? 'upload' : 'file';

        $request->validate([
            $field => 'required|image|mimes:jpeg,png,jpg,webp,gif|max:12288',
        ]);

        $path = $request->file($field)->store('articles', 'public');
        return response()->json([
            'uploaded' => true,
            'url' => asset('storage/'.$path),
        ]);
    }

    private function makeSlug(?string $candidate, string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($candidate ?: $title) ?: 'article';
        $slug = $base;
        $i = 1;
        while (
            Article::where('slug', $slug)
                ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    public function updateStatus(Request $request)
    {
        foreach ($request->input('statuses', []) as $id => $status) {
            if ($model = Article::find($id)) {
                $model->active = (bool)$status;
                $model->save();
            }
        }
        foreach ($request->input('orders', []) as $id => $order) {
            if ($model = Article::find($id)) {
                $model->order = (int)$order;
                $model->save();
            }
        }

        return back()->with('success', 'Изменения сохранены');
    }
}
