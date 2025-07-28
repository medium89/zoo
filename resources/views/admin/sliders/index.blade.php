@extends('admin.index')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Слайдеры</h1>
    <a href="{{ route('admin.sliders.create') }}" class="btn btn-primary">Добавить слайд</a>
</div>
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
<table class="table table-bordered">
    <thead>
        <tr>
            <th>#</th>
            <th>Изображение</th>
            <th>Действия</th>
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
        </tr>
    @endforeach
    </tbody>
</table>
@endsection 