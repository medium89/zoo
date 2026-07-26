@extends('admin.index')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Клиенты</h1>
        <a href="{{ route('admin.clients.create') }}" class="btn btn-primary">Добавить</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($clients->count())
        <div class="admin-grid" style="--grid-cols: 80px 1.3fr 1fr 120px 120px 170px;">
            <div class="admin-grid-header">
                <div>#</div>
                <div>Клиент</div>
                <div>Телефон</div>
                <div>Питомцы</div>
                <div>Записи</div>
                <div class="text-end">Действия</div>
            </div>
            <div class="admin-grid-body">
                @foreach($clients as $client)
                    <div class="admin-grid-row">
                        <div class="text-muted">{{ $client->id }}</div>
                        <div><a href="{{ route('admin.clients.show', $client) }}">{{ $client->name }}</a></div>
                        <div>{{ $client->phone ?: '—' }}</div>
                        <div>{{ $client->animals_count }}</div>
                        <div>{{ $client->boardings_count }}</div>
                        <div class="actions">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-eye"></i></a>
                                <a href="{{ route('admin.clients.edit', $client) }}" class="btn btn-sm btn-primary text-white"><i class="fa fa-pen"></i></a>
                                <form action="{{ route('admin.clients.destroy', $client) }}" method="POST" class="d-inline js-delete-form" data-confirm="Удалить клиента? Связанные питомцы и записи останутся без хозяина.">
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
        {{ $clients->links() }}
    @else
        <div class="text-muted">Клиентов пока нет.</div>
    @endif
</div>
@endsection
