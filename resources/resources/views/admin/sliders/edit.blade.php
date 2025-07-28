@extends('admin.index')
@section('content')
<h3>Редактировать слайд</h3>
@if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<form action="{{ route('sliders.update', $slider) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label for="image" class="form-label">Изображение</label><br>
        <img src="{{ asset('storage/'.$slider->image) }}" alt="" width="120" class="mb-2"><br>
        <input type="file" class="form-control" id="image" name="image">
        <small class="text-muted">Оставьте пустым, если не хотите менять изображение</small>
    </div>
    <button type="submit" class="btn btn-success">Сохранить</button>
    <a href="{{ route('sliders.index') }}" class="btn btn-secondary">Назад</a>
</form>
@endsection 