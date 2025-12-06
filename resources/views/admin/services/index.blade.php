@extends('admin.index')
@php use Illuminate\Support\Str; @endphp
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Услуги</h1>
    <a href="{{ route('admin.services.create') }}" class="btn btn-primary">Добавить</a>
</div>
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
<form id="status-form" action="{{ route('admin.services.status') }}" method="POST">
    @csrf
</form>
<div class="admin-grid" style="--grid-cols: 120px 150px 1fr 2fr 180px 180px;">
    <div class="admin-grid-header">
        <div>Порядок</div>
        <div>Изображение</div>
        <div>Заголовок</div>
        <div>Текст</div>
        <div>Статус</div>
        <div class="text-end">Действия</div>
    </div>
    <div class="admin-grid-body js-sortable" id="srvSort" data-custom-sort="1">
        @foreach($services as $service)
            <div class="admin-grid-row" data-id="{{ $service->id }}">
                <div class="js-order-label text-muted" style="cursor:grab;"><i class="fa fa-grip-vertical me-1"></i>{{ $loop->iteration }}</div>
                <div><img src="{{ asset('storage/'.$service->image) }}" alt="" width="90" class="rounded shadow-sm"></div>
                <div class="fw-semibold">{{ $service->title }}</div>
                <div class="text-clip">{{ Str::limit(strip_tags($service->text), 140) }}</div>
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input js-status-toggle" type="checkbox" data-id="{{ $service->id }}" {{ $service->active ? 'checked' : '' }}>
                        </div>
                    </div>
                    <input type="hidden" name="orders[{{ $service->id }}]" value="{{ $loop->iteration }}" class="js-order-input" form="status-form">
                </div>
                <div class="actions">
                    <div class="d-flex gap-2 align-items-center justify-content-end">
                        <a href="{{ route('admin.services.edit', $service->id) }}" class="btn btn-sm btn-primary text-white"><i class="fa fa-pen"></i></a>
                        <form action="{{ route('admin.services.destroy', $service->id) }}" method="POST" style="display:inline-block">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Удалить?')">Удалить</button>
                        </form>
                    </div>
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
