@extends('layouts.app')

@section('content')
@include('sections.header')

<section class="article-hero">
    <div class="container">
        <p class="text-uppercase text-muted mb-2 small">Блог</p>
        <h1 class="fw-bold">Статьи</h1>
        <p class="text-muted mb-0">Свежие материалы о заботе за питомцами и сервисах.</p>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-3">
                <div class="card shadow-sm border-0 sticky-top" style="top: 90px;">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-3">Фильтр</h6>
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
                                @if($article->images->first())
                                    <img src="{{ asset('storage/'.$article->images->first()->path) }}" class="card-img-top" alt="{{ $article->title }}" style="height:180px;object-fit:cover;">
                                @endif
                                <div class="card-body d-flex flex-column">
                                    <span class="badge bg-secondary mb-2">{{ $article->published_at? $article->published_at->format('d.m.Y') : $article->created_at->format('d.m.Y') }}</span>
                                    <h5 class="card-title">{{ $article->title }}</h5>
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

@include('sections.footer')

<style>
    .article-hero{
        padding: 96px 0 64px;
        background: radial-gradient(circle at 20% 20%, #f0f6ff 0, transparent 35%), radial-gradient(circle at 80% 10%, #fce7f3 0, transparent 32%), linear-gradient(135deg, #f8fafc 0%, #ffffff 60%);
        border-bottom: 1px solid #e9ecef;
    }
    .article-card img{
        border-top-left-radius: 0.75rem;
        border-top-right-radius: 0.75rem;
    }
    .article-card{
        border-radius: 12px;
        transition: transform .12s ease, box-shadow .12s ease;
    }
    .article-card:hover{
        transform: translateY(-4px);
        box-shadow: 0 18px 40px rgba(15,23,42,0.12);
    }
</style>
@endsection
