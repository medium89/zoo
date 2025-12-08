@extends('admin.index')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">Управление пользователями</h1>
    <p class="mb-4">Здесь вы можете управлять пользователями вашего сайта.</p>

    <a href="{{ route('admin.users.create') }}" class="btn btn-success mb-3">Добавить нового пользователя</a>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Список пользователей</h6>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
            <div class="admin-grid" style="--grid-cols: 80px 1fr 1fr 190px;">
                <div class="admin-grid-header">
                    <span>ID</span>
                    <span>Имя</span>
                    <span>Email</span>
                    <span>Действия</span>
                </div>
                <div class="admin-grid-body">
                    @foreach ($users as $user)
                        <div class="admin-grid-row" data-id="{{ $user->id }}">
                            <div data-label="ID">{{ $user->id }}</div>
                            <div data-label="Имя">{{ $user->name }}</div>
                            <div data-label="Email">{{ $user->email }}</div>
                            <div class="actions" data-label="Действия">
                                <div class="d-flex gap-1 flex-wrap justify-content-end w-100">
                                    <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-primary btn-sm">Редактировать</a>
                                    <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="m-0">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Вы уверены?')">Удалить</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 
