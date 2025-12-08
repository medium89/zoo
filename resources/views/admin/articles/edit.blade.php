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
        <div class="row g-3 mb-3">
            <div class="col-lg-6">
                <label class="form-label">Заголовок</label>
                <input type="text" name="title" class="form-control" required value="{{ $article->title }}">
            </div>
            <div class="col-lg-6">
                <label class="form-label">ЧПУ (slug)</label>
                <input type="text" name="slug" class="form-control" value="{{ $article->slug }}" placeholder="Автоиз заголовка">
            </div>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-6 col-lg-3">
                <label class="form-label">SEO Title</label>
                <input type="text" name="seo_title" class="form-control" value="{{ $article->seo_title }}" placeholder="Если пусто — будет использован заголовок">
            </div>
            <div class="col-md-6 col-lg-3">
                <label class="form-label">SEO Description</label>
                <input type="text" name="seo_description" class="form-control" value="{{ $article->seo_description }}">
            </div>
            <div class="col-md-6 col-lg-3">
                <label class="form-label">Robots</label>
                <input type="text" name="seo_robots" class="form-control" value="{{ $article->seo_robots ?? 'index, follow' }}">
            </div>
            <div class="col-md-6 col-lg-3">
                <label class="form-label">Charset</label>
                <input type="text" name="seo_charset" class="form-control" value="{{ $article->seo_charset ?? 'UTF-8' }}">
            </div>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-12">
                <label class="form-label">Краткое описание</label>
                <textarea name="excerpt" class="form-control wysiwyg-excerpt" rows="3" data-editor-height="220">{{ $article->excerpt }}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Текст статьи</label>
                <textarea name="content" class="form-control js-wysiwyg" rows="12" data-editor-custom="1">{{ $article->content }}</textarea>
            </div>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-lg-6">
                <label class="form-label">Обложка статьи</label><br>
                @if($article->cover_path)
                    <img src="{{ asset('storage/'.$article->cover_path) }}" alt="" width="140" class="mb-2 rounded shadow-sm"><br>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="removeCover" name="remove_cover" value="1">
                        <label class="form-check-label" for="removeCover">Удалить текущую обложку</label>
                    </div>
                @endif
                <div class="d-flex align-items-center gap-2">
                    <input type="file" name="cover" class="form-control" id="coverInput">
                    <button type="button" class="btn btn-outline-secondary btn-sm js-clear-file" data-target="#coverInput">Очистить</button>
                </div>
            </div>
            <div class="col-lg-6">
                <label class="form-label">Фон для article-hero</label><br>
                @if($article->hero_image_path ?? false)
                    <img src="{{ asset('storage/'.$article->hero_image_path) }}" alt="" width="140" class="mb-2 rounded shadow-sm"><br>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="removeHero" name="remove_hero" value="1">
                        <label class="form-check-label" for="removeHero">Удалить текущий фон</label>
                    </div>
                @endif
                <div class="d-flex align-items-center gap-2">
                    <input type="file" name="hero_image" class="form-control" id="heroInput" accept="image/*">
                    <button type="button" class="btn btn-outline-secondary btn-sm js-clear-file" data-target="#heroInput">Очистить</button>
                </div>
            </div>
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
        <div class="mb-3 mt-3">
            <label class="form-label">Категория</label>
            <select name="category_id" class="form-select">
                <option value="">Без категории</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ $article->category_id == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Добавить изображения</label>
            <div class="d-flex align-items-center gap-2">
                <input type="file" name="images[]" class="form-control" id="imagesInput" multiple>
                <button type="button" class="btn btn-outline-secondary btn-sm js-clear-file" data-target="#imagesInput">Очистить</button>
            </div>
        </div>
        @if($article->images->count())
            <div class="mb-3">
                <div class="fw-bold mb-1">Текущие изображения</div>
                <div class="d-flex flex-wrap gap-3">
                    @foreach($article->images as $img)
                        <div class="d-flex flex-column align-items-center gap-1" style="width:120px;">
                            <img src="{{ asset('storage/'.$img->path) }}" alt="" style="width:120px;height:90px;border-radius:6px;object-fit:cover;">
                            <form action="{{ route('admin.articles.images.destroy', [$article, $img]) }}" method="POST" class="w-100">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm w-100">Удалить</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
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
    if(!target || !window.tinymce){ return; }

    const uploadUrl = '{{ route('admin.articles.upload') }}';
    const csrf = '{{ csrf_token() }}';

    tinymce.init({
        target,
        menubar: false,
        branding: false,
        height: 460,
        plugins: 'advlist autolink lists link image media table code fullscreen',
        toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media table | removeformat code fullscreen',
        automatic_uploads: true,
        images_upload_handler: (blobInfo, progress) => new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append('file', blobInfo.blob(), blobInfo.filename());
            fetch(uploadUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf },
                body: formData,
            }).then(async response => {
                if(!response.ok){ throw new Error('Upload failed'); }
                const data = await response.json();
                const url = data.url || data.location;
                if(url){ resolve(url); } else { reject('Неверный ответ загрузки'); }
            }).catch(err => reject(err && err.message ? err.message : err));
        }),
        file_picker_types: 'image',
        image_title: true,
        image_dimensions: true,
        image_caption: true,
        image_advtab: true,
        convert_urls: false,
        relative_urls: false,
        promotion: false,
        content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; font-size: 15px; }'
    });
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
    document.querySelectorAll('.js-clear-file').forEach(btn=>{
        btn.addEventListener('click', ()=>{
            const target = document.querySelector(btn.dataset.target);
            if(!target) return;
            target.value = '';
        });
    });
});
</script>
@endpush
@endsection
