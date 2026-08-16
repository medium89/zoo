@extends('admin.index')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">Редактировать пользователя</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Форма редактирования пользователя</h6>
        </div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form action="{{ route('admin.users.update', $user->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group mb-3">
                    <label for="name">Имя:</label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                </div>

                <div class="form-group mb-3">
                    <label for="email">Email:</label>
                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                </div>

                <div class="form-group mb-3">
                    <label for="password">Новый пароль (оставьте пустым, если не хотите менять):</label>
                    <input type="password" class="form-control" id="password" name="password">
                </div>

                <div class="form-group mb-3">
                    <label for="password_confirmation">Подтвердите новый пароль:</label>
                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                </div>

                <button type="submit" class="btn btn-success">Обновить пользователя</button>
            </form>
        </div>
    </div>
</div>
@endsection
