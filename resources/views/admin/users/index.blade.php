@extends('admin.index')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">Управление пользователями</h1>
    <p class="mb-4">Здесь вы можете управлять пользователями вашего сайта.</p>

    <a href="{{ route('admin.users.create') }}" class="btn btn-success mb-3 d-none">Добавить нового пользователя</a>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    <x-admin.filters :action="route('admin.users.index')" :filters="$filters" placeholder="Имя или email" :auto="true" :attached="true">
        <label class="admin-filter-bar__field">Доступ<select name="role" class="form-select"><option value="">Все</option><option value="admin" @selected(($filters['role'] ?? '') === 'admin')>Администраторы</option><option value="user" @selected(($filters['role'] ?? '') === 'user')>Пользователи</option></select></label>
    </x-admin.filters>
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
                        <x-admin.actions-menu label="Действия с пользователем {{ $user->name }}">
                            <a href="{{ route('admin.users.edit', $user->id) }}" class="admin-actions-menu__item"><i class="fa fa-pen" aria-hidden="true"></i><span>Редактировать</span></a>
                            <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="js-delete-form" data-confirm="Удалить пользователя {{ $user->name }}?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="admin-actions-menu__item admin-actions-menu__item--danger"><i class="fa fa-trash" aria-hidden="true"></i><span>Удалить</span></button>
                            </form>
                        </x-admin.actions-menu>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
<x-admin.fab label="Добавить пользователя" :href="route('admin.users.create')" />
@endsection 
