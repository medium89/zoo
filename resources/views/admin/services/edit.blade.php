@extends('admin.index')
@section('content')
<h3>Редактировать услугу</h3>
@if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<form action="{{ route('services.update', $service) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label for="image" class="form-label">Изображение</label><br>
        <img src="{{ asset('storage/'.$service->image) }}" alt="" width="80" class="mb-2"><br>
        <input type="file" class="form-control" id="image" name="image">
        <small class="text-muted">Оставьте пустым, если не хотите менять изображение</small>
    </div>
    <div class="mb-3">
        <label for="title" class="form-label">Заголовок</label>
        <input type="text" class="form-control" id="title" name="title" value="{{ $service->title }}" required>
    </div>
    <div class="mb-3">
        <label for="text" class="form-label">Текст</label>
        <textarea class="form-control" id="text" name="text" rows="4" required>{{ $service->text }}</textarea>
    </div>
    <button type="submit" class="btn btn-success">Сохранить</button>
    <a href="{{ route('services.index') }}" class="btn btn-secondary">Назад</a>
</form>
@endsection 