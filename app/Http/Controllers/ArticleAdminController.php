<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ArticleAdminController extends Controller
{
    public function index()
    {
        $articles = Article::latest()->paginate(10);
        return view('admin.articles.index', compact('articles'));
    }

    public function create()
    {
        return view('admin.articles.create');
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
            'active' => 'nullable|boolean',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:12288',
        ]);

        if (empty($data['published_at'])) {
            $data['published_at'] = Carbon::now();
        }
        $data['active'] = $request->boolean('active', true);
        $data['slug'] = $this->makeSlug($data['slug'] ?? null, $data['title']);
        $data['seo_robots'] = $data['seo_robots'] ?? 'index, follow';
        $data['seo_charset'] = $data['seo_charset'] ?? 'UTF-8';

        $article = Article::create($data);
        $this->storeImages($request, $article);

        return redirect()->route('admin.articles.index')->with('success', 'Статья добавлена');
    }

    public function edit(Article $article)
    {
        $article->load('images');
        return view('admin.articles.edit', compact('article'));
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
            'active' => 'nullable|boolean',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:12288',
        ]);

        $data['active'] = $request->boolean('active', true);
        $data['slug'] = $this->makeSlug($data['slug'] ?? $article->slug, $data['title'], $article->id);
        $data['seo_robots'] = $data['seo_robots'] ?? 'index, follow';
        $data['seo_charset'] = $data['seo_charset'] ?? 'UTF-8';
        $article->update($data);
        $this->storeImages($request, $article);

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

    private function storeImages(Request $request, Article $article): void
    {
        if (!$request->hasFile('images')) return;
        $order = (int)($article->images()->max('order') ?? 0) + 1;
        foreach ($request->file('images') as $file) {
            if (!$file) continue;
            $path = $file->store('articles', 'public');
            ArticleImage::create([
                'article_id' => $article->id,
                'path' => $path,
                'order' => $order++,
            ]);
        }
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,webp,gif|max:12288',
        ]);
        $path = $request->file('file')->store('articles', 'public');
        return response()->json(['location' => asset('storage/'.$path)]);
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
}
