<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;

use App\Models\Article;
use App\Models\ArticleComment;
use App\Models\Category;
use App\Services\TelegramNotificationService;
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

        $categorySlug = request('category');
        if ($categorySlug) {
            $q->whereHas('category', function ($sub) use ($categorySlug) {
                $sub->where('slug', $categorySlug);
            });
        }

        $articles = $q->orderBy('order')->orderByDesc('published_at')->orderByDesc('created_at')->paginate(9)->appends(request()->query());
        $categories = Category::whereHas('articles', function($sub){
            $sub->where('active', true);
        })->orderBy('name')->get();

        return view('articles.index', compact('articles', 'search', 'from', 'to', 'categories', 'categorySlug'));
    }

    public function show(Article $article)
    {
        abort_unless($article->active, 404);

        $article->load('images');
        $comments = ArticleComment::where('article_id', $article->id)
            ->where('status', 'approved')
            ->orderBy('order')
            ->orderBy('created_at')
            ->get();

        return view('articles.show', [
            'article' => $article,
            'comments' => $comments,
        ]);
    }

    public function comment(Request $request, Article $article, TelegramNotificationService $telegram)
    {
        $data = $request->validate([
            'email' => 'required|email|max:255',
            'content' => 'required|string|max:5000',
            'parent_id' => 'nullable|exists:article_comments,id',
            'website' => 'nullable|string|max:0',
        ]);

        if (! empty($data['parent_id']) && ! ArticleComment::whereKey($data['parent_id'])
            ->where('article_id', $article->id)
            ->exists()) {
            return back()->withErrors(['parent_id' => 'Ответ можно оставить только на комментарий к этой статье.']);
        }

        $data['article_id'] = $article->id;
        $data['status'] = 'pending';
        $data['order'] = (int)ArticleComment::max('order') + 1;
        unset($data['website']);

        $comment = ArticleComment::create($data);

        $text = "Новый комментарий к статье:\n";
        $text .= "Статья: {$article->title}\n";
        $text .= "Email: {$comment->email}\n";
        $text .= "Текст: ".trim($comment->content);
        $telegram->notifyConfiguredChats($text);

        return back()->with('success', 'Комментарий отправлен на модерацию');
    }
}
