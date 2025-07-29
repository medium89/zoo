@extends('admin.index')
@section('content')
<h3>Добавить слайд</h3>
@if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<form action="{{ route('admin.sliders.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="mb-3">
        <label for="image" class="form-label">Изображение</label>
        <input type="file" class="form-control" id="image" name="image" required>
    </div>
    <button type="submit" class="btn btn-success">Сохранить</button>
    <a href="{{ route('admin.sliders.index') }}" class="btn btn-secondary">Назад</a>
</form>
@endsection 