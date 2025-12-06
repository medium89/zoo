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
            <label class="form-label">ЧПУ (slug)</label>
            <input type="text" name="slug" class="form-control" value="{{ $article->slug }}" placeholder="Автоиз заголовка">
        </div>
        <div class="mb-3">
            <label class="form-label">Краткое описание</label>
            <textarea name="excerpt" class="form-control wysiwyg-excerpt" rows="3" data-editor-height="220">{{ $article->excerpt }}</textarea>
        </div>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Дата публикации</label>
                <input type="datetime-local" name="published_at" class="form-control"
                       value="{{ old('published_at', optional($article->published_at)->format('Y-m-d\\TH:i')) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Создана</label>
                <input type="text" class="form-control" value="{{ $article->created_at->format('d.m.Y H:i') }}" readonly>
            </div>
            <div class="col-md-4">
                <label class="form-label">Обновлена</label>
                <input type="text" class="form-control" value="{{ $article->updated_at->format('d.m.Y H:i') }}" readonly>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Текст статьи (WYSIWYG)</label>
            <textarea name="content" class="form-control js-wysiwyg" rows="12" required data-editor-custom="1">{{ $article->content }}</textarea>
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
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">SEO Title</label>
                <input type="text" name="seo_title" class="form-control" value="{{ $article->seo_title }}" placeholder="Если пусто — будет использован заголовок">
            </div>
            <div class="col-md-6">
                <label class="form-label">SEO Description</label>
                <input type="text" name="seo_description" class="form-control" value="{{ $article->seo_description }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Robots</label>
                <input type="text" name="seo_robots" class="form-control" value="{{ $article->seo_robots ?? 'index, follow' }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Charset</label>
                <input type="text" name="seo_charset" class="form-control" value="{{ $article->seo_charset ?? 'UTF-8' }}">
            </div>
        </div>
        <div class="form-check form-switch mb-4">
            <input class="form-check-input" type="checkbox" role="switch" id="active" name="active" value="1" {{ $article->active ? 'checked' : '' }}>
            <label class="form-check-label" for="active">Активно</label>
        </div>
        <button class="btn btn-success">Сохранить</button>
    </form>
</div>
@include('admin.partials.wysiwyg-scripts')
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    const target = document.querySelector('.js-wysiwyg');
    if(!target || !window.ClassicEditor){ return; }

    ClassicEditor.create(target, {
        toolbar: [
            'undo','redo','|',
            'heading','|',
            'bold','italic','link','|',
            'bulletedList','numberedList','|',
            'insertTable','blockQuote','|',
            'imageUpload','mediaEmbed'
        ],
        heading: {
            options: [
                { model: 'paragraph', title: 'Обычный', class: 'ck-heading_paragraph' },
                { model: 'heading2', view: 'h2', title: 'Заголовок 2', class: 'ck-heading_heading2' },
                { model: 'heading3', view: 'h3', title: 'Заголовок 3', class: 'ck-heading_heading3' }
            ]
        },
        ckfinder: {
            uploadUrl: '{{ route('admin.articles.upload') }}',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        },
        link: { decorators: { addTargetToExternalLinks: true } },
        mediaEmbed: { previewsInData: true }
    }).then(editor => {
        editor.ui.view.editable.element.style.minHeight = '420px';
    }).catch(error => {
        console.error('CKEditor init error', error);
    });
});
</script>
@endpush
@endsection
