@extends('admin.index')

@section('content')
<div class="container-fluid">
    <h1 class="mb-3">Новая категория</h1>
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    <form action="{{ route('admin.categories.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label">Название</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Slug</label>
            <input type="text" name="slug" class="form-control" placeholder="Автоиз названия, можно задать вручную">
        </div>
        <button class="btn btn-success">Сохранить</button>
    </form>
</div>
@endsection
