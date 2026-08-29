<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticleComment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticlePublicationSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_article_is_not_publicly_available(): void
    {
        $article = Article::create([
            'title' => 'Черновик',
            'slug' => 'private-draft',
            'content' => 'Не для публикации',
            'active' => false,
        ]);

        $this->get(route('articles.show', $article))->assertNotFound();
    }

    public function test_new_comment_is_sent_to_moderation(): void
    {
        $article = Article::create([
            'title' => 'Статья',
            'slug' => 'published-article',
            'content' => 'Текст',
            'active' => true,
        ]);

        $this->post(route('articles.comment', $article), [
            'email' => 'reader@example.test',
            'content' => 'Новый комментарий',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('article_comments', [
            'article_id' => $article->id,
            'content' => 'Новый комментарий',
            'status' => 'pending',
        ]);
    }

    public function test_comment_cannot_reply_to_comment_from_another_article(): void
    {
        $article = Article::create(['title' => 'Первая', 'slug' => 'first-article', 'content' => 'Текст', 'active' => true]);
        $other = Article::create(['title' => 'Вторая', 'slug' => 'second-article', 'content' => 'Текст', 'active' => true]);
        $foreignComment = ArticleComment::create([
            'article_id' => $other->id,
            'email' => 'reader@example.test',
            'content' => 'Чужой комментарий',
            'status' => 'approved',
            'order' => 1,
        ]);

        $this->post(route('articles.comment', $article), [
            'email' => 'reader@example.test',
            'content' => 'Ответ',
            'parent_id' => $foreignComment->id,
        ])->assertSessionHasErrors('parent_id');

        $this->assertDatabaseMissing('article_comments', ['content' => 'Ответ']);
    }
}
