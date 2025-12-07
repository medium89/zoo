@extends('admin.index')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Сообщения обратной связи</h1>
    </div>
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form id="feedbacks-form" action="{{ route('admin.feedbacks.reorder') }}" method="POST">@csrf</form>
    <div class="admin-grid" style="--grid-cols: 100px 1.2fr 1fr 2fr 1fr 1fr 160px;">
        <div class="admin-grid-header">
            <div>Порядок</div>
            <div>Имя</div>
            <div>Телефон</div>
            <div>Сообщение</div>
            <div>Статус</div>
            <div>Получено</div>
            <div class="text-end">Действия</div>
        </div>
        <div class="admin-grid-body js-sortable" id="feedbacksSort" data-custom-sort="1">
            @foreach ($feedbacks as $feedback)
                <div class="admin-grid-row" data-id="{{ $feedback->id }}">
                    <div class="js-order-label text-muted" style="cursor:grab;"><i class="fa fa-grip-vertical me-1"></i>{{ $loop->iteration }}</div>
                    <div>{{ $feedback->name }}</div>
                    <div>{{ $feedback->phone ?? 'Не указан' }}</div>
                    <div class="text-clip">{{ \Illuminate\Support\Str::limit($feedback->message, 160) }}</div>
                    <div class="d-flex align-items-center">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input js-status-toggle" type="checkbox" data-id="{{ $feedback->id }}" {{ $feedback->status === 'completed' ? 'checked' : '' }}>
                        </div>
                        <input type="hidden" name="orders[{{ $feedback->id }}]" value="{{ $loop->iteration }}" class="js-order-input" form="feedbacks-form">
                    </div>
                    <div>{{ $feedback->created_at->format('d.m.Y H:i') }}</div>
                    <div class="actions">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.feedbacks.edit', $feedback->id) }}" class="btn btn-sm btn-primary text-white"><i class="fa fa-pen"></i></a>
                            <form action="{{ route('admin.feedbacks.destroy', $feedback->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Удалить сообщение?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mt-3 d-flex justify-content-start">
        {{ $feedbacks->links('pagination::bootstrap-4') }}
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', ()=>{
    const renumber = ()=>{
        document.querySelectorAll('#feedbacksSort .admin-grid-row').forEach((row, idx)=>{
            row.querySelector('.js-order-label').innerHTML = `<i class="fa fa-grip-vertical me-1"></i>${idx+1}`;
            const orderInput = row.querySelector('.js-order-input');
            if (orderInput) orderInput.value = idx+1;
        });
    };
    renumber();

    const form = document.getElementById('feedbacks-form');
    document.querySelectorAll('#feedbacksSort .js-status-toggle').forEach(toggle=>{
        toggle.addEventListener('change', ()=>{
            form.querySelectorAll('input[name^="statuses["]').forEach(el=>el.remove());
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `statuses[${toggle.dataset.id}]`;
            input.value = toggle.checked ? 'completed' : 'new';
            form.appendChild(input);
            form.submit();
        });
    });

    if (window.Sortable) {
        Sortable.create(document.getElementById('feedbacksSort'), {
            animation:150,
            handle: '.js-order-label',
            onEnd: ()=>{
                renumber();
                form.submit();
            }
        });
    }
});
</script>
@endsection 
