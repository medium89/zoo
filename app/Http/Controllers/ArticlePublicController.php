<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleComment;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ArticlePublicController extends Controller
{
    public function index()
    {
        $q = Article::with('images')->where('active', true);

        $search = request('search');
        $from = request('from');
        $to = request('to');

        if ($search) {
            $q->where(function($sub) use ($search){
                $sub->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        if ($from) {
            $fromDate = Carbon::parse($from)->startOfDay();
            $q->whereDate('published_at', '>=', $fromDate);
        }

        if ($to) {
            $toDate = Carbon::parse($to)->endOfDay();
            $q->whereDate('published_at', '<=', $toDate);
        }

        $articles = $q->orderByDesc('published_at')->orderByDesc('created_at')->paginate(9)->appends(request()->query());

        return view('articles.index', compact('articles', 'search', 'from', 'to'));
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
