@extends('admin.index')

@section('content')
<div class="container-fluid">
    <h1 class="mb-3">Добавить питомца</h1>
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    <form action="{{ route('admin.animals.store') }}" method="POST" enctype="multipart/form-data" class="card">
        @csrf
        @include('admin.animals.partials.form', ['animal' => null, 'clients' => $clients])
    </form>
</div>
@endsection
