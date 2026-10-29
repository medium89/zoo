@extends('admin.index')
@php use Illuminate\Support\Str; @endphp
@section('content')
<div class="d-flex justify-content-between align-items-center">
    <h1>Преимущества</h1>
    <a href="{{ route('admin.advantages.create') }}" class="btn btn-primary d-none">Добавить</a>
</div>
<x-admin.fab label="Добавить преимущество" :href="route('admin.advantages.create')" />
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
<form action="{{ route('admin.advantages.background') }}" method="POST" class="d-flex flex-wrap align-items-end gap-2 mb-3">
    @csrf
    <label class="form-label mb-0">Фон секции преимуществ<br><input class="form-control form-control-color" type="color" name="advantages_bg_color" value="{{ \App\Models\SiteSetting::first()?->advantages_bg_color ?? '#7845ab' }}"></label>
    <button type="submit" class="btn btn-primary">Сохранить цвет</button>
    <button type="submit" class="btn btn-outline-secondary" name="reset_bg" value="1">По умолчанию</button>
</form>
<form id="status-form" action="{{ route('admin.advantages.status') }}" method="POST">
    @csrf
</form>
<div class="admin-grid" style="--grid-cols: 120px 150px 1fr 2fr 180px 180px;">
    <div class="admin-grid-header">
        <div>Порядок</div>
        <div>Изображение</div>
        <div>Заголовок</div>
        <div>Краткое содержание</div>
        <div>Статус</div>
        <div class="text-end">Действия</div>
    </div>
    <div class="admin-grid-body js-sortable" id="advSort" data-custom-sort="1">
        @foreach($advantages as $advantage)
            <div class="admin-grid-row admin-grid-row--media adv-card-row" data-id="{{ $advantage->id }}">
                <div class="js-order-label text-muted admin-grid-cell--order" style="cursor:grab;"><i class="fa fa-grip-vertical me-1"></i>{{ $loop->iteration }}</div>
                <div class="admin-grid-cell--image"><img src="{{ $advantage->image ? asset('storage/' . $advantage->image) : asset('images/image-placeholder.svg') }}" alt="" width="90" class="rounded shadow-sm"></div>
                <div class="fw-semibold admin-grid-cell--title">{{ $advantage->title }}</div>
                <div class="adv-text text-clip admin-grid-cell--description">{{ Str::limit(strip_tags($advantage->short_text ?: $advantage->text), 260) }}</div>
                <div class="d-flex align-items-center admin-grid-cell--status">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input js-status-toggle" type="checkbox" data-id="{{ $advantage->id }}" {{ $advantage->active ? 'checked' : '' }} aria-label="Показывать преимущество">
                    </div>
                    <span class="admin-status-label">{{ $advantage->active ? 'Включено' : 'Выключено' }}</span>
                    <input type="hidden" name="orders[{{ $advantage->id }}]" value="{{ $loop->iteration }}" class="js-order-input" form="status-form">
                </div>
                <div class="actions admin-grid-cell--actions">
                    <x-admin.actions-menu label="Действия с преимуществом {{ $advantage->title }}">
                        <a href="{{ route('admin.advantages.edit', $advantage->id) }}" class="admin-actions-menu__item"><i class="fa fa-pen" aria-hidden="true"></i><span>Редактировать</span></a>
                        <form action="{{ route('admin.advantages.destroy', $advantage->id) }}" method="POST" class="js-delete-form" data-confirm="Удалить преимущество?">
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
        document.querySelectorAll('#advSort .admin-grid-row').forEach((row, idx)=>{
            row.querySelector('.js-order-label').innerHTML = `<i class="fa fa-grip-vertical me-1"></i>${idx+1}`;
            const orderInput = row.querySelector('.js-order-input');
            if (orderInput) orderInput.value = idx+1;
        });
    };
    renumber();
    document.querySelectorAll('#advSort .js-status-toggle').forEach(toggle=>{
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
        Sortable.create(document.getElementById('advSort'), {
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
