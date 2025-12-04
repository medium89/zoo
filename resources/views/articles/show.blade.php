@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="mb-4">
        <a href="{{ route('articles.index') }}" class="btn btn-outline-secondary btn-sm">← ко всем статьям</a>
    </div>
    <h1 class="mb-3">{{ $article->title }}</h1>
    <div class="text-muted mb-3">{{ $article->created_at->format('d.m.Y H:i') }}</div>
    @if($article->images->count())
        <div class="d-flex flex-wrap gap-2 mb-3">
            @foreach($article->images as $img)
                <img src="{{ asset('storage/'.$img->path) }}" alt="" style="height:180px;border-radius:8px;object-fit:cover;">
            @endforeach
        </div>
    @endif
    <div class="mb-5" style="white-space:pre-wrap;">{!! $article->content !!}</div>

    <hr>
    <h3 class="mt-4">Комментарии</h3>
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
                echo '<div class="p-3 border rounded">';
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

    <div class="card mt-4">
        <div class="card-body">
            <h5 class="card-title">Оставить комментарий</h5>
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
