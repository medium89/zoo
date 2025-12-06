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
                    <table class="table align-middle admin-grid-table" style="--grid-cols: 80px 1fr 2fr 200px;">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Кличка</th>
                            <th>Описание</th>
                            <th>Действия</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($animals as $animal)
                            <tr>
                                <td>{{ $animal->id }}</td>
                                <td>{{ $animal->name }}</td>
                                <td class="text-clip">{{ $animal->description }}</td>
                                <td class="actions">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="{{ route('admin.animals.edit', $animal) }}" class="btn btn-sm btn-primary text-white"><i class="fa fa-pen"></i></a>
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
