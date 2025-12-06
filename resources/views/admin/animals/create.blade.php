@extends('admin.index')

@section('content')
<div class="container-fluid">
    <h1 class="mb-3">Добавить питомца</h1>
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    <form action="{{ route('admin.animals.store') }}" method="POST" class="card">
        @csrf
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Кличка</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Описание</label>
                <input type="text" name="description" class="form-control">
            </div>
        </div>
        <div class="card-footer d-flex gap-2">
            <button class="btn btn-success">Сохранить</button>
            <a href="{{ route('admin.animals.index') }}" class="btn btn-secondary">Назад</a>
        </div>
    </form>
</div>
@endsection
