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
<div class="row js-sortable">
    @foreach($galleries as $gallery)
        <div class="col-6 col-sm-4 col-md-3 col-lg-2 mb-3" data-id="{{ $gallery->id }}">
            <div class="card h-100 shadow-sm">
                @php
                    $thumb = preg_replace('/^galleries\//', 'galleries/thumbs/', $gallery->image);
                    $thumbExists = \Illuminate\Support\Facades\Storage::disk('public')->exists($thumb);
                    $thumbUrl = $thumbExists ? asset('storage/' . $thumb) : asset('storage/' . $gallery->image);
                @endphp
                <div class="position-relative">
                    <div class="position-absolute top-0 start-0 m-2 badge bg-secondary js-order-label" style="cursor:grab;"><i class="fa fa-grip-vertical me-1"></i>{{ $gallery->number }}</div>
                    <img src="{{ $thumbUrl }}" class="card-img-top" alt="" style="height:140px;object-fit:cover;">
                </div>
                <div class="card-body text-center d-flex flex-column gap-2 py-2">
                    <input type="hidden" name="numbers[{{ $gallery->id }}]" value="{{ $gallery->number }}" class="js-order-input" form="status-form">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <form action="{{ route('admin.galleries.destroy', $gallery->id) }}" method="POST" class="mb-0">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Удалить фото?')">Удалить</button>
                        </form>
                        <div class="form-check form-switch d-flex align-items-center gap-2 mb-0">
                            <input type="hidden" name="statuses[{{ $gallery->id }}]" value="0" form="status-form">
                            <input class="form-check-input" type="checkbox" name="statuses[{{ $gallery->id }}]" value="1" form="status-form" {{ $gallery->active ? 'checked' : '' }}>
                            <label class="form-check-label mb-0">Статус</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
<button type="submit" form="status-form" class="btn btn-primary mt-2">Сохранить</button>
@endsection
