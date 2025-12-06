@extends('admin.index')

@section('content')
<div class="container-fluid">
    <h1 class="mb-3">Комментарии к статьям</h1>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <div class="admin-grid" style="--grid-cols: 70px 1.6fr 1.2fr 2fr 1.2fr 1fr 140px;">
        <div class="admin-grid-header">
            <div>#</div>
            <div>Статья</div>
            <div>Email</div>
            <div>Текст</div>
            <div>Статус</div>
            <div>Создан</div>
            <div class="text-end">Действия</div>
        </div>
        <div class="admin-grid-body">
        @forelse($comments as $c)
            <div class="admin-grid-row">
                <div>{{ $c->id }}</div>
                <div>{{ optional($c->article)->title }}</div>
                <div>{{ $c->email }}</div>
                <div class="text-clip">{{ $c->content }}</div>
                <div>
                    <form action="{{ route('admin.article-comments.update', $c) }}" method="POST" class="d-flex align-items-center gap-2">
                        @csrf @method('PUT')
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="pending" {{ $c->status==='pending'?'selected':'' }}>На модерации</option>
                            <option value="approved" {{ $c->status==='approved'?'selected':'' }}>Опубликован</option>
                            <option value="rejected" {{ $c->status==='rejected'?'selected':'' }}>Отклонён</option>
                        </select>
                    </form>
                </div>
                <div>{{ $c->created_at->format('d.m.Y H:i') }}</div>
                <div class="actions">
                    <div class="d-flex justify-content-end gap-2">
                        <form action="{{ route('admin.article-comments.destroy', $c) }}" method="POST" onsubmit="return confirm('Удалить комментарий?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger">Удалить</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-muted">Комментариев нет.</div>
        @endforelse
        </div>
    </div>
    {{ $comments->links() }}
</div>
@endsection
