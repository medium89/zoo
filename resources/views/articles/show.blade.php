@extends('layouts.app')

@section('content')
@include('sections.header-lite')

@php 
    $heroBackground = $article->hero_image_path 
        ? asset('storage/'.$article->hero_image_path) 
        : ($article->images->first() ? asset('storage/'.$article->images->first()->path) : null);
@endphp
<section class="article-hero" @if($heroBackground) style="--hero-bg: url('{{ $heroBackground }}');" @endif>
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
        <div class="row g-4">
            <div class="col-lg-3 d-none d-lg-block">
                <div class="card shadow-sm border-0 sticky-top toc-card" style="top: 110px;">
                    <div>
                        <div class="filter-card__header">
                            <span>Содержание</span>
                        </div>
                        <nav id="tocNav" class="toc-nav small">
                            <div class="text-muted">Заголовков нет</div>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="col-lg-9">
                @php
                    $contentHtml = $article->content;
                    if ($article->excerpt) {
                        $pos = strpos($contentHtml, $article->excerpt);
                        if ($pos === 0) {
                            $contentHtml = substr($contentHtml, strlen($article->excerpt));
                        }
                    }
                @endphp
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body article-content" id="articleContent">
                        {!! $contentHtml !!}
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
                                <div class="visually-hidden" aria-hidden="true"><label>Сайт<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
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
        </div>
    </div>
</section>

@include('sections.footer-lite')

<style>
    .article-hero{
        position: relative;
        display: flex;
        height: 350px;
        padding: 0;
        align-items: center;
        background-color: var(--color-secondary);
        background-image: var(--hero-bg, none), url('/assets/img/bg.png');
        background-repeat: no-repeat, repeat;
        background-position: center, center;
        background-size: cover, 6%;
        color: #fff;
        box-shadow: inset 0 -1px 0 rgba(255,255,255,0.18);
        overflow: hidden;
        isolation: isolate;
    }
    .article-hero::before{
        position: absolute;
        inset: 0;
        z-index: 0;
        background: linear-gradient(90deg, rgba(25,18,32,.62), rgba(38,25,48,.38) 56%, rgba(25,18,32,.24));
        content: '';
    }
    .hero-container{
        position: relative;
        z-index: 1;
        display: flex;
        min-height: 100%;
        justify-content: center;
        align-items: center;
        text-align: center;
    }
    .article-title-lg{
        max-width: 1050px;
        font-size: clamp(3rem, 5vw, 4.25rem);
        line-height: 1.08;
        letter-spacing: -.035em;
        color: #fff;
        text-shadow: 0 5px 24px rgba(0,0,0,.38);
    }
    .hero-header{
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 14px 26px;
        background: transparent;
        border-radius: 14px;
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
        padding: 44px 48px 48px;
    }
    .article-content h1, .article-content h2, .article-content h3{
        display: block;
        width: auto;
        font-weight: 700;
        text-transform: none;
        opacity: 1 !important;
        transform: none !important;
        animation: none !important;
        position: static;
    }
    .article-content h1{
        margin: 0 0 1.25rem;
        font-size: clamp(2rem, 3.4vw, 3rem);
        line-height: 1.15;
    }
    .article-content h2{
        margin: 2.15rem 0 .85rem !important;
        color: var(--color-primary);
        font-size: clamp(1.35rem, 2vw, 1.7rem);
        line-height: 1.28;
    }
    .article-content h3{
        margin: 1.65rem 0 .7rem !important;
        font-size: clamp(1.15rem, 1.7vw, 1.4rem);
        line-height: 1.3;
    }
    .article-content h1::after,
    .article-content h2::after,
    .article-content h3::after{
        display: none !important;
    }
    .article-content p{
        line-height: 1.7;
        margin: 0 0 1rem;
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
    /* Toc as vertical navbar style */
    .toc-card{
        border-radius: 14px;
        overflow: hidden;
    }
    .toc-title{
        font-size: 1.05rem;
        letter-spacing: 0.3px;
        color: #fff;
        background: #8c4dc7;
        color: #fff;
        border-radius: 12px;
        padding: 8px 12px;
    }
    .toc-title h5{
        font-size: 1.3rem;
        padding: 0.6rem 0rem 0rem 1rem;
        color: #fff;
    }
    .toc-nav{
        display: block;
    }
    .toc-nav a{
        display: block;
        margin: 0;
        padding: 7px 10px;
        border: none;
        border-radius: 9px;
        background: transparent;
        box-shadow: inset 0 0 0 1px transparent;
        text-decoration: none;
        font-size: 0.97rem;
        letter-spacing: 0.02em;
        text-transform: none;
        color: var(--color-primary);
        transition: color .2s ease, background-color .2s ease, box-shadow .2s ease;
    }
    .toc-nav a:last-child{
        margin-bottom: 0;
    }
    .toc-nav a:hover{
        color: var(--color-secondary);
    }
    .toc-nav a.active{
        color: var(--color-secondary);
        background: rgba(140,77,199,.055);
        box-shadow: inset 0 0 0 1px var(--color-secondary);
    }
    .toc-nav .toc-sub{
        margin-left: 0;
    }
    .toc-nav .toc-list{
        margin: 0;
        padding: 14px 18px 18px;
        list-style: none;
    }
    .toc-nav .toc-item{
        position: relative;
        padding-left: 18px;
    }
    .toc-nav .toc-item::before{
        position: absolute;
        top: 14px;
        left: 1px;
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: var(--color-secondary);
        content: '';
    }
    .toc-nav .toc-item-h3{
        margin-left: 10px;
    }
    .toc-nav .toc-item-h3::before{
        width: 5px;
        height: 5px;
    }
    .article-content ul{
        margin: .25rem 0 1.2rem;
        padding-left: 1.45rem;
    }
    .article-content li{
        margin-bottom: .38rem;
        padding-left: .2rem;
        line-height: 1.55;
    }
    .article-content li::marker{
        color: var(--color-secondary);
        font-size: 1.15em;
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
    /* For consistency with filter header style */
    .filter-card__header{
        background: #8c4dc7;
        color: #fff;
        padding: 14px 18px;
        font-weight: 700;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        border-top-left-radius: 0.5rem;
        border-top-right-radius: 0.5rem;
    }
    @media (max-width: 991.98px){
        .article-hero{
            height: 290px;
        }
        .article-title-lg{
            font-size: clamp(2.35rem, 6vw, 3.25rem);
        }
        .comment-form-wrapper{
            max-width: 100%;
        }
    }
    @media (max-width: 767.98px){
        .article-hero{
            height: 240px;
            padding: 0;
            background-position: 68% center, center;
        }
        .article-title-lg{
            font-size: clamp(1.75rem, 8vw, 2.35rem);
        }
        .hero-header{
            padding: 14px 8px;
        }
        .article-content{
            padding: 32px 22px 36px;
        }
        .article-content h2{
            margin-top: 1.85rem !important;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const form = document.getElementById('commentForm');
    const parentInput = form.querySelector('input[name="parent_id"]');
    const replyInfo = document.getElementById('replyInfo');
    const replyText = replyInfo.querySelector('span');
    const tocNav = document.getElementById('tocNav');
    const articleContent = document.getElementById('articleContent');

    if (tocNav && articleContent) {
        const headings = articleContent.querySelectorAll('h2, h3');
        const list = document.createElement('ul');
        list.classList.add('toc-list');
        headings.forEach((h, idx) => {
            if (!h.id) {
                h.id = 'section-' + (idx + 1);
            }
            const li = document.createElement('li');
            li.className = h.tagName.toLowerCase() === 'h2' ? 'toc-item toc-item-h2' : 'toc-item toc-item-h3';
            const a = document.createElement('a');
            a.href = '#' + h.id;
            a.textContent = h.textContent.trim();
            li.appendChild(a);
            list.appendChild(li);
        });
        tocNav.innerHTML = '';
        tocNav.appendChild(list);

        const links = Array.from(tocNav.querySelectorAll('a'));
        let scrollTicking = false;
        const setActiveLink = (index) => {
            links.forEach((link, linkIndex) => link.classList.toggle('active', linkIndex === index));
        };
        const updateActiveLink = () => {
            let activeIndex = 0;
            headings.forEach((heading, index) => {
                if (heading.getBoundingClientRect().top <= 150) activeIndex = index;
            });
            setActiveLink(activeIndex);
            scrollTicking = false;
        };
        const requestActiveLinkUpdate = () => {
            if (scrollTicking) return;
            scrollTicking = true;
            window.requestAnimationFrame(updateActiveLink);
        };

        updateActiveLink();
        window.addEventListener('scroll', requestActiveLinkUpdate, {passive: true});
        window.addEventListener('resize', requestActiveLinkUpdate);

        tocNav.addEventListener('click', (e)=>{
            if (e.target.tagName.toLowerCase() !== 'a') return;
            e.preventDefault();
            setActiveLink(links.indexOf(e.target));
            const id = e.target.getAttribute('href').replace('#','');
            const target = document.getElementById(id);
            if (target) target.scrollIntoView({behavior:'smooth', block:'start'});
        });
    }

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
