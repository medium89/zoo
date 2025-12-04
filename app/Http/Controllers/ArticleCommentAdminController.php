<?php

namespace App\Http\Controllers;

use App\Models\ArticleComment;
use Illuminate\Http\Request;

class ArticleCommentAdminController extends Controller
{
    public function index()
    {
        $comments = ArticleComment::latest()->paginate(20);
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
}
