@extends('admin.index')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Питомцы</h1>
        <a href="{{ route('admin.animals.create') }}" class="btn btn-primary">Добавить</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <x-admin.filters :action="route('admin.animals.index')" :filters="$filters" placeholder="Кличка или хозяин">
        <label class="admin-filter-bar__field">Вид<select name="category_id" class="form-select"><option value="">Все виды</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>@endforeach</select></label>
        <label class="admin-filter-bar__field">Хозяин<select name="owner" class="form-select"><option value="">Все</option><option value="with" @selected(($filters['owner'] ?? '') === 'with')>Указан</option><option value="without" @selected(($filters['owner'] ?? '') === 'without')>Не указан</option></select></label>
    </x-admin.filters>

    @if($animals->count())
        <div class="admin-grid" style="--grid-cols: 80px 1fr 1fr 1fr 100px 160px;">
            <div class="admin-grid-header">
                <div>#</div>
                <div>Кличка</div>
                <div>Категория</div>
                <div>Хозяин</div>
                <div>Записи</div>
                <div class="text-end">Действия</div>
            </div>
            <div class="admin-grid-body">
                @foreach($animals as $animal)
                    <div class="admin-grid-row">
                        <div class="text-muted">{{ $animals->firstItem() + $loop->index }}</div>
                        <div>
                            <a href="{{ route('admin.animals.show', $animal) }}">{{ $animal->name }}</a>
                            @include('admin.partials.tags-list', ['tags' => $animal->tags])
                        </div>
                        <div>{{ $animal->category?->name ?: '—' }}</div>
                        <div>{{ $animal->client?->name ?: '—' }}</div>
                        <div>{{ $animal->boardings_count }}</div>
                        <div class="actions">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('admin.animals.show', $animal) }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-eye"></i></a>
                                <a href="{{ route('admin.animals.edit', $animal) }}" class="btn btn-sm btn-primary text-white"><i class="fa fa-pen"></i></a>
                                <form action="{{ route('admin.animals.destroy', $animal) }}" method="POST" class="d-inline js-delete-form" data-confirm="Удалить питомца?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="admin-pagination mt-4">
            <span class="text-muted small">
                Показано: {{ $animals->firstItem() }}–{{ $animals->lastItem() }} из {{ $animals->total() }} питомцев
            </span>
            {{ $animals->onEachSide(1)->links('pagination::bootstrap-4') }}
        </div>
    @else
        <div class="text-muted">Питомцев пока нет.</div>
    @endif
</div>
@endsection
