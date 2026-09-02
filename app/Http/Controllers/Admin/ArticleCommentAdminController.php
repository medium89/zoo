<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\ArticleComment;
use Illuminate\Http\Request;

class ArticleCommentAdminController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|in:pending,approved,rejected',
        ]);
        $comments = ArticleComment::with('article')
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(fn ($items) => $items->where('email', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%")
                    ->orWhereHas('article', fn ($articles) => $articles->where('title', 'like', "%{$search}%")));
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderBy('order')->latest()->paginate(20)->withQueryString();

        return view('admin.articles.comments', compact('comments', 'filters'));
    }

    public function update(Request $request, ArticleComment $article_comment)
    {
        $data = $request->validate([
            'status' => 'required|string|in:pending,approved,rejected',
        ]);
        $article_comment->update($data);
        return back()->with('success', 'Статус обновлён');
    }

    public function destroy(ArticleComment $article_comment)
    {
        $article_comment->delete();
        return back()->with('success', 'Комментарий удалён');
    }

    public function updateStatus(Request $request)
    {
        foreach ($request->input('statuses', []) as $id => $status) {
            if ($comment = ArticleComment::find($id)) {
                $comment->status = $status;
                $comment->save();
            }
        }
        foreach ($request->input('orders', []) as $id => $order) {
            if ($comment = ArticleComment::find($id)) {
                $comment->order = (int)$order;
                $comment->save();
            }
        }

        return back()->with('success', 'Изменения сохранены');
    }
}
