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
<table class="table table-bordered">
    <thead>
        <tr>
            <th></th>
            <th>Изображение</th>
            <th>Заголовок</th>
            <th>Текст</th>
            <th></th>
            <th>Статус</th>
        </tr>
    </thead>
    <tbody class="js-sortable">
    @foreach($services as $service)
        <tr data-id="{{ $service->id }}">
            <td class="js-order-label text-muted no-label" style="cursor:grab;"><i class="fa fa-grip-vertical me-1"></i>{{ $service->order }}</td>
            <td class="no-label"><img src="{{ asset('storage/'.$service->image) }}" alt="" width="80"></td>
            <td>{{ $service->title }}</td>
            <td>{{ Str::limit($service->text, 50) }}</td>
            <td class="no-label">
                <a href="{{ route('admin.services.edit', $service->id) }}" class="btn btn-sm btn-warning">Редактировать</a>
                <form action="{{ route('admin.services.destroy', $service->id) }}" method="POST" style="display:inline-block">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Удалить?')">Удалить</button>
                </form>
            </td>
            <td>
                <input type="hidden" name="statuses[{{ $service->id }}]" value="0" form="status-form">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="statuses[{{ $service->id }}]" value="1" form="status-form" {{ $service->active ? 'checked' : '' }}>
                </div>
                <input type="hidden" name="orders[{{ $service->id }}]" value="{{ $service->order }}" class="js-order-input" form="status-form">
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
<button type="submit" form="status-form" class="btn btn-primary mt-2">Сохранить статусы</button>
@endsection 
