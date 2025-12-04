@extends('admin.index')

@section('content')
<div class="container-fluid">
    <h1 class="mb-3">Новая статья</h1>
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    <form action="{{ route('admin.articles.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label class="form-label">Заголовок</label>
            <input type="text" name="title" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Краткое описание</label>
            <textarea name="excerpt" class="form-control" rows="2"></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Текст статьи (HTML допускается)</label>
            <textarea name="content" class="form-control" rows="10" required></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Изображения (можно несколько)</label>
            <input type="file" name="images[]" class="form-control" multiple>
        </div>
        <button class="btn btn-success">Сохранить</button>
    </form>
</div>
@endsection
