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
            <th>#</th>
            <th>Изображение</th>
            <th>Заголовок</th>
            <th>Текст</th>
            <th>Действия</th>
            <th>Статус</th>
        </tr>
    </thead>
    <tbody>
    @foreach($advantages as $advantage)
        <tr>
            <td>{{ $advantage->id }}</td>
            <td><img src="{{ asset('storage/'.$advantage->image) }}" alt="" width="80"></td>
            <td>{{ $advantage->title }}</td>
            <td>{{ $advantage->text }}</td>
            <td>
                <a href="{{ route('admin.advantages.edit', $advantage->id) }}" class="btn btn-sm btn-warning">Редактировать</a>
                <form action="{{ route('admin.advantages.destroy', $advantage->id) }}" method="POST" style="display:inline-block">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Удалить?')">Удалить</button>
                </form>
            </td>
            <td>
                <select name="statuses[{{ $advantage->id }}]" class="form-select form-select-sm" form="status-form">
                    <option value="1" {{ $advantage->active ? 'selected' : '' }}>Вкл</option>
                    <option value="0" {{ !$advantage->active ? 'selected' : '' }}>Выкл</option>
                </select>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
<button type="submit" form="status-form" class="btn btn-primary mt-2">Сохранить статусы</button>
@endsection 