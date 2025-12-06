@extends('admin.index')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Социальные контакты</h1>
    <a href="{{ route('admin.socials.create') }}" class="btn btn-primary">Добавить контакт</a>
</div>
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
<form id="status-form" action="{{ route('admin.socials.status') }}" method="POST">
    @csrf
</form>
<table class="table table-bordered">
    <thead>
        <tr>
            <th></th>
            <th>Иконка</th>
            <th>Заголовок</th>
            <th>Ссылка</th>
            <th>Текст ссылки</th>
            <th>Текст</th>
            <th>Порядок</th>
            <th></th>
            <th>Статус</th>
        </tr>
    </thead>
    <tbody class="js-sortable">
    @foreach($socials as $social)
        <tr data-id="{{ $social->id }}">
            <td class="js-order-label text-muted no-label" style="cursor:grab;"><i class="fa fa-grip-vertical me-1"></i>{{ $social->order }}</td>
            <td><i class="{{ $social->icon }}"></i> <span class="text-muted">{{ $social->icon }}</span></td>
            <td>{{ $social->title }}</td>
            <td><a href="{{ $social->link }}" target="_blank">{{ $social->link }}</a></td>
            <td>{{ $social->link_text }}</td>
            <td>{{ $social->text }}</td>
            <td>{{ $social->order }}</td>
            <td class="no-label align-middle">
                <div class="d-flex align-items-center gap-2">
                    <input type="hidden" name="statuses[{{ $social->id }}]" value="0" form="status-form">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="statuses[{{ $social->id }}]" value="1" form="status-form" {{ $social->active ? 'checked' : '' }}>
                    </div>
                    <span class="text-muted small">Статус</span>
                </div>
                <input type="hidden" name="orders[{{ $social->id }}]" value="{{ $social->order }}" class="js-order-input" form="status-form">
            </td>
            <td class="no-label align-middle">
                <div class="d-flex gap-2 align-items-center">
                    <a href="{{ route('admin.socials.edit', $social->id) }}" class="btn btn-sm btn-warning">Редактировать</a>
                    <form action="{{ route('admin.socials.destroy', $social->id) }}" method="POST" style="display:inline-block">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Удалить контакт?')">Удалить</button>
                    </form>
                </div>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
<button type="submit" form="status-form" class="btn btn-primary mt-2">Сохранить статусы</button>
@endsection 
