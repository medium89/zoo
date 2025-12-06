@extends('admin.index')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Социальные контакты</h1>
    <a href="{{ route('admin.socials.create') }}" class="btn btn-primary">Добавить контакт</a>
</div>
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
<form id="status-form" action="{{ route('admin.socials.status') }}" method="POST">
    @csrf
</form>
<div class="admin-grid" style="--grid-cols: 120px 140px 1fr 1.3fr 1fr 1.5fr 150px 150px;">
    <div class="admin-grid-header">
        <div>Порядок</div>
        <div>Иконка</div>
        <div>Заголовок</div>
        <div>Ссылка</div>
        <div>Текст ссылки</div>
        <div>Текст</div>
        <div>Статус</div>
        <div class="text-end">Действия</div>
    </div>
    <div class="admin-grid-body js-sortable" id="socSort" data-custom-sort="1">
        @foreach($socials as $social)
            <div class="admin-grid-row" data-id="{{ $social->id }}">
                <div class="js-order-label text-muted" style="cursor:grab;"><i class="fa fa-grip-vertical me-1"></i>{{ $loop->iteration }}</div>
                <div><i class="{{ $social->icon }}"></i> <span class="text-muted">{{ $social->icon }}</span></div>
                <div>{{ $social->title }}</div>
                <div><a href="{{ $social->link }}" target="_blank">{{ $social->link }}</a></div>
                <div>{{ $social->link_text }}</div>
                <div class="text-clip">{{ $social->text }}</div>
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input js-status-toggle" type="checkbox" data-id="{{ $social->id }}" {{ $social->active ? 'checked' : '' }}>
                        </div>
                    </div>
                    <input type="hidden" name="orders[{{ $social->id }}]" value="{{ $loop->iteration }}" class="js-order-input" form="status-form">
                </div>
                <div class="actions">
                    <div class="d-flex gap-2 align-items-center justify-content-end">
                        <a href="{{ route('admin.socials.edit', $social->id) }}" class="btn btn-sm btn-primary text-white"><i class="fa fa-pen"></i></a>
                        <form action="{{ route('admin.socials.destroy', $social->id) }}" method="POST" style="display:inline-block">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Удалить контакт?')">Удалить</button>
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
        document.querySelectorAll('#socSort .admin-grid-row').forEach((row, idx)=>{
            row.querySelector('.js-order-label').innerHTML = `<i class="fa fa-grip-vertical me-1"></i>${idx+1}`;
            const orderInput = row.querySelector('.js-order-input');
            if (orderInput) orderInput.value = idx+1;
        });
    };
    renumber();
    document.querySelectorAll('#socSort .js-status-toggle').forEach(toggle=>{
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
        Sortable.create(document.getElementById('socSort'), {
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
