@extends('admin.index')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Слайды</h1>
    <a href="{{ route('admin.sliders.create') }}" class="btn btn-primary">Добавить слайд</a>
</div>
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
<form id="status-form" action="{{ route('admin.sliders.status') }}" method="POST">
    @csrf
</form>
<div class="admin-grid" style="--grid-cols: 140px 200px 1fr 220px;">
    <div class="admin-grid-header">
        <div>Порядок</div>
        <div>Изображение</div>
        <div>Статус</div>
        <div class="text-end">Действия</div>
    </div>
    <div class="admin-grid-body js-sortable" id="slidersSort">
        @foreach($sliders as $slider)
            <div class="admin-grid-row" data-id="{{ $slider->id }}">
                <div class="js-order-label text-muted" style="cursor:grab;"><i class="fa fa-grip-vertical me-1"></i>{{ $slider->order }}</div>
                <div><img src="{{ asset('storage/'.$slider->image) }}" alt="" width="120"></div>
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <input type="hidden" name="statuses[{{ $slider->id }}]" value="0" form="status-form">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input js-status-toggle" type="checkbox" data-id="{{ $slider->id }}" {{ $slider->active ? 'checked' : '' }}>
                        </div>
                    </div>
                    <input type="hidden" name="orders[{{ $slider->id }}]" value="{{ $slider->order }}" class="js-order-input" form="status-form">
                </div>
                <div class="actions">
                    <div class="d-flex justify-content-end gap-2 align-items-center">
                        <a href="{{ route('admin.sliders.edit', $slider) }}" class="btn btn-sm btn-primary text-white"><i class="fa fa-pen"></i></a>
                        <form action="{{ route('admin.sliders.destroy', $slider) }}" method="POST" style="display:inline-block">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Удалить слайд?')">Удалить</button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', ()=>{
    document.querySelectorAll('.js-status-toggle').forEach(toggle=>{
        toggle.addEventListener('change', ()=>{
            const form = document.getElementById('status-form');
            let input = form.querySelector(`input[name="statuses[${toggle.dataset.id}]"]`);
            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = `statuses[${toggle.dataset.id}]`;
                form.appendChild(input);
            }
            input.value = toggle.checked ? 1 : 0;
            form.submit();
        });
    });
});
</script>
<style>
    .admin-grid {
        display: grid;
        gap: 12px;
    }
    .admin-grid-header {
        display: grid;
        grid-template-columns: var(--grid-cols, repeat(auto-fit, minmax(120px,1fr)));
        gap: 12px;
        font-weight: 600;
        color: #6c757d;
        font-size: 0.95rem;
        padding-left: 2px;
    }
    .admin-grid-body {
        display: grid;
        gap: 12px;
    }
    .admin-grid-row {
        display: grid;
        grid-template-columns: var(--grid-cols, repeat(auto-fit, minmax(120px,1fr)));
        gap: 12px;
        padding: 12px;
        border-radius: 14px;
        background: #fff;
        border: 1px solid #e9ecef;
        box-shadow: 0 8px 20px rgba(31,35,42,0.08);
        align-items: center;
    }
    .admin-grid-row .actions {
        display: flex;
        justify-content: flex-end;
    }
</style>
@endsection 
