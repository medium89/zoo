@extends('admin.index')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Животные передержки</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.boarding.index') }}" class="btn btn-outline-primary">Календарь</a>
            <a href="{{ route('admin.boarding.archive') }}" class="btn btn-outline-secondary">Архив</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <p class="text-muted mb-3">
                Список пополняется автоматически при добавлении заявок на передержку и используется для подсказок в поле «Кличка».
            </p>
            @if($animals->count())
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Фото</th>
                                <th>Кличка</th>
                                <th>Вид</th>
                                <th>Хозяин</th>
                                <th>Описание</th>
                                <th>Количество заявок</th>
                                <th>Последняя заявка</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($animals as $animal)
                                @php($last = $animal->boardings->first())
                                <tr>
                                    <td>{{ $animal->id }}</td>
                                    <td>
                                        @if($animal->photos->first())
                                            <img src="{{ Storage::url($animal->photos->first()->path) }}" alt="{{ $animal->name }}" style="width:46px;height:46px;object-fit:cover;border-radius:8px;">
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td><a href="{{ route('admin.animals.show', $animal) }}">{{ $animal->name }}</a></td>
                                    <td>{{ $animal->species ?: '—' }}</td>
                                    <td>
                                        @if($animal->client)
                                            <a href="{{ route('admin.clients.show', $animal->client) }}">{{ $animal->client->name }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $animal->description }}</td>
                                    <td>{{ $animal->boardings_count }}</td>
                                    <td>
                                        @if($last)
                                            {{ $last->start_date->toDateString() }} — {{ $last->end_date->toDateString() }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-muted">Животных пока нет. Добавьте заявку на передержку, чтобы они появились в списке.</div>
            @endif
        </div>
    </div>
</div>
@endsection
