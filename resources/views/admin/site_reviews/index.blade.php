@extends('admin.index')

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div><h1 class="mb-1">Отзывы с сайта</h1><p class="text-muted mb-0">Новые отзывы появляются здесь до публикации на сайте.</p></div>
        <div class="btn-group" role="group" aria-label="Статус отзывов">
            <a class="btn {{ $status === 'new' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('admin.site-reviews.index', ['status' => 'new']) }}">Новые</a>
            <a class="btn {{ $status === 'published' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('admin.site-reviews.index', ['status' => 'published']) }}">Опубликованы</a>
            <a class="btn {{ $status === 'hidden' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('admin.site-reviews.index', ['status' => 'hidden']) }}">Скрыты</a>
        </div>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @forelse($reviews as $review)
        <article class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between gap-3 flex-wrap">
                    <div><strong>{{ $review->name }}</strong><span class="text-warning ms-2" aria-label="{{ $review->rating }} из 5 звёзд">{{ str_repeat('★', $review->rating) }}</span></div>
                    <time class="text-muted small" datetime="{{ $review->created_at->toIso8601String() }}">{{ $review->created_at->format('d.m.Y H:i') }}</time>
                </div>
                <p class="mt-3 mb-3" style="white-space:pre-wrap">{{ $review->text }}</p>
                <div class="d-flex flex-wrap gap-2">
                    @if($review->status !== 'published')
                        <form action="{{ route('admin.site-reviews.status', $review) }}" method="POST">@csrf @method('PATCH')<input type="hidden" name="status" value="published"><button class="btn btn-primary btn-sm" type="submit">Опубликовать</button></form>
                    @endif
                    @if($review->status !== 'hidden')
                        <form action="{{ route('admin.site-reviews.status', $review) }}" method="POST">@csrf @method('PATCH')<input type="hidden" name="status" value="hidden"><button class="btn btn-outline-secondary btn-sm" type="submit">Скрыть</button></form>
                    @endif
                    <form action="{{ route('admin.site-reviews.destroy', $review) }}" method="POST" onsubmit="return confirm('Удалить отзыв?')">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm" type="submit">Удалить</button></form>
                </div>
            </div>
        </article>
    @empty
        <div class="card"><div class="card-body text-muted">Отзывов с таким статусом пока нет.</div></div>
    @endforelse
    {{ $reviews->links('pagination::bootstrap-4') }}
</div>
@endsection
