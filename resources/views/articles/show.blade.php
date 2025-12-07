@extends('layouts.app')

@section('content')
@include('sections.header-lite')

@php $heroImage = $article->images->first(); @endphp
<section class="article-hero" @if($heroImage) style="--hero-bg: url('{{ asset('storage/'.$heroImage->path) }}');" @endif>
    <div class="container hero-container">
        <div class="hero-header text-center">
            <h1 class="fw-bold mb-0 article-title-lg">{{ $article->title }}</h1>
        </div>
    </div>
</section>

<nav class="article-breadcrumbs">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="/">Главная</a></li>
            <li class="breadcrumb-item"><a href="{{ route('articles.index') }}">Статьи</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $article->title }}</li>
        </ol>
    </div>
</nav>

<section class="py-5 bg-light">
    <div class="container">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body article-content">
                {!! $article->content !!}
                <div class="article-meta text-muted mt-4">
                    Опубликовано: {{ $article->published_at? $article->published_at->format('d.m.Y') : $article->created_at->format('d.m.Y') }}
                </div>
            </div>
        </div>

        <div class="card comment-card border-0 bg-transparent shadow-none">
            <div class="p-4">
                <h5 class="card-title">Комментарии</h5>
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @php
                    $tree = [];
                    foreach($comments as $c){ $tree[$c->parent_id ?? 0][] = $c; }
                    $render = function($parentId, $depth) use (&$render, $tree){
                        if(!isset($tree[$parentId])) return;
                        echo '<ul class="comment-tree list-unstyled">';
                        foreach($tree[$parentId] as $c){
                            echo '<li class="mb-3">';
                            echo '<div class="comment-item" style="--level:'.$depth.'">';
                            echo '<div class="comment-meta d-flex justify-content-between align-items-start gap-3">';
                            echo '<div class="comment-author">'.$c->email.'</div>';
                            echo '<div class="comment-date text-muted">'.$c->created_at->format('d.m.Y H:i').'</div>';
                            echo '</div>';
                            echo '<div class="comment-text mt-2">'.nl2br(e($c->content)).'</div>';
                            echo '<button class="btn btn-link btn-sm p-0 mt-3 comment-reply js-reply" data-id="'.$c->id.'">Ответить</button>';
                            echo '</div>';
                            $render($c->id, $depth+1);
                            echo '</li>';
                        }
                        echo '</ul>';
                    };
                @endphp
                <div id="article-comments">{!! $render(0,0) !!}</div>

                <h5 class="card-title mt-4 mb-3">Оставить комментарий</h5>
                <div class="comment-form-wrapper">
                    <form action="{{ route('articles.comment', $article) }}" method="POST" id="commentForm" class="comment-form card border-0 shadow-sm">
                        @csrf
                        <input type="hidden" name="parent_id" value="">
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Текст</label>
                                <textarea name="content" class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="mb-2 text-muted small" id="replyInfo" style="display:none;">Ответ на комментарий #<span></span> <button type="button" class="btn btn-link btn-sm p-0" id="cancelReply">отменить</button></div>
                            <button class="btn btn-primary">Отправить</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

@include('sections.footer-lite')

<style>
    .article-hero{
        --hero-bg: linear-gradient(135deg, #0b1f3f 0%, #0f2a52 50%, #122f5d 100%);
        position: relative;
        padding: 140px 0 120px;
        background-image: linear-gradient(135deg, rgba(6,12,28,0.75), rgba(7,18,41,0.75)), var(--hero-bg);
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        overflow: hidden;
    }
    .hero-container{
        position: relative;
        z-index: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        text-align: center;
    }
    .article-title-lg{
        font-size: 2.6rem;
        color: #fff;
    }
    .hero-header{
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 22px 36px;
        background: rgba(8, 18, 44, 0.85);
        border-radius: 14px;
        box-shadow: 0 12px 30px rgba(0,0,0,0.25);
        backdrop-filter: blur(4px);
    }
    .hero-header h1{
        color: #fff;
        margin: 0;
    }
    .article-breadcrumbs{
        padding: 0.85rem 0;
        background: #f3f5f9;
        border-bottom: 1px solid #e1e7ef;
    }
    .article-breadcrumbs .breadcrumb{
        margin: 0;
        background: transparent;
        padding: 0;
        font-size: 0.95rem;
        align-items: center;
        gap: 6px;
    }
    .article-breadcrumbs a{
        color: #0d6efd;
        text-decoration: none;
        font-weight: 600;
        background: #fff;
        padding: 6px 10px;
        border-radius: 999px;
        border: 1px solid #e4e8ef;
    }
    .article-breadcrumbs .breadcrumb-item.active{
        color: #6c757d;
        font-weight: 600;
    }
    .article-breadcrumbs .breadcrumb-item + .breadcrumb-item::before{
        color: #adb5bd;
    }
    .article-gallery{
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    }
    .article-gallery img{
        width: 100%;
        height: 180px;
        object-fit: cover;
    }
    .article-content{
        padding: 3rem 3rem 3rem 4rem;
    }
    .article-content h1, .article-content h2, .article-content h3{
        margin-top: 1.4rem;
        font-size: inherit;
        font-weight: 600;
        text-transform: none;
        opacity: 1 !important;
        transform: none !important;
        animation: none !important;
        position: static;
    }
    .article-content h1::after,
    .article-content h2::after,
    .article-content h3::after{
        display: none !important;
    }
    .article-content p{
        line-height: 1.7;
        margin-bottom: 0;
    }
    .article-content img{
        max-width: 100%;
        height: auto;
        display: block;
        margin: 1rem 0;
    }
    .article-meta{
        font-size: 0.95rem;
    }
    .comment-tree{
        margin: 0;
        padding: 0;
    }
    .comment-item{
        position: relative;
        margin-left: calc(var(--level) * 18px);
        border: 1px solid #e5e9f0;
        background: #fff;
        border-radius: 14px;
        padding: 14px 16px;
        box-shadow: 0 8px 22px rgba(31,35,42,0.06);
    }
    .comment-meta{
        gap: 10px;
    }
    .comment-author{
        font-weight: 700;
        font-size: 1rem;
    }
    .comment-date{
        font-size: 0.85rem;
        white-space: nowrap;
    }
    .comment-text{
        font-size: 0.97rem;
        line-height: 1.55;
        color: #1f2630;
    }
    .comment-reply{
        font-weight: 600;
        color: #0d6efd;
    }
    .comment-form-wrapper{
        max-width: 38%;
    }
    .comment-form.card{
        border-radius: 16px;
    }
    .comment-card{
        border: 0;
        background: transparent;
        box-shadow: none;
    }
    @media (max-width: 991.98px){
        .comment-form-wrapper{
            max-width: 100%;
        }
    }
    @media (max-width: 767.98px){
        .article-hero{
            padding: 120px 0 90px;
        }
        .article-title-lg{
            font-size: 2rem;
        }
        .hero-header{
            padding: 16px 22px;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const form = document.getElementById('commentForm');
    const parentInput = form.querySelector('input[name="parent_id"]');
    const replyInfo = document.getElementById('replyInfo');
    const replyText = replyInfo.querySelector('span');
    document.querySelectorAll('.js-reply').forEach(btn=>{
        btn.addEventListener('click', ()=>{
            const id = btn.dataset.id;
            parentInput.value = id;
            replyText.textContent = id;
            replyInfo.style.display = 'block';
            form.scrollIntoView({behavior:'smooth'});
        });
    });
    document.getElementById('cancelReply').addEventListener('click', ()=>{
        parentInput.value = '';
        replyInfo.style.display = 'none';
    });
});
</script>
@endsection
