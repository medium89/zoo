@extends('admin.index')
@section('content')
<div class="admin-live-preview-layout">
<div class="admin-live-preview-layout__fields">
<h3>Добавить услугу</h3>
@if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<form action="{{ route('admin.services.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="mb-3">
        <label for="image" class="form-label">Изображение основной версии сайта (V1)</label>
        <input type="file" class="form-control" id="image" name="image" required accept="image/*">
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
        <label for="sticker_image" class="form-label">Иллюстрация стикера V2</label>
        <input type="file" class="form-control" id="sticker_image" name="sticker_image" accept="image/*">
        <small class="text-muted">Изображение для карточки услуги на /v2. Можно добавить позже.</small>
    </div>
    <div class="mb-3">
        <label for="title" class="form-label">Заголовок</label>
        <input type="text" class="form-control" id="title" name="title" required>
    </div>
    <div class="mb-3">
        <label for="text" class="form-label">Текст</label>
        <textarea class="form-control" data-rich-editor id="text" name="text" rows="4"></textarea>
    </div>
    <div class="mb-3">
        <label for="service_tag_input" class="form-label">Теги для стикера V2</label>
        <div class="service-tag-editor" data-service-tag-editor>
            <div class="service-tag-editor__entry">
                <input type="text" class="form-control" id="service_tag_input" data-service-tag-input placeholder="Напишите текст тега">
                <button type="button" class="btn btn-outline-primary service-tag-editor__add" data-service-tag-add>Добавить тег</button>
            </div>
            <div class="service-tag-editor__status" data-service-tag-status role="status"></div>
            <div class="service-tag-editor__list" data-service-tag-list aria-live="polite"></div>
            <textarea name="tags_text" data-service-tag-value hidden>{{ old('tags_text', '') }}</textarea>
        </div>
        <small class="text-muted">Добавьте теги кнопкой. Перетаскивайте плашки за значок с точками, чтобы задать порядок на сайте. На клавиатуре используйте стрелки; затем сохраните услугу.</small>
    </div>
    <div class="mb-3">
        <label for="sticker_doodle" class="form-label">Значок в правом нижнем углу стикера V2</label>
        <input type="file" class="form-control" id="sticker_doodle" name="sticker_doodle" accept="image/*">
        <small class="text-muted">PNG, JPG, GIF, WebP, AVIF, BMP или SVG, до 5 МБ. Файл сохраняется в исходном формате; GIF остаётся анимированным. Рекомендуется прозрачный фон.</small>
    </div>
    @include('admin.services.partials.sticker-colors')
    <div class="mb-3">
        <label for="order" class="form-label">Порядок</label>
        <input type="number" class="form-control" id="order" name="order" value="0">
    </div>
    <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" id="active" name="active" value="1" checked>
        <label class="form-check-label" for="active">Активна</label>
    </div>
    <button type="submit" class="btn btn-success">Сохранить</button>
</form>
</div>
@include('admin.partials.live-preview', ['kind' => 'service'])
</div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('input', function(e){
            if(e.target.matches('.js-scale')){
                e.target.closest('.col-md-6').querySelector('.js-scale-val').textContent = e.target.value;
            }
            if(e.target.matches('.js-quality')){
                e.target.closest('.col-md-6').querySelector('.js-quality-val').textContent = e.target.value;
            }
        });
    </script>
@endsection
