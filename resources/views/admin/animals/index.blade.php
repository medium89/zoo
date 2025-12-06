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

    <div class="card">
        <div class="card-body">
            @if($animals->count())
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Кличка</th>
                            <th>Описание</th>
                            <th class="text-end">Действия</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($animals as $animal)
                            <tr>
                                <td>{{ $animal->id }}</td>
                                <td>{{ $animal->name }}</td>
                                <td>{{ $animal->description }}</td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="{{ route('admin.animals.edit', $animal) }}" class="btn btn-sm btn-outline-primary">Редактировать</a>
                                        <form action="{{ route('admin.animals.destroy', $animal) }}" method="POST" class="d-inline js-delete-form" data-confirm="Удалить питомца?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Удалить</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $animals->links() }}
            @else
                <div class="text-muted">Питомцев пока нет.</div>
            @endif
        </div>
    </div>
</div>
@endsection
