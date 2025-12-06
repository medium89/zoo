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
            <textarea name="excerpt" class="form-control wysiwyg-excerpt" rows="3" data-editor-height="220"></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Дата публикации</label>
            <input type="datetime-local" name="published_at" class="form-control"
                   value="{{ old('published_at', now()->format('Y-m-d\\TH:i')) }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Текст статьи (WYSIWYG)</label>
            <textarea name="content" class="form-control js-wysiwyg" rows="12" required data-editor-custom="1"></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Изображения (можно несколько)</label>
            <input type="file" name="images[]" class="form-control" multiple>
        </div>
        <div class="form-check form-switch mb-4">
            <input class="form-check-input" type="checkbox" role="switch" id="active" name="active" value="1" checked>
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
