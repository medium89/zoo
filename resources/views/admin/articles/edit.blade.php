@extends('admin.index')

@section('content')
<div class="container-fluid">
    <h1 class="mb-3">Редактирование статьи</h1>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    <form action="{{ route('admin.articles.update', $article) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label class="form-label">Заголовок</label>
            <input type="text" name="title" class="form-control" required value="{{ $article->title }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Краткое описание</label>
            <textarea name="excerpt" class="form-control" rows="2">{{ $article->excerpt }}</textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Текст статьи</label>
            <textarea name="content" class="form-control" rows="10" required>{{ $article->content }}</textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Добавить изображения</label>
            <input type="file" name="images[]" class="form-control" multiple>
        </div>
        @if($article->images->count())
            <div class="mb-3">
                <div class="fw-bold mb-1">Текущие изображения</div>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($article->images as $img)
                        <img src="{{ asset('storage/'.$img->path) }}" alt="" style="height:90px;border-radius:6px;object-fit:cover;">
                    @endforeach
                </div>
            </div>
        @endif
        <button class="btn btn-success">Сохранить</button>
    </form>
</div>
@endsection
