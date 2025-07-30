@extends('admin.index')
@section('content')
<h3>Добавить преимущество</h3>
@if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<form action="{{ route('admin.advantages.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="mb-3">
        <label for="image" class="form-label">Изображение</label>
        <input type="file" class="form-control" id="image" name="image" required>
    </div>
    <div class="mb-3">
        <label for="title" class="form-label">Заголовок</label>
        <input type="text" class="form-control" id="title" name="title" required>
    </div>
    <div class="mb-3">
        <label for="text" class="form-label">Текст</label>
        <textarea class="form-control wysiwyg" id="text" name="text" rows="4" required></textarea>
    </div>
    <button type="submit" class="btn btn-success">Сохранить</button>
    <a href="{{ route('admin.advantages.index') }}" class="btn btn-secondary">Назад</a>
</form>
<!-- Подключение TinyMCE WYSIWYG редактора -->
<script src="https://cdn.tiny.cloud/1/ilf8e4vsikngopxe08xuqeely1o5rigddts9einhhrfen31e/tinymce/8/tinymce.min.js" referrerpolicy="origin" crossorigin="anonymous"></script>
<script>
    tinymce.init({
        selector: 'textarea.wysiwyg',
        menubar: false,
        plugins: 'link lists code',
        toolbar: 'undo redo | bold italic underline | bullist numlist | link | code',
        language: 'ru',
        height: 300
    });
</script>
@endsection