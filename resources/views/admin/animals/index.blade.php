@extends('admin.index')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Питомцы</h1>
        <a href="{{ route('admin.animals.create') }}" class="btn btn-primary">Добавить</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($animals->count())
        <form id="animals-order-form" action="{{ route('admin.animals.reorder') }}" method="POST">@csrf</form>
        <div class="admin-grid" style="--grid-cols: 100px 1fr 2fr 160px;">
            <div class="admin-grid-header">
                <div>#</div>
                <div>Кличка</div>
                <div>Описание</div>
                <div class="text-end">Действия</div>
            </div>
            <div class="admin-grid-body js-sortable" id="animalSort" data-custom-sort="1">
                @foreach($animals as $animal)
                    <div class="admin-grid-row" data-id="{{ $animal->id }}">
                        <div class="js-order-label text-muted" style="cursor:grab;"><i class="fa fa-grip-vertical me-1"></i>{{ $loop->iteration }}</div>
                        <div>{{ $animal->name }}</div>
                        <div class="text-clip">{{ $animal->description }}</div>
                        <input type="hidden" name="orders[{{ $animal->id }}]" value="{{ $loop->iteration }}" class="js-order-input" form="animals-order-form">
                        <div class="actions">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('admin.animals.edit', $animal) }}" class="btn btn-sm btn-primary text-white"><i class="fa fa-pen"></i></a>
                                <form action="{{ route('admin.animals.destroy', $animal) }}" method="POST" class="d-inline js-delete-form" data-confirm="Удалить питомца?">
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
        {{ $animals->links() }}
    @else
        <div class="text-muted">Питомцев пока нет.</div>
    @endif
</div>
<script>
document.addEventListener('DOMContentLoaded', ()=>{
    const renumber = ()=>{
        document.querySelectorAll('#animalSort .admin-grid-row').forEach((row, idx)=>{
            row.querySelector('.js-order-label').innerHTML = `<i class="fa fa-grip-vertical me-1"></i>${idx+1}`;
            const orderInput = row.querySelector('.js-order-input');
            if (orderInput) orderInput.value = idx+1;
        });
    };
    renumber();
    if (window.Sortable && document.getElementById('animalSort')) {
        Sortable.create(document.getElementById('animalSort'), {
            animation:150,
            handle: '.js-order-label',
            onEnd: ()=>{
                renumber();
                document.getElementById('animals-order-form').submit();
            }
        });
    }
});
</script>
@endsection
