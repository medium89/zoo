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
@endsection
