@extends('admin.index')@section('content')
<h1>Обо мне</h1>
@if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<form action="{{ route('admin.about.update') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="form-group mb-3">
        <label for="image" class="form-label">Фото</label><br>
        @if($about && $about->image)
            <img src="{{ asset('storage/'.$about->image) }}" alt="" width="120" class="mb-2"><br>
        @endif
        <input type="file" class="form-control" id="image" name="image">
        <small class="text-muted">Оставьте пустым, если не хотите менять фото</small>
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
    <div class="form-group mb-3">
        <label for="text" class="form-label">Текст</label>
        <textarea class="form-control wysiwyg" id="text" name="text" rows="6">{{ $about ? $about->text : '' }}</textarea>
    </div>
    <button type="submit" class="btn btn-success">Сохранить</button>
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
