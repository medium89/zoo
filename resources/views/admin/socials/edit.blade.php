@extends('admin.index')
@section('content')
<h3>Редактировать контакт</h3>
@if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<form action="{{ route('admin.socials.update', $social->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label for="icon" class="form-label">Иконка (Font Awesome)</label>
        <input type="text" class="form-control" id="icon" name="icon" value="{{ $social->icon }}" required>
        <small class="text-muted">Пример: fab fa-whatsapp, fab fa-telegram</small>
    </div>
    <div class="mb-3">
        <label for="title" class="form-label">Заголовок</label>
        <input type="text" class="form-control" id="title" name="title" value="{{ $social->title }}" required>
    </div>
    <div class="mb-3">
        <label for="link" class="form-label">Ссылка</label>
        <input type="text" class="form-control" id="link" name="link" value="{{ $social->link }}" required>
    </div>
    <div class="mb-3">
        <label for="text" class="form-label">Текст</label>
        <input type="text" class="form-control" id="text" name="text" value="{{ $social->text }}" required>
    </div>
    <div class="mb-3">
        <label for="order" class="form-label">Порядок</label>
        <input type="number" class="form-control" id="order" name="order" value="{{ $social->order }}">
    </div>
    <button type="submit" class="btn btn-success">Сохранить</button>
    <a href="{{ route('admin.socials.index') }}" class="btn btn-secondary">Назад</a>
</form>
@endsection 