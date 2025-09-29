@extends('admin.index')
@section('content')
<h3>Добавить преимущество</h3>
@if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<form action="{{ route('admin.advantages.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="mb-3">
        <label for="image" class="form-label">Изображение</label>
        <input type="file" class="form-control" id="image" name="image" required>
        <div class="row g-2 mt-2">
            <div class="col-md-6">
                <label class="form-label">Размер (в %)</label>
                <input type="range" class="form-range js-scale" name="image_scale" min="10" max="100" step="5" value="100">
                <small class="text-muted">Текущий: <span class="js-scale-val">100</span>%</small>
            </div>
            <div class="col-md-6">
                <label class="form-label">Качество (в %)</label>
                <input type="range" class="form-range js-quality" name="image_quality" min="40" max="100" step="5" value="85">
                <small class="text-muted">Текущий: <span class="js-quality-val">85</span>%</small>
            </div>
        </div>
    </div>
    <div class="mb-3">
        <label for="title" class="form-label">Заголовок</label>
        <input type="text" class="form-control" id="title" name="title" required>
    </div>
    <div class="mb-3">
        <label for="text" class="form-label">Текст</label>
        <textarea class="form-control wysiwyg" id="text" name="text" rows="4"></textarea>
    </div>
    <div class="mb-3">
        <label for="active" class="form-label">Статус</label>
        <select id="active" name="active" class="form-select">
            <option value="1" selected>Вкл</option>
            <option value="0">Выкл</option>
        </select>
    </div>
    <button type="submit" class="btn btn-success">Сохранить</button>
    <a href="{{ route('admin.advantages.index') }}" class="btn btn-secondary">Назад</a>
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
