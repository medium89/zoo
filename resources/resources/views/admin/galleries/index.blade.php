@extends('admin.index')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Фотоальбом</h1>
    <a href="{{ route('admin.galleries.create') }}" class="btn btn-primary">Добавить фото</a>
</div>
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
<div class="row">
    @foreach($galleries as $gallery)
        <div class="col-md-3 mb-4">
            <div class="card">
                <img src="{{ asset('storage/'.$gallery->image) }}" class="card-img-top" alt="" style="height:180px;object-fit:cover;">
                <div class="card-body text-center">
                    <form action="{{ route('admin.galleries.destroy', $gallery->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Удалить фото?')">Удалить</button>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection 