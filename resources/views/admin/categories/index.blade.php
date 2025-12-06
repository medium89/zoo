@extends('admin.index')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Категории животных</h1>
    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">Добавить</a>
 </div>
 @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
 @endif
 <div class="admin-grid" style="--grid-cols: 80px 1fr 1fr 160px;">
    <div class="admin-grid-header">
        <div>#</div>
        <div>Название</div>
        <div>Slug</div>
        <div class="text-end">Действия</div>
    </div>
    <div class="admin-grid-body">
        @forelse($categories as $cat)
            <div class="admin-grid-row">
                <div>{{ $loop->iteration }}</div>
                <div>{{ $cat->name }}</div>
                <div class="text-muted">{{ $cat->slug }}</div>
                <div class="actions">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.categories.edit', $cat) }}" class="btn btn-sm btn-primary text-white"><i class="fa fa-pen"></i></a>
                        <form action="{{ route('admin.categories.destroy', $cat) }}" method="POST" onsubmit="return confirm('Удалить категорию?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-muted">Категорий пока нет.</div>
        @endforelse
    </div>
 </div>
@endsection
