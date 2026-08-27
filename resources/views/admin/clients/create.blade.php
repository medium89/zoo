@extends('admin.index')

@section('content')
<div class="container-fluid">
    <h1 class="mb-3">Добавить клиента</h1>
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    <form action="{{ route('admin.clients.store') }}" method="POST" enctype="multipart/form-data" class="card">
        @csrf
        @include('admin.clients.partials.form', ['client' => null])
    </form>
</div>
@endsection
