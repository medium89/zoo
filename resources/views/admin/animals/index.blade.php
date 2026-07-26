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

    @if($animals->count())
        <div class="admin-grid" style="--grid-cols: 80px 1fr 1fr 1fr 100px 160px;">
            <div class="admin-grid-header">
                <div>#</div>
                <div>Кличка</div>
                <div>Вид</div>
                <div>Хозяин</div>
                <div>Записи</div>
                <div class="text-end">Действия</div>
            </div>
            <div class="admin-grid-body">
                @foreach($animals as $animal)
                    <div class="admin-grid-row">
                        <div class="text-muted">{{ $animals->firstItem() + $loop->index }}</div>
                        <div><a href="{{ route('admin.animals.show', $animal) }}">{{ $animal->name }}</a></div>
                        <div>{{ $animal->species ?: '—' }}</div>
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
        {{ $animals->links() }}
    @else
        <div class="text-muted">Питомцев пока нет.</div>
    @endif
</div>
@endsection
