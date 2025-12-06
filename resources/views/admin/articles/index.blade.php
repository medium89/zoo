@extends('admin.index')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Статьи</h1>
        <a href="{{ route('admin.articles.create') }}" class="btn btn-primary">Добавить</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="admin-grid" style="--grid-cols: 80px 2fr 1.2fr 1.2fr 200px;">
        <div class="admin-grid-header">
            <div>#</div>
            <div>Заголовок</div>
            <div>Создана</div>
            <div>Публикация</div>
            <div class="text-end">Действия</div>
        </div>
        <div class="admin-grid-body">
            @forelse($articles as $article)
                <div class="admin-grid-row">
                    <div>{{ $article->id }}</div>
                    <div>{{ $article->title }}</div>
                    <div>{{ $article->created_at->format('d.m.Y H:i') }}</div>
                    <div>{{ $article->published_at ? $article->published_at->format('d.m.Y H:i') : '—' }}</div>
                    <div class="actions">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.articles.edit', $article) }}" class="btn btn-sm btn-primary text-white"><i class="fa fa-pen"></i></a>
                            <form action="{{ route('admin.articles.destroy', $article) }}" method="POST" class="d-inline" onsubmit="return confirm('Удалить статью?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger">Удалить</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-muted">Статей нет.</div>
            @endforelse
        </div>
    </div>

    {{ $articles->links() }}
</div>
@endsection
