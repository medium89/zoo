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
                            <div class="card h-100 shadow-sm border-0 article-card">
                                @php
                                    $cover = $article->cover_path ? asset('storage/'.$article->cover_path) : null;
                                    if(!$cover && $article->images->first()){
                                        $cover = asset('storage/'.$article->images->first()->path);
                                    }
                                    $placeholder = 'data:image/svg+xml;utf8,'.rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="600" height="360" viewBox="0 0 600 360"><rect width="600" height="360" fill="%23415366"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="%23ffffff" font-size="24" font-family="Arial, sans-serif">Нет изображения</text></svg>');
                                @endphp
                                <img src="{{ $cover ?? $placeholder }}" class="card-img-top" alt="{{ $article->title }}" style="height:200px;object-fit:contain;background:#fff;">
                                <div class="card-body d-flex flex-column">
                                    <h4 class="card-title fw-bold">{{ $article->title }}</h4>
                                    @if($article->excerpt)
                                        <p class="card-text text-muted">{{ \Illuminate\Support\Str::limit(strip_tags($article->excerpt), 160) }}</p>
                                    @endif
                                    <div class="mt-auto d-flex justify-content-between align-items-center">
                                        <span class="text-muted small">{{ $article->created_at->format('d.m.Y') }}</span>
                                        <a href="{{ route('articles.show', $article) }}" class="btn btn-outline-primary btn-sm">Читать</a>
                                    </div>
                                </div>
                            </div>
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
    .article-card img{
        border-top-left-radius: 0.75rem;
        border-top-right-radius: 0.75rem;
    }
    .article-card{
        border-radius: 12px;
    }
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
    .filter-card .btn-primary{
        background: #ff8091;
        border-color: #ff8091;
        box-shadow: 0 6px 16px rgba(255,128,145,0.25);
    }
    .filter-card .btn-primary:hover{
        background: #ff6a7f;
        border-color: #ff6a7f;
    }
</style>
@endsection
