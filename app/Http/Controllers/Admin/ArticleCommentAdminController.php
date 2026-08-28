<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\ArticleComment;
use Illuminate\Http\Request;

class ArticleCommentAdminController extends Controller
{
    public function index()
    {
        $comments = ArticleComment::orderBy('order')->latest()->paginate(20);
        return view('admin.articles.comments', compact('comments'));
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
