@extends('layouts.app')

@section('content')
@include('sections.header')

<section class="article-hero">
    <div class="container">
        <h1 class="fw-bold mb-2">{{ $article->title }}</h1>
        <div class="d-flex gap-3 align-items-center text-muted">
            <span><i class="fa fa-calendar me-1"></i>{{ $article->published_at? $article->published_at->format('d.m.Y') : $article->created_at->format('d.m.Y') }}</span>
            <span><i class="fa fa-clock me-1"></i>{{ $article->created_at->format('H:i') }}</span>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        @if($article->images->count())
            <div class="article-gallery mb-4">
                @foreach($article->images as $img)
                    <img src="{{ asset('storage/'.$img->path) }}" alt="" class="rounded shadow-sm">
                @endforeach
            </div>
        @endif
        <div class="card shadow-sm border-0 mb-5">
            <div class="card-body article-content">
                {!! $article->content !!}
            </div>
        </div>

        <div class="card">
            <div class="card-body">
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
                            echo '<div class="fw-bold">'.$c->email.'</div>';
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
                <div id="comments">{!! $render(0,0) !!}</div>

                <h5 class="card-title mt-4">Оставить комментарий</h5>
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
</section>

@include('sections.footer-lite')

<style>
    .article-hero{
        padding: 72px 0 40px;
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 60%);
        border-bottom: 1px solid #e9ecef;
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
    .article-content h1, .article-content h2, .article-content h3{
        margin-top: 1.4rem;
    }
    .article-content p{
        line-height: 1.7;
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
