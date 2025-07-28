@extends('admin.index')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Преимущества</h1>
    <a href="{{ route('admin.advantages.create') }}" class="btn btn-primary">Добавить</a>
</div>
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
<table class="table table-bordered">
    <thead>
        <tr>
            <th>#</th>
            <th>Изображение</th>
            <th>Заголовок</th>
            <th>Текст</th>
            <th>Действия</th>
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
        </tr>
    @endforeach
    </tbody>
</table>
@endsection 