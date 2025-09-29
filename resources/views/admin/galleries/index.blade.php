@extends('admin.index')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Фотоальбом</h1>
    <a href="{{ route('admin.galleries.create') }}" class="btn btn-primary">Добавить фото</a>
</div>
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
<form id="status-form" action="{{ route('admin.galleries.status') }}" method="POST">
    @csrf
</form>
<div class="row">
    @foreach($galleries as $gallery)
        <div class="col-md-3 mb-4">
            <div class="card">
                @php
                    $thumb = preg_replace('/^galleries\//', 'galleries/thumbs/', $gallery->image);
                    $thumbExists = \Illuminate\Support\Facades\Storage::disk('public')->exists($thumb);
                    $thumbUrl = $thumbExists ? asset('storage/' . $thumb) : asset('storage/' . $gallery->image);
                @endphp
                <img src="{{ $thumbUrl }}" class="card-img-top" alt="" style="height:180px;object-fit:cover;">
                <div class="card-body text-center">
                    <div class="mb-2">
                        <select name="numbers[{{ $gallery->id }}]" class="form-select form-select-sm" form="status-form">
                            @for($i = 1; $i <= $galleries->count(); $i++)
                                <option value="{{ $i }}" {{ $gallery->number == $i ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <form action="{{ route('admin.galleries.destroy', $gallery->id) }}" method="POST" class="mb-2">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Удалить фото?')">Удалить</button>
                    </form>
                    <select name="statuses[{{ $gallery->id }}]" class="form-select form-select-sm" form="status-form">
                        <option value="1" {{ $gallery->active ? 'selected' : '' }}>Вкл</option>
                        <option value="0" {{ !$gallery->active ? 'selected' : '' }}>Выкл</option>
                    </select>
                </div>
            </div>
        </div>
    @endforeach
</div>
<button type="submit" form="status-form" class="btn btn-primary mt-2">Сохранить</button>
@endsection
