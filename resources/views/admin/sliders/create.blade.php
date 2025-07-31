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
    <div class="mb-3">
        <label for="text" class="form-label">Текст</label>
        <textarea class="form-control wysiwyg" id="text" name="text" rows="4"></textarea>
    </div>
    <div class="mb-3">
        <label for="text_bg" class="form-label">Картинка-подложка</label>
        <input type="file" class="form-control" id="text_bg" name="text_bg">
    </div>
    <div class="mb-3">
        <label for="position" class="form-label">Расположение текста</label>
        <select id="position" name="position" class="form-select">
            <option value="left">Слева</option>
            <option value="center" selected>По центру</option>
            <option value="right">Справа</option>
        </select>
    </div>
    <button type="submit" class="btn btn-success">Сохранить</button>
    <a href="{{ route('admin.sliders.index') }}" class="btn btn-secondary">Назад</a>
</form>