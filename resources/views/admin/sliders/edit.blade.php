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
<form action="{{ route('admin.sliders.update', $slider->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label for="image" class="form-label">Изображение</label><br>
        <img src="{{ asset('storage/'.$slider->image) }}" alt="" width="120" class="mb-2"><br>
        <input type="file" class="form-control" id="image" name="image">
        <small class="text-muted">Оставьте пустым, если не хотите менять изображение</small>
    </div>
    <div class="mb-3">
        <label for="text" class="form-label">Текст</label>
        <textarea class="form-control wysiwyg" id="text" name="text" rows="4">{{ $slider->text }}</textarea>
    </div>
    <div class="mb-3">
        <label for="text_bg" class="form-label">Картинка-подложка</label><br>
        @if($slider->text_bg)
            <img src="{{ asset('storage/'.$slider->text_bg) }}" alt="" width="120" class="mb-2"><br>
        @endif
        <input type="file" class="form-control" id="text_bg" name="text_bg">
        <small class="text-muted">Оставьте пустым, если не хотите менять картинку</small>
    </div>
    <div class="mb-3">
        <label for="position" class="form-label">Расположение текста</label>
        <select id="position" name="position" class="form-select">
            <option value="left" {{ $slider->position === 'left' ? 'selected' : '' }}>Слева</option>
            <option value="center" {{ $slider->position === 'center' ? 'selected' : '' }}>По центру</option>
            <option value="right" {{ $slider->position === 'right' ? 'selected' : '' }}>Справа</option>
        </select>
    </div>
    <button type="submit" class="btn btn-success">Сохранить</button>
    <a href="{{ route('admin.sliders.index') }}" class="btn btn-secondary">Назад</a>
</form>
<!-- Подключение TinyMCE WYSIWYG редактора -->
<script src="{{ asset('assets/addons/tinymce.min.js') }}" referrerpolicy="origin" crossorigin="anonymous"></script>
<script src="{{ asset('assets/addons/emojis.js') }}"></script>
<script>
  tinymce.init({
    selector: 'textarea.wysiwyg',
    menubar: false,
    plugins: 'link lists code emoticons',
    toolbar: 'undo redo | bold italic underline | bullist numlist | link | emoticons | code',
    language: 'ru',
    height: 300,
    entity_encoding: 'raw',
    emoticons_database: 'emoji'  // 🔧 обязателен!
  });
</script>
@endsection