@extends('admin.index')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Социальные контакты</h1>
    <a href="{{ route('admin.socials.create') }}" class="btn btn-primary">Добавить контакт</a>
</div>
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
<table class="table table-bordered">
    <thead>
        <tr>
            <th>#</th>
            <th>Иконка</th>
            <th>Заголовок</th>
            <th>Ссылка</th>
            <th>Текст</th>
            <th>Порядок</th>
            <th>Действия</th>
        </tr>
    </thead>
    <tbody>
    @foreach($socials as $social)
        <tr>
            <td>{{ $social->id }}</td>
            <td><i class="{{ $social->icon }}"></i> <span class="text-muted">{{ $social->icon }}</span></td>
            <td>{{ $social->title }}</td>
            <td><a href="{{ $social->link }}" target="_blank">{{ $social->link }}</a></td>
            <td>{{ $social->text }}</td>
            <td>{{ $social->order }}</td>
            <td>
                <a href="{{ route('admin.socials.edit', $social->id) }}" class="btn btn-sm btn-warning">Редактировать</a>
                <form action="{{ route('admin.socials.destroy', $social->id) }}" method="POST" style="display:inline-block">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Удалить контакт?')">Удалить</button>
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
@endsection 