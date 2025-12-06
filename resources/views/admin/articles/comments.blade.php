@extends('admin.index')

@section('content')
<div class="container-fluid">
    <h1 class="mb-3">Комментарии к статьям</h1>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <form id="comments-form" action="{{ route('admin.article-comments.status') }}" method="POST">@csrf</form>
    <div class="admin-grid" style="--grid-cols: 100px 1.6fr 1.2fr 2fr 1.2fr 1fr 140px;">
        <div class="admin-grid-header">
            <div>Порядок</div>
            <div>Статья</div>
            <div>Email</div>
            <div>Текст</div>
            <div>Статус</div>
            <div>Создан</div>
            <div class="text-end">Действия</div>
        </div>
        <div class="admin-grid-body js-sortable" id="commentsSort" data-custom-sort="1">
        @forelse($comments as $c)
            <div class="admin-grid-row" data-id="{{ $c->id }}">
                <div class="js-order-label text-muted" style="cursor:grab;"><i class="fa fa-grip-vertical me-1"></i>{{ $loop->iteration }}</div>
                <div class="text-clip">{{ optional($c->article)->title }}</div>
                <div>{{ $c->email }}</div>
                <div class="text-clip">{{ $c->content }}</div>
                <div class="d-flex align-items-center">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input js-status-toggle" type="checkbox" data-id="{{ $c->id }}" {{ $c->status === 'approved' ? 'checked' : '' }}>
                    </div>
                    <input type="hidden" name="orders[{{ $c->id }}]" value="{{ $loop->iteration }}" class="js-order-input" form="comments-form">
                </div>
                <div>{{ $c->created_at->format('d.m.Y H:i') }}</div>
                <div class="actions">
                    <div class="d-flex justify-content-end gap-2">
                        <form action="{{ route('admin.article-comments.destroy', $c) }}" method="POST" onsubmit="return confirm('Удалить комментарий?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-muted">Комментариев нет.</div>
        @endforelse
        </div>
    </div>
    {{ $comments->links() }}
</div>
<script>
document.addEventListener('DOMContentLoaded', ()=>{
    const renumber = ()=>{
        document.querySelectorAll('#commentsSort .admin-grid-row').forEach((row, idx)=>{
            row.querySelector('.js-order-label').innerHTML = `<i class="fa fa-grip-vertical me-1"></i>${idx+1}`;
            const orderInput = row.querySelector('.js-order-input');
            if (orderInput) orderInput.value = idx+1;
        });
    };
    renumber();

    const form = document.getElementById('comments-form');
    document.querySelectorAll('#commentsSort .js-status-toggle').forEach(toggle=>{
        toggle.addEventListener('change', ()=>{
            form.querySelectorAll('input[name^="statuses["]').forEach(el=>el.remove());
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `statuses[${toggle.dataset.id}]`;
            input.value = toggle.checked ? 'approved' : 'pending';
            form.appendChild(input);
            form.submit();
        });
    });

    if (window.Sortable) {
        Sortable.create(document.getElementById('commentsSort'), {
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
