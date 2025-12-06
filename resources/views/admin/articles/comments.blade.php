@extends('admin.index')

@section('content')
<div class="container-fluid">
    <h1 class="mb-3">Комментарии к статьям</h1>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <div class="table-responsive">
        <table class="table align-middle admin-grid-table" style="--grid-cols: 70px 1.6fr 1.2fr 2fr 1.2fr 1fr 140px;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Статья</th>
                    <th>Email</th>
                    <th>Текст</th>
                    <th>Статус</th>
                    <th>Создан</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
            @forelse($comments as $c)
                <tr>
                    <td>{{ $c->id }}</td>
                    <td>{{ optional($c->article)->title }}</td>
                    <td>{{ $c->email }}</td>
                    <td class="text-clip">{{ $c->content }}</td>
                    <td>
                        <form action="{{ route('admin.article-comments.update', $c) }}" method="POST" class="d-flex align-items-center gap-2">
                            @csrf @method('PUT')
                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="pending" {{ $c->status==='pending'?'selected':'' }}>На модерации</option>
                                <option value="approved" {{ $c->status==='approved'?'selected':'' }}>Опубликован</option>
                                <option value="rejected" {{ $c->status==='rejected'?'selected':'' }}>Отклонён</option>
                            </select>
                        </form>
                    </td>
                    <td>{{ $c->created_at->format('d.m.Y H:i') }}</td>
                    <td class="actions">
                        <div class="d-flex justify-content-end gap-2">
                            <form action="{{ route('admin.article-comments.destroy', $c) }}" method="POST" onsubmit="return confirm('Удалить комментарий?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger">Удалить</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-muted">Комментариев нет.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $comments->links() }}
</div>
@endsection
