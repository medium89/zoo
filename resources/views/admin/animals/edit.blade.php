@extends('admin.index')

@section('content')
<div class="container-fluid">
    <h1 class="mb-3">Редактировать питомца</h1>
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    <form action="{{ route('admin.animals.update', $animal) }}" method="POST" enctype="multipart/form-data" class="card">
        @csrf
        @method('PUT')
        @include('admin.animals.partials.form', ['animal' => $animal, 'clients' => $clients])
    </form>
</div>
@endsection
