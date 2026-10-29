@extends('admin.index')
@php use Illuminate\Support\Str; @endphp
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-services-list.css') }}?v=20261028-2">
@endpush
@section('content')
<div class="d-flex justify-content-between align-items-center services-index-header">
    <h1 class="visually-hidden">Услуги</h1>
    <span class="services-mobile-title d-md-none" aria-hidden="true">Услуги</span>
    <a href="{{ route('admin.services.create') }}" class="btn btn-primary services-add-button d-md-none"><i class="fa fa-plus" aria-hidden="true"></i> Добавить</a>
</div>
<x-admin.fab class="services-fab" label="Добавить услугу" :href="route('admin.services.create')" />
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
<form id="status-form" action="{{ route('admin.services.status') }}" method="POST">
    @csrf
</form>
<div class="admin-grid services-grid" style="--grid-cols: 120px 150px 1fr 2fr 180px 180px;">
    <div class="admin-grid-header">
        <div>Порядок</div>
        <div>Иллюстрация V2</div>
        <div>Заголовок</div>
        <div>Текст</div>
        <div>Статус</div>
        <div class="text-end">Действия</div>
    </div>
    <div class="admin-grid-body js-sortable" id="srvSort" data-custom-sort="1">
        @foreach($services as $service)
            <div class="admin-grid-row admin-grid-row--media" data-id="{{ $service->id }}">
                <div class="js-order-label text-muted admin-grid-cell--order" style="cursor:grab;"><i class="fa fa-grip-vertical me-1"></i>{{ $loop->iteration }}</div>
                <div class="admin-grid-cell--image"><img src="{{ $service->stickerImageUrl() }}" alt="" width="90" class="rounded shadow-sm"></div>
                <div class="fw-semibold admin-grid-cell--title">{{ $service->title }}</div>
                <div class="text-clip admin-grid-cell--description">{{ Str::limit(html_entity_decode(strip_tags($service->text), ENT_QUOTES | ENT_HTML5, 'UTF-8'), 140) }}</div>
                <div class="admin-grid-cell--status">
                    <div class="d-flex align-items-center gap-2">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input js-status-toggle" type="checkbox" data-id="{{ $service->id }}" {{ $service->active ? 'checked' : '' }} aria-label="Показывать услугу">
                        </div>
                    </div>
                    <span class="admin-status-label">{{ $service->active ? 'Включено' : 'Выключено' }}</span>
                    <input type="hidden" name="orders[{{ $service->id }}]" value="{{ $loop->iteration }}" class="js-order-input" form="status-form">
                </div>
                <div class="actions admin-grid-cell--actions">
                    <x-admin.actions-menu label="Действия с услугой {{ $service->title }}">
                        <a href="{{ route('admin.services.edit', $service->id) }}" class="admin-actions-menu__item"><i class="fa fa-pen" aria-hidden="true"></i><span>Редактировать</span></a>
                        <form action="{{ route('admin.services.destroy', $service->id) }}" method="POST" class="js-delete-form" data-confirm="Удалить услугу?">
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
<script>
document.addEventListener('DOMContentLoaded', ()=>{
    const renumber = ()=>{
        document.querySelectorAll('#srvSort .admin-grid-row').forEach((row, idx)=>{
            row.querySelector('.js-order-label').innerHTML = `<i class="fa fa-grip-vertical me-1"></i>${idx+1}`;
            const orderInput = row.querySelector('.js-order-input');
            if (orderInput) orderInput.value = idx+1;
        });
    };
    renumber();
    document.querySelectorAll('#srvSort .js-status-toggle').forEach(toggle=>{
        toggle.addEventListener('change', ()=>{
            const form = document.getElementById('status-form');
            form.querySelectorAll('input[name^="statuses["]').forEach(el=>el.remove());
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `statuses[${toggle.dataset.id}]`;
            input.value = toggle.checked ? 1 : 0;
            form.appendChild(input);
            form.submit();
        });
    });
    if (window.Sortable) {
        Sortable.create(document.getElementById('srvSort'), {
            animation:150,
            handle: '.js-order-label',
            onEnd: ()=>{
                renumber();
                document.getElementById('status-form').submit();
            }
        });
    }
});
</script>
@endsection 
