<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:12288',
        ]);

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
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:12288',
        ]);

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
}
