@extends('admin.index')
@section('content')
<div class="admin-live-preview-layout">
<div class="admin-live-preview-layout__fields">
<h3>Добавить фото</h3>
@if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<form action="{{ route('admin.galleries.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="mb-3">
        <label for="images" class="form-label">Фотографии</label>
        <input type="file" class="form-control" id="images" name="images[]" multiple required accept="image/*">
        <small class="text-muted">Можно выбрать несколько файлов</small>
    </div>
    <!-- Индивидуальные бегунки будут в карточках предпросмотра ниже -->

    <div class="mb-3">
        <label class="form-label">Предпросмотр и оценка размера</label>
        <div id="previews" class="row g-3"></div>
        <small class="text-muted d-block mt-2">Настройки применяются отдельно к каждому фото (бегунки над превью).</small>
    </div>
    <button type="submit" class="btn btn-success" id="submitBtn">Загрузить</button>
</form>
</div>
@include('admin.partials.live-preview', ['kind' => 'gallery'])
</div>
@endsection
