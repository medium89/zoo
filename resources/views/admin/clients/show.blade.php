@extends('admin.index')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>{{ $client->name }}</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.clients.edit', $client) }}" class="btn btn-primary">Редактировать</a>
            <a href="{{ route('admin.clients.index') }}" class="btn btn-secondary">Назад</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">Информация</div>
                <div class="card-body">
                    <p><strong>Телефон:</strong> {{ $client->phone ?: '—' }}</p>
                    <p class="mb-0"><strong>Заметка:</strong><br>{{ $client->note ?: '—' }}</p>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header">Питомцы</div>
                <div class="card-body">
                    @forelse($client->animals as $animal)
                        <div class="border rounded p-3 mb-2">
                            <div class="d-flex justify-content-between gap-2">
                                <div>
                                    <a href="{{ route('admin.animals.show', $animal) }}" class="fw-bold">{{ $animal->name }}</a>
                                    <div class="text-muted small">{{ $animal->species ?: 'вид не указан' }} · записей: {{ $animal->boardings->count() }}</div>
                                </div>
                                @if($animal->photos->first())
                                    <img src="{{ Storage::url($animal->photos->first()->path) }}" alt="{{ $animal->name }}" style="width:54px;height:54px;object-fit:cover;border-radius:8px;">
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-muted">У клиента пока нет питомцев.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header">История записей</div>
        <div class="card-body">
            @if($client->boardings->count())
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Питомец</th>
                                <th>Услуга</th>
                                <th>Период</th>
                                <th>Источник</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($client->boardings->sortByDesc('start_date') as $boarding)
                                <tr>
                                    <td>{{ $boarding->id }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            @if($boarding->animal?->photos->first())
                                                <img src="{{ Storage::url($boarding->animal->photos->first()->path) }}" alt="{{ $boarding->animal->name }}" style="width:38px;height:38px;object-fit:cover;border-radius:8px;">
                                            @endif
                                            @if($boarding->animal)
                                                <a href="{{ route('admin.animals.show', $boarding->animal) }}">{{ $boarding->animal->name }}</a>
                                            @else
                                                {{ $boarding->name }}
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ $boarding->service_type }}</td>
                                    <td>{{ $boarding->start_date->toDateString() }} — {{ $boarding->end_date->toDateString() }}</td>
                                    <td>{{ $boarding->source ?? 'admin' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-muted">Записей пока нет.</div>
            @endif
        </div>
    </div>
</div>
@endsection
