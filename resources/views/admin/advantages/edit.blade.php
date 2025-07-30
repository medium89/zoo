@extends('admin.index')
@section('content')
<h3>Редактировать преимущество</h3>
@if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<form action="{{ route('admin.advantages.update', $advantage->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label for="image" class="form-label">Изображение</label><br>
        <img src="{{ asset('storage/'.$advantage->image) }}" alt="" width="80" class="mb-2"><br>
        <input type="file" class="form-control" id="image" name="image">
        <small class="text-muted">Оставьте пустым, если не хотите менять изображение</small>
    </div>
    <div class="mb-3">
        <label for="title" class="form-label">Заголовок</label>
        <input type="text" class="form-control" id="title" name="title" value="{{ $advantage->title }}" required>
    </div>
    <div class="mb-3">
        <label for="text" class="form-label">Текст</label>
        <textarea class="form-control wysiwyg" id="text" name="text" rows="4">{{ $advantage->text }}</textarea>
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
        plugins: 'link lists code emoticons',
        toolbar: 'undo redo | bold italic underline | bullist numlist | link | emoticons | code',
        language: 'ru',
        height: 300,
        entity_encoding: 'raw',
        emoticons_append: {
            custom_emoji: [
                { title: 'Улыбка', char: '&#128512;' },  // 😀
                { title: 'Подмигивание', char: '&#128521;' },  // 😉
                { title: 'Сердце', char: '&#10084;&#65039;' },  // ❤️
                { title: 'Огонь', char: '&#128293;' },  // 🔥
                { title: 'Ракета', char: '&#128640;' }  // 🚀
            ]
        }
    });
</script>
@endsection