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
        <div class="row g-2 mt-2">
            <div class="col-md-6">
                <label class="form-label">Размер (в %)</label>
                <input type="range" class="form-range js-scale" name="text_bg_scale" min="10" max="100" step="5" value="100">
                <small class="text-muted">Текущий: <span class="js-scale-val">100</span>%</small>
            </div>
            <div class="col-md-6">
                <label class="form-label">Качество (в %)</label>
                <input type="range" class="form-range js-quality" name="text_bg_quality" min="40" max="100" step="5" value="85">
                <small class="text-muted">Текущий: <span class="js-quality-val">85</span>%</small>
            </div>
        </div>
    </div>
    <div class="mb-3">
        <label for="position" class="form-label">Расположение текста</label>
        <select id="position" name="position" class="form-select">
            <option value="left" {{ $slider->position === 'left' ? 'selected' : '' }}>Слева</option>
            <option value="center" {{ $slider->position === 'center' ? 'selected' : '' }}>По центру</option>
            <option value="right" {{ $slider->position === 'right' ? 'selected' : '' }}>Справа</option>
        </select>
    </div>
    <div class="mb-3">
        <label for="order" class="form-label">Порядок</label>
        <input type="number" class="form-control" id="order" name="order" value="{{ $slider->order }}">
    </div>
    <div class="mb-3">
        <label for="active" class="form-label">Статус</label>
        <select id="active" name="active" class="form-select">
            <option value="1" {{ $slider->active ? 'selected' : '' }}>Вкл</option>
            <option value="0" {{ !$slider->active ? 'selected' : '' }}>Выкл</option>
        </select>
    </div>
    <button type="submit" class="btn btn-success">Сохранить</button>
    <a href="{{ route('admin.sliders.index') }}" class="btn btn-secondary">Назад</a>
</form>
@endsection

@section('scripts')
    @include('admin.partials.wysiwyg-scripts')
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
