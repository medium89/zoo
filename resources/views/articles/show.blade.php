@extends('layouts.app')

@section('content')
@include('sections.header-lite')

<section class="article-hero">
    <div class="container">
        <div class="hero-header text-center">
            <h1 class="fw-bold mb-0 article-title-lg">{{ $article->title }}</h1>
        </div>
        @if($article->images->count())
            <div class="article-gallery hero-gallery mt-4">
                @foreach($article->images as $img)
                    <img src="{{ asset('storage/'.$img->path) }}" alt="" class="rounded shadow-sm">
                @endforeach
            </div>
        @endif
    </div>
</section>

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
                        echo '<ul class="list-unstyled" style="margin-left:'.($depth*20).'px">';
                        foreach($tree[$parentId] as $c){
                            echo '<li class="mb-3">';
                            echo '<div class="p-3 border rounded bg-white">';
                            echo '<div class="fw-bold mb-1" style="margin-bottom:5px;">'.$c->email.'</div>';
                            echo '<div class="text-muted" style="font-size:12px;">'.$c->created_at->format('d.m.Y H:i').'</div>';
                            echo '<div class="mt-2">'.e($c->content).'</div>';
                            echo '<button class="btn btn-link btn-sm p-0 mt-2 js-reply" data-id="'.$c->id.'">Ответить</button>';
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
                    <form action="{{ route('articles.comment', $article) }}" method="POST" id="commentForm">
                        @csrf
                        <input type="hidden" name="parent_id" value="">
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
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

@include('sections.footer-lite')

<style>
    .article-hero{
        padding: 72px 0 40px;
        background: linear-gradient(135deg, #0b1f3f 0%, #0f2a52 50%, #122f5d 100%);
        border-bottom: 1px solid #0c1d38;
    }
    .article-title-lg{
        font-size: 2.5rem;
        color: #fff;
    }
    .hero-header{
        display: inline-block;
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
    .hero-gallery img{
        border: 2px solid rgba(255,255,255,0.08);
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
    .article-meta{
        font-size: 0.95rem;
    }
    .comment-form-wrapper{
        max-width: 30%;
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
