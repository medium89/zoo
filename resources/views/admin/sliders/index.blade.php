@extends('admin.index')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Слайды</h1>
    <a href="{{ route('admin.sliders.create') }}" class="btn btn-primary d-none">Добавить слайд</a>
</div>
<x-admin.fab label="Добавить слайд" :href="route('admin.sliders.create')" />
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
<form id="status-form" action="{{ route('admin.sliders.status') }}" method="POST">
    @csrf
</form>
<div class="admin-grid" style="--grid-cols: 120px 200px 1fr 180px;">
    <div class="admin-grid-header">
        <div>Порядок</div>
        <div>Изображение</div>
        <div>Статус</div>
        <div class="text-end">Действия</div>
    </div>
    <div class="admin-grid-body js-sortable" id="slidersSort" data-custom-sort="1">
        @foreach($sliders as $slider)
            <div class="admin-grid-row" data-id="{{ $slider->id }}">
                <div class="js-order-label text-muted" style="cursor:grab;"><i class="fa fa-grip-vertical me-1"></i>{{ $loop->iteration }}</div>
                <div><img src="{{ asset('storage/'.$slider->image) }}" alt="" width="120"></div>
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input js-status-toggle" type="checkbox" data-id="{{ $slider->id }}" {{ $slider->active ? 'checked' : '' }}>
                        </div>
                    </div>
                    <input type="hidden" name="orders[{{ $slider->id }}]" value="{{ $loop->iteration }}" class="js-order-input" form="status-form">
                </div>
                <div class="actions">
                    <x-admin.actions-menu label="Действия со слайдом">
                        <a href="{{ route('admin.sliders.edit', $slider) }}" class="admin-actions-menu__item"><i class="fa fa-pen" aria-hidden="true"></i><span>Редактировать</span></a>
                        <form action="{{ route('admin.sliders.destroy', $slider) }}" method="POST" class="js-delete-form" data-confirm="Удалить слайд?">
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
        document.querySelectorAll('#slidersSort .admin-grid-row').forEach((row, idx)=>{
            const label = row.querySelector('.js-order-label');
            const orderField = row.querySelector('.js-order-input');
            if (label) label.innerHTML = `<i class="fa fa-grip-vertical me-1"></i>${idx+1}`;
            if (orderField) orderField.value = idx + 1;
        });
    };
    renumber();

    document.querySelectorAll('.js-status-toggle').forEach(toggle=>{
        toggle.addEventListener('change', ()=>{
            const form = document.getElementById('status-form');
            // убрать старые статусы
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
        Sortable.create(document.getElementById('slidersSort'), {
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
