<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticleComment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

    public function test_public_articles_are_newest_first_by_default_and_can_be_reversed(): void
    {
        Article::create([
            'title' => 'Старая статья',
            'slug' => 'old-article',
            'content' => 'Текст',
            'active' => true,
            'order' => 1,
            'published_at' => '2026-01-10 12:00:00',
        ]);
        Article::create([
            'title' => 'Новая статья',
            'slug' => 'new-article',
            'content' => 'Текст',
            'active' => true,
            'order' => 999,
            'published_at' => '2026-08-10 12:00:00',
        ]);

        $this->get(route('articles.index'))
            ->assertOk()
            ->assertSeeInOrder(['Новая статья', 'Старая статья']);

        $this->get(route('articles.index', ['sort' => 'oldest']))
            ->assertOk()
            ->assertSeeInOrder(['Старая статья', 'Новая статья']);
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
            'author_name' => 'Анна',
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
            'author_name' => 'Анна',
            'email' => 'reader@example.test',
            'content' => 'Чужой комментарий',
            'status' => 'approved',
            'order' => 1,
        ]);

        $this->post(route('articles.comment', $article), [
            'author_name' => 'Анна',
            'email' => 'reader@example.test',
            'content' => 'Ответ',
            'parent_id' => $foreignComment->id,
        ])->assertSessionHasErrors('parent_id');

        $this->assertDatabaseMissing('article_comments', ['content' => 'Ответ']);
    }

    public function test_safe_comment_is_published_automatically(): void
    {
        config(['services.aitunnel.api_key' => 'test-key']);
        Http::fake([
            'https://api.aitunnel.ru/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => ['content' => json_encode(['decision' => 'approve', 'reasons' => []])],
                ]],
            ]),
        ]);

        $article = Article::create([
            'title' => 'Полезная статья',
            'slug' => 'safe-comment-article',
            'content' => 'Текст',
            'active' => true,
        ]);

        $this->post(route('articles.comment', $article), [
            'author_name' => 'Мария',
            'email' => 'private@example.test',
            'content' => 'Спасибо за понятную и полезную статью.',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('article_comments', [
            'article_id' => $article->id,
            'author_name' => 'Мария',
            'status' => 'approved',
        ]);

    }

    public function test_comment_marked_as_harmful_stays_on_manual_review(): void
    {
        config(['services.aitunnel.api_key' => 'test-key']);
        Http::fake([
            'https://api.aitunnel.ru/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => ['content' => json_encode(['decision' => 'review', 'reasons' => ['оскорбления']])],
                ]],
            ]),
        ]);

        $article = Article::create([
            'title' => 'Статья для проверки',
            'slug' => 'review-comment-article',
            'content' => 'Текст',
            'active' => true,
        ]);

        $this->post(route('articles.comment', $article), [
            'author_name' => 'Иван',
            'email' => 'private@example.test',
            'content' => 'Оскорбительный комментарий',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('article_comments', [
            'article_id' => $article->id,
            'status' => 'pending',
        ]);
    }
}
