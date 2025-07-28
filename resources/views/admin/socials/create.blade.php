@extends('admin.index')
@section('content')
<h3>Добавить контакт</h3>
@if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<form action="{{ route('socials.store') }}" method="POST">
    @csrf
    <div class="mb-3">
        <label for="icon" class="form-label">Иконка (Font Awesome)</label>
        <input type="text" class="form-control" id="icon" name="icon" placeholder="fab fa-whatsapp" required>
        <small class="text-muted">Пример: fab fa-whatsapp, fab fa-telegram</small>
    </div>
    <div class="mb-3">
        <label for="title" class="form-label">Заголовок</label>
        <input type="text" class="form-control" id="title" name="title" required>
    </div>
    <div class="mb-3">
        <label for="link" class="form-label">Ссылка</label>
        <input type="text" class="form-control" id="link" name="link" required>
    </div>
    <div class="mb-3">
        <label for="text" class="form-label">Текст</label>
        <input type="text" class="form-control" id="text" name="text" required>
    </div>
    <div class="mb-3">
        <label for="order" class="form-label">Порядок</label>
        <input type="number" class="form-control" id="order" name="order" value="0">
    </div>
    <button type="submit" class="btn btn-success">Сохранить</button>
    <a href="{{ route('socials.index') }}" class="btn btn-secondary">Назад</a>
</form>
@endsection 