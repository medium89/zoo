@extends('admin.index')

@section('content')
<div class="container-fluid">
    <h1 class="mb-3">Редактирование категории</h1>
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    <form action="{{ route('admin.categories.update', $category) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label class="form-label">Название</label>
            <input type="text" name="name" class="form-control" required value="{{ $category->name }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Slug</label>
            <input type="text" name="slug" class="form-control" value="{{ $category->slug }}" placeholder="Автоиз названия, можно задать вручную">
        </div>
        <button class="btn btn-success">Сохранить</button>
    </form>
</div>
@endsection
