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
            <th></th>
            <th>Изображение</th>
            <th></th>
            <th></th>
            <th>Статус</th>
        </tr>
    </thead>
    <tbody id="slidersSort" class="js-sortable">
    @foreach($sliders as $slider)
        <tr data-id="{{ $slider->id }}">
            <td class="js-order-label text-muted no-label" style="cursor:grab;"><i class="fa fa-grip-vertical me-1"></i>{{ $slider->order }}</td>
            <td class="no-label"><img src="{{ asset('storage/'.$slider->image) }}" alt="" width="120"></td>
            <td class="no-label">{{ $slider->order }}</td>
            <td class="no-label">
                <a href="{{ route('admin.sliders.edit', $slider) }}" class="btn btn-sm btn-warning">Редактировать</a>
                <form action="{{ route('admin.sliders.destroy', $slider) }}" method="POST" style="display:inline-block">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Удалить слайд?')">Удалить</button>
                </form>
            </td>
            <td>
                <input type="hidden" name="statuses[{{ $slider->id }}]" value="0" form="status-form">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="statuses[{{ $slider->id }}]" value="1" form="status-form" {{ $slider->active ? 'checked' : '' }}>
                </div>
                <input type="hidden" name="orders[{{ $slider->id }}]" value="{{ $slider->order }}" class="js-order-input" form="status-form">
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
<button type="submit" form="status-form" class="btn btn-primary mt-2">Сохранить статусы</button>
@endsection 
