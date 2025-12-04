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
            <label class="form-label">Текст статьи (WYSIWYG)</label>
            <textarea name="content" class="form-control js-wysiwyg" rows="12" required></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Изображения (можно несколько)</label>
            <input type="file" name="images[]" class="form-control" multiple>
        </div>
        <button class="btn btn-success">Сохранить</button>
    </form>
</div>
@push('scripts')
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    tinymce.init({
        selector: '.js-wysiwyg',
        height: 500,
        menubar: true,
        plugins: 'link image lists table code codesample media',
        toolbar: 'undo redo | styles | bold italic underline | alignleft aligncenter alignright | bullist numlist | link image media | code',
        images_upload_url: '{{ route('admin.articles.upload') }}',
        images_upload_credentials: true,
        relative_urls: false,
        convert_urls: false,
        setup: function (editor) {
            editor.on('init', function(){
                editor.options.set('images_upload_handler', function (blobInfo, success, failure){
                    const xhr = new XMLHttpRequest();
                    xhr.withCredentials = true;
                    xhr.open('POST', '{{ route('admin.articles.upload') }}');
                    xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');
                    xhr.onload = function() {
                        if (xhr.status !== 200) { failure('HTTP Error: ' + xhr.status); return; }
                        const json = JSON.parse(xhr.responseText);
                        if (!json || typeof json.location != 'string') { failure('Invalid JSON: ' + xhr.responseText); return; }
                        success(json.location);
                    };
                    const formData = new FormData();
                    formData.append('file', blobInfo.blob(), blobInfo.filename());
                    xhr.send(formData);
                });
            });
        }
    });
});
</script>
@endpush
@endsection
