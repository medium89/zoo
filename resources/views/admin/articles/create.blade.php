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
            <label class="form-label">ЧПУ (slug)</label>
            <input type="text" name="slug" class="form-control" placeholder="Автоиз генерации заголовка, можно задать вручную">
        </div>
        <div class="mb-3">
            <label class="form-label">Краткое описание</label>
            <textarea name="excerpt" class="form-control wysiwyg-excerpt" rows="3" data-editor-height="220"></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Обложка статьи</label>
            <input type="file" name="cover" class="form-control">
            <div class="row g-2 mt-2">
                <div class="col-md-6">
                    <label class="form-label">Размер (в %)</label>
                    <input type="range" class="form-range js-scale" name="image_scale" min="10" max="100" step="5" value="100">
                    <small class="text-muted">Текущий: <span class="js-scale-val">100</span>%</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Качество (в %)</label>
                    <input type="range" class="form-range js-quality" name="image_quality" min="40" max="100" step="5" value="85">
                    <small class="text-muted">Текущее: <span class="js-quality-val">85</span>%</small>
                </div>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Дата публикации</label>
            <input type="datetime-local" name="published_at" class="form-control"
                   value="{{ old('published_at', now()->format('Y-m-d\\TH:i')) }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Категория</label>
            <select name="category_id" class="form-select">
                <option value="">Без категории</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Текст статьи (WYSIWYG)</label>
            <textarea name="content" class="form-control js-wysiwyg" rows="12" data-editor-custom="1"></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Изображения (можно несколько)</label>
            <input type="file" name="images[]" class="form-control" multiple>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">SEO Title</label>
                <input type="text" name="seo_title" class="form-control" placeholder="Если пусто — будет использован заголовок">
            </div>
            <div class="col-md-6">
                <label class="form-label">SEO Description</label>
                <input type="text" name="seo_description" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label">Robots</label>
                <input type="text" name="seo_robots" class="form-control" value="index, follow">
            </div>
            <div class="col-md-6">
                <label class="form-label">Charset</label>
                <input type="text" name="seo_charset" class="form-control" value="UTF-8">
            </div>
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
    document.querySelectorAll('.js-scale').forEach(input=>{
        input.addEventListener('input', ()=>{
            input.closest('.col-md-6').querySelector('.js-scale-val').textContent = input.value;
        });
    });
    document.querySelectorAll('.js-quality').forEach(input=>{
        input.addEventListener('input', ()=>{
            input.closest('.col-md-6').querySelector('.js-quality-val').textContent = input.value;
        });
    });
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
        simpleUpload: {
            uploadUrl: '{{ route('admin.articles.upload') }}',
            withCredentials: true,
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
