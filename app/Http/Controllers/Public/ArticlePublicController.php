<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;

use App\Models\Article;
use App\Models\ArticleComment;
use App\Services\AitunnelService;
use Throwable;
use App\Models\Category;
use App\Services\TelegramNotificationService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ArticlePublicController extends Controller
{
    public function index(Request $request)
    {
        $q = Article::with('images')->where('active', true);

        $search = request('search');
        $from = request('from');
        $to = request('to');
        $sort = in_array($request->query('sort'), ['newest', 'oldest'], true)
            ? $request->query('sort')
            : 'newest';

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

        $dateDirection = $sort === 'oldest' ? 'asc' : 'desc';
        $articles = $q
            ->orderByRaw("COALESCE(published_at, created_at) {$dateDirection}")
            ->orderBy('id', $dateDirection)
            ->paginate(9)
            ->appends(request()->query());
        $categories = Category::whereHas('articles', function($sub){
            $sub->where('active', true);
        })->orderBy('name')->get();

        return view('articles.index', compact('articles', 'search', 'from', 'to', 'sort', 'categories', 'categorySlug'));
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


    public function comment(
        Request $request,
        Article $article,
        TelegramNotificationService $telegram,
        AitunnelService $aitunnel,
    ) {
        $data = $request->validate([
            'author_name' => 'required|string|max:100',
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

        $moderation = ['publish' => false, 'reasons' => []];
        try {
            $moderation = $aitunnel->moderateArticleComment($data['content']);
        } catch (Throwable) {
            // Ошибка или недоступность AI не должна автоматически публиковать комментарий.
        }

        $data['article_id'] = $article->id;
        $data['status'] = $moderation['publish'] ? 'approved' : 'pending';
        $data['order'] = (int) ArticleComment::max('order') + 1;
        unset($data['website']);

        $comment = ArticleComment::create($data);

        $text = "Новый комментарий к статье:\n";
        $text .= "Статья: {$article->title}\n";
        $text .= "Автор: {$comment->author_name}\n";
        $text .= "Email: {$comment->email}\n";
        $text .= 'Статус: '.($comment->status === 'approved' ? 'опубликован автоматически' : 'на ручной проверке')."\n";
        if ($moderation['reasons'] !== []) {
            $text .= 'Причины проверки: '.implode(', ', $moderation['reasons'])."\n";
        }
        $text .= "Текст: ".trim($comment->content);
        $telegram->notifyConfiguredChats($text);

        return back()->with(
            'success',
            $comment->status === 'approved'
                ? 'Комментарий опубликован.'
                : 'Комментарий отправлен на модерацию.'
        );
    }
}
