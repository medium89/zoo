@extends('admin.index')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Услуги</h1>
    <a href="{{ route('admin.services.create') }}" class="btn btn-primary">Добавить</a>
</div>
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
<form id="status-form" action="{{ route('admin.services.status') }}" method="POST">
    @csrf
</form>
<table class="table table-bordered admin-grid-table" style="--grid-cols: 120px 150px 1fr 2fr 200px 160px;">
    <thead>
        <tr>
            <th>Порядок</th>
            <th>Изображение</th>
            <th>Заголовок</th>
            <th>Текст</th>
            <th>Статус</th>
            <th>Действия</th>
        </tr>
    </thead>
    <tbody class="js-sortable">
    @foreach($services as $service)
        <tr data-id="{{ $service->id }}">
            <td class="js-order-label text-muted no-label align-middle" style="cursor:grab;"><i class="fa fa-grip-vertical me-1"></i>{{ $service->order }}</td>
            <td class="no-label align-middle"><img src="{{ asset('storage/'.$service->image) }}" alt="" width="90" class="rounded shadow-sm"></td>
            <td class="align-middle fw-semibold">{{ $service->title }}</td>
            <td class="align-middle text-clip">{{ Str::limit(strip_tags($service->text), 140) }}</td>
            <td class="align-middle">
                <div class="d-flex align-items-center gap-2">
                    <input type="hidden" name="statuses[{{ $service->id }}]" value="0" form="status-form">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="statuses[{{ $service->id }}]" value="1" form="status-form" {{ $service->active ? 'checked' : '' }}>
                    </div>
                    <span class="text-muted small">Статус</span>
                </div>
                <input type="hidden" name="orders[{{ $service->id }}]" value="{{ $service->order }}" class="js-order-input" form="status-form">
            </td>
            <td class="no-label align-middle actions">
                <div class="d-flex gap-2 align-items-center">
                    <a href="{{ route('admin.services.edit', $service->id) }}" class="btn btn-sm btn-primary text-white"><i class="fa fa-pen"></i></a>
                    <form action="{{ route('admin.services.destroy', $service->id) }}" method="POST" style="display:inline-block">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Удалить?')">Удалить</button>
                    </form>
                </div>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
<button type="submit" form="status-form" class="btn btn-primary mt-2">Сохранить статусы</button>
@endsection 
