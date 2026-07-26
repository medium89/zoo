@extends('admin.index')

@section('content')
<div class="container-fluid">
    <h1 class="mb-3">Редактировать клиента</h1>
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    <form action="{{ route('admin.clients.update', $client) }}" method="POST" class="card">
        @csrf
        @method('PUT')
        @include('admin.clients.partials.form', ['client' => $client])
    </form>
</div>
@endsection
