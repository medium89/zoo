<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleComment;
use Illuminate\Http\Request;

class ArticlePublicController extends Controller
{
    public function index()
    {
        $articles = Article::latest()->paginate(9);
        return view('articles.index', compact('articles'));
    }

    public function show(Article $article)
    {
        $article->load('images');
        $comments = ArticleComment::where('article_id', $article->id)
            ->where('status', 'approved')
            ->orderBy('created_at')
            ->get();

        return view('articles.show', [
            'article' => $article,
            'comments' => $comments,
        ]);
    }

    public function comment(Request $request, Article $article)
    {
        $data = $request->validate([
            'email' => 'required|email|max:255',
            'content' => 'required|string',
            'parent_id' => 'nullable|exists:article_comments,id',
        ]);

        $data['article_id'] = $article->id;
        $data['status'] = 'pending';

        ArticleComment::create($data);

        return back()->with('success', 'Комментарий отправлен на модерацию');
    }
}
