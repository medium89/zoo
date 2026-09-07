@extends('layouts.app')

@section('content')
@include('sections.header-lite')

<section class="article-hero">
    <div class="container text-center">
        <h1 class="fw-bold mb-0 display-5">Статьи</h1>
    </div>
</section>

<nav class="article-breadcrumbs">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="/">Главная</a></li>
            <li class="breadcrumb-item active" aria-current="page">Статьи</li>
        </ol>
    </div>
</nav>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-3">
                <div class="card shadow-sm border-0 sticky-top filter-card" style="top: 90px;">
                    <div class="filter-card__header">
                        <span>Фильтр</span>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('articles.index') }}" method="GET" class="d-flex flex-column gap-3">
                            <div>
                                <label class="form-label small text-muted mb-1">Поиск по названию</label>
                                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Введите слова">
                            </div>
                            <div>
                                <label class="form-label small text-muted mb-1">Дата с</label>
                                <input type="date" name="from" value="{{ request('from') }}" class="form-control">
                            </div>
                            <div>
                                <label class="form-label small text-muted mb-1">Дата по</label>
                                <input type="date" name="to" value="{{ request('to') }}" class="form-control">
                            </div>
                            @if(!empty($categories) && $categories->count())
                            <div>
                                <label class="form-label small text-muted mb-1">Категория</label>
                                <select name="category" class="form-select">
                                    <option value="">Все категории</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->slug }}" {{ ($categorySlug ?? '') === $cat->slug ? 'selected' : '' }}>{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                            <div class="d-flex gap-2">
                                <button class="btn btn-primary flex-grow-1" type="submit">Применить</button>
                                <a href="{{ route('articles.index') }}" class="btn btn-outline-secondary" title="Сбросить"><i class="fa fa-rotate-left"></i></a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-9">
                <div class="row g-4">
                    @forelse($articles as $article)
                        <div class="col-md-6 col-xl-4">
                            <article class="card h-100 border-0 article-card">
                                @php
                                    $cover = $article->cover_path ? asset('storage/'.$article->cover_path) : null;
                                    if(!$cover && $article->images->first()){
                                        $cover = asset('storage/'.$article->images->first()->path);
                                    }
                                    $placeholder = 'data:image/svg+xml;utf8,'.rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="600" height="360" viewBox="0 0 600 360"><rect width="600" height="360" fill="%23415366"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="%23ffffff" font-size="24" font-family="Arial, sans-serif">Нет изображения</text></svg>');
                                @endphp
                                <a href="{{ route('articles.show', $article) }}" class="article-card__cover-link">
                                    <img src="{{ $cover ?? $placeholder }}" class="article-card__cover" alt="{{ $article->title }}">
                                </a>
                                <div class="card-body article-card__body">
                                    <h4 class="article-card__title">
                                        <a href="{{ route('articles.show', $article) }}" class="article-card__title-link">{{ $article->title }}</a>
                                    </h4>
                                    @if($article->excerpt)
                                        <p class="article-card__excerpt">{{ strip_tags($article->excerpt) }}</p>
                                    @endif
                                    <div class="article-card__footer">
                                        <time datetime="{{ ($article->published_at ?: $article->created_at)->toDateString() }}">{{ ($article->published_at ?: $article->created_at)->locale('ru')->translatedFormat('j F Y') }}</time>
                                        <a href="{{ route('articles.show', $article) }}" class="article-card__read-more">Читать <span aria-hidden="true">→</span></a>
                                    </div>
                                </div>
                            </article>
                        </div>
                    @empty
                        <div class="col-12 text-muted">Статей пока нет.</div>
                    @endforelse
                </div>
                <div class="mt-4">{{ $articles->links() }}</div>
            </div>
        </div>
    </div>
</section>

@include('sections.footer-lite')

<style>
    .article-hero{
        padding: 2rem 0 2.5rem;
        background: var(--color-secondary) url('/assets/img/bg.png') repeat center center;
        background-size: 6%;
        color: #fff;
        box-shadow: inset 0 -1px 0 rgba(255,255,255,0.18);
    }
    .article-hero h1{
        color:#fff;
        text-shadow: 0 6px 18px rgba(0,0,0,0.18);
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
        gap: 6px;
        align-items: center;
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
    .article-card{
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 8px 24px rgba(37,31,52,.08);
        overflow: hidden;
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .article-card:hover{
        transform: translateY(-3px);
        box-shadow: 0 14px 32px rgba(37,31,52,.13);
    }
    .article-card__cover-link{
        display: block;
        aspect-ratio: 3 / 2;
        overflow: hidden;
        background: #f3eff7;
    }
    .article-card__cover{
        display: block;
        width: 100%;
        height: 100%;
        object-fit: contain;
        object-position: center;
    }
    .article-card__body{
        display: flex;
        flex: 1;
        flex-direction: column;
        padding: 18px 18px 16px;
    }
    .article-card__title{
        display: -webkit-box;
        min-height: 3.75em;
        margin: 0 0 11px;
        overflow: hidden;
        color: #24202b;
        font-size: 1.08rem;
        font-weight: 700;
        line-height: 1.25;
        letter-spacing: -.012em;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 3;
    }
    .article-card__title-link{
        color: inherit;
        text-decoration: none;
        text-transform: none;
    }
    .article-card__title-link:hover{
        color: var(--color-secondary);
        text-decoration: none;
    }
    .article-card__excerpt{
        display: -webkit-box;
        margin: 0 0 18px;
        overflow: hidden;
        color: #686170;
        font-size: .9rem;
        line-height: 1.55;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 4;
    }
    .article-card__footer{
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-top: auto;
        padding-top: 14px;
        border-top: 1px solid #eee9f2;
    }
    .article-card__footer time{
        color: #8a8290;
        font-size: .78rem;
        line-height: 1.2;
    }
    .article-card__read-more{
        display: inline-flex;
        align-items: center;
        gap: 5px;
        color: var(--color-secondary);
        font-size: .82rem;
        font-weight: 700;
        line-height: 1.2;
        text-decoration: none;
        text-transform: none;
    }
    .article-card__read-more span{
        font-size: 1rem;
        transition: transform .18s ease;
    }
    .article-card__read-more:hover{
        color: var(--color-secondary-hover);
    }
    .article-card__read-more:hover span{
        transform: translateX(3px);
    }
    .filter-card__header{
        background: #8c4dc7;
        color: #fff;
        padding: 14px 24px;
        font-weight: 700;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        border-top-left-radius: 0.5rem;
        border-top-right-radius: 0.5rem;
    }
    .filter-card .card-body{
        padding: 18px 24px 20px;
    }
    .filter-card .btn-primary{
        background: #ff8091;
        border-color: #ff8091;
        box-shadow: 0 6px 16px rgba(255,128,145,0.25);
    }
    .filter-card .btn-primary:hover{
        background: #ff6a7f;
        border-color: #ff6a7f;
    }
    @media (max-width: 575.98px){
        .filter-card__header{
            padding-right: 20px;
            padding-left: 20px;
        }
        .filter-card .card-body{
            padding-right: 20px;
            padding-left: 20px;
        }
    }
</style>
@endsection
