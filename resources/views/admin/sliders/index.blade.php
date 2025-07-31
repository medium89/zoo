@extends('admin.index')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Слайды</h1>
    <a href="{{ route('admin.sliders.create') }}" class="btn btn-primary">Добавить слайд</a>
</div>
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
<form id="status-form" action="{{ route('admin.sliders.status') }}" method="POST">
    @csrf
</form>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>#</th>
            <th>Изображение</th>
            <th>Действия</th>
            <th>Статус</th>
        </tr>
    </thead>
    <tbody>
    @foreach($sliders as $slider)
        <tr>
            <td>{{ $slider->id }}</td>
            <td><img src="{{ asset('storage/'.$slider->image) }}" alt="" width="120"></td>
            <td>
                <a href="{{ route('admin.sliders.edit', $slider) }}" class="btn btn-sm btn-warning">Редактировать</a>
                <form action="{{ route('admin.sliders.destroy', $slider) }}" method="POST" style="display:inline-block">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Удалить слайд?')">Удалить</button>
                </form>
            </td>
            <td>
                <select name="statuses[{{ $slider->id }}]" class="form-select form-select-sm" form="status-form">
                    <option value="1" {{ $slider->active ? 'selected' : '' }}>Вкл</option>
                    <option value="0" {{ !$slider->active ? 'selected' : '' }}>Выкл</option>
                </select>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
<button type="submit" form="status-form" class="btn btn-primary mt-2">Сохранить статусы</button>
@endsection 