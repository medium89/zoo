@extends('layouts.app')

@section('content')
<div class="container py-5">
    <h1 class="mb-4">Статьи</h1>
    <div class="row g-4">
        @forelse($articles as $article)
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">{{ $article->title }}</h5>
                        @if($article->excerpt)
                            <p class="card-text text-muted">{{ \Illuminate\Support\Str::limit($article->excerpt, 160) }}</p>
                        @endif
                        <div class="mt-auto d-flex justify-content-between align-items-center">
                            <span class="text-muted" style="font-size:12px;">{{ $article->created_at->format('d.m.Y') }}</span>
                            <a href="{{ route('articles.show', $article) }}" class="btn btn-primary btn-sm">Читать</a>
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
@endsection
