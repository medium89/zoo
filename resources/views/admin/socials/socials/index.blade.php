@extends('admin.index')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Социальные контакты</h1>
    <a href="{{ route('admin.socials.create') }}" class="btn btn-primary">Добавить контакт</a>
</div>
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
<table class="table table-bordered">
    <thead>
        <tr>
            <th>#</th>
            <th>Иконка</th>
            <th>Заголовок</th>
            <th>Ссылка</th>
            <th>Текст ссылки</th>
            <th>Текст</th>
            <th>Порядок</th>
            <th>Действия</th>
        </tr>
    </thead>
    <tbody>
    @foreach($socials as $social)
        <tr>
            <td>{{ $social->id }}</td>
            <td><i class="{{ $social->icon }}"></i> <span class="text-muted">{{ $social->icon }}</span></td>
            <td>{{ $social->title }}</td>
            <td><a href="{{ $social->link }}" target="_blank">{{ $social->link }}</a></td>
            <td>{{ $social->link_text }}</td>
            <td>{{ $social->text }}</td>
            <td>{{ $social->order }}</td>
            <td class="actions">
                <x-admin.actions-menu label="Действия с контактом {{ $social->title }}">
                <a href="{{ route('admin.socials.edit', $social->id) }}" class="admin-actions-menu__item"><i class="fa fa-pen" aria-hidden="true"></i><span>Редактировать</span></a>
                <form action="{{ route('admin.socials.destroy', $social->id) }}" method="POST" class="js-delete-form" data-confirm="Удалить контакт?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="admin-actions-menu__item admin-actions-menu__item--danger"><i class="fa fa-trash" aria-hidden="true"></i><span>Удалить</span></button>
                </form>
                </x-admin.actions-menu>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
@endsection 
