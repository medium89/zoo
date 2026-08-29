<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;

use App\Models\Article;
use App\Models\ArticleComment;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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

    public function comment(Request $request, Article $article)
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

        $token = env('TELEGRAM_BOT_TOKEN');
        $chatIds = [
            env('TELEGRAM_CHAT_ID'),
            env('TELEGRAM_CHAT_ID_2')
        ];
        $text = "Новый комментарий к статье:\n";
        $text .= "Статья: {$article->title}\n";
        $text .= "Email: {$comment->email}\n";
        $text .= "Текст: ".trim($comment->content);
        foreach ($chatIds as $id) {
            if ($token && $id) {
                Http::get("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $id,
                    'text'    => $text,
                ]);
            }
        }

        return back()->with('success', 'Комментарий отправлен на модерацию');
    }
}
