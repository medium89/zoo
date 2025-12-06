@extends('admin.index')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Преимущества</h1>
    <a href="{{ route('admin.advantages.create') }}" class="btn btn-primary">Добавить</a>
</div>
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
<form id="status-form" action="{{ route('admin.advantages.status') }}" method="POST">
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
    @foreach($advantages as $advantage)
        <tr data-id="{{ $advantage->id }}" class="adv-card-row">
            <td class="js-order-label text-muted no-label align-middle" style="cursor:grab;"><i class="fa fa-grip-vertical me-1"></i>{{ $advantage->order }}</td>
            <td class="no-label align-middle"><img src="{{ asset('storage/'.$advantage->image) }}" alt="" width="90" class="rounded shadow-sm"></td>
            <td class="align-middle fw-semibold">{{ $advantage->title }}</td>
            <td class="adv-text">{!! $advantage->text !!}</td>
            <td class="no-label align-middle">
                <div class="d-flex gap-2 align-items-center">
                    <a href="{{ route('admin.advantages.edit', $advantage->id) }}" class="btn btn-sm btn-warning">Редактировать</a>
                    <form action="{{ route('admin.advantages.destroy', $advantage->id) }}" method="POST" style="display:inline-block">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Удалить?')">Удалить</button>
                    </form>
                </div>
            </td>
            <td class="align-middle">
                <div class="d-flex align-items-center gap-2">
                    <input type="hidden" name="statuses[{{ $advantage->id }}]" value="0" form="status-form">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="statuses[{{ $advantage->id }}]" value="1" form="status-form" {{ $advantage->active ? 'checked' : '' }}>
                    </div>
                    <span class="text-muted small">Статус</span>
                </div>
                <input type="hidden" name="orders[{{ $advantage->id }}]" value="{{ $advantage->order }}" class="js-order-input" form="status-form">
            </td>
        </tr>
@endforeach
    </tbody>
</table>
<button type="submit" form="status-form" class="btn btn-primary mt-2">Сохранить статусы</button>
<style>
.adv-card-row td { align-items: flex-start !important; }
.adv-card-row .adv-text p { margin-bottom: 8px; }
.adv-card-row .adv-text { max-width: 720px; }
.adv-card-row .js-order-label { align-items: center; }
.adv-card-row .js-order-label i { vertical-align: middle; }
</style>
@endsection 
