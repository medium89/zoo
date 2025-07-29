@extends('admin.index')
@section('content')
<h3>Добавить фото</h3>
@if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<form action="{{ route('admin.galleries.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="mb-3">
        <label for="images" class="form-label">Фотографии</label>
        <input type="file" class="form-control" id="images" name="images[]" multiple required>
        <small class="text-muted">Можно выбрать несколько файлов</small>
    </div>
    <button type="submit" class="btn btn-success">Загрузить</button>
    <a href="{{ route('admin.galleries.index') }}" class="btn btn-secondary">Назад</a>
</form>
@endsection 