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

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Заголовок</th>
                    <th>Создана</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($articles as $article)
                <tr>
                    <td>{{ $article->id }}</td>
                    <td>{{ $article->title }}</td>
                    <td>{{ $article->created_at->format('d.m.Y H:i') }}</td>
                    <td class="text-end">
                        <a href="{{ route('admin.articles.edit', $article) }}" class="btn btn-sm btn-warning">Редактировать</a>
                        <form action="{{ route('admin.articles.destroy', $article) }}" method="POST" class="d-inline" onsubmit="return confirm('Удалить статью?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger">Удалить</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-muted">Статей нет.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $articles->links() }}
</div>
@endsection
