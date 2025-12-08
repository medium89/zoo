@extends('admin.index')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Архив записей</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.boarding.index') }}" class="btn btn-outline-primary">Календарь</a>
            <a href="{{ route('admin.boarding.animals') }}" class="btn btn-outline-secondary">Животные</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            @if($archived->count())
                <div class="admin-grid" style="--grid-cols: 60px 1.2fr 1.5fr 1fr 1.3fr 1fr 190px;">
                    <div class="admin-grid-header">
                        <span>#</span>
                        <span>Кличка</span>
                        <span>Описание</span>
                        <span>Тип услуги</span>
                        <span>Период</span>
                        <span>Архивировано</span>
                        <span class="text-end">Действия</span>
                    </div>
                    <div class="admin-grid-body">
                        @foreach($archived as $row)
                            <div class="admin-grid-row" data-id="{{ $row->id }}">
                                <div data-label="ID">{{ $row->id }}</div>
                                <div data-label="Кличка">{{ $row->name }}</div>
                                <div data-label="Описание">{{ $row->description }}</div>
                                <div data-label="Тип услуги">{{ $row->service_type }}</div>
                                <div data-label="Период">{{ $row->start_date->toDateString() }} — {{ $row->end_date->toDateString() }}</div>
                                <div data-label="Архивировано">{{ optional($row->archived_at)->format('d.m.Y H:i') }}</div>
                                <div class="actions" data-label="Действия">
                                    <div class="d-flex flex-wrap gap-1 justify-content-end w-100">
                                        <form action="{{ route('admin.boarding.restore', $row) }}" method="POST" class="m-0">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success">Восстановить</button>
                                        </form>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger js-delete-entry"
                                                data-url="{{ route('admin.boarding.destroy', $row) }}"
                                                data-id="{{ $row->id }}"
                                                data-name="{{ $row->name }}">
                                            Удалить
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="text-muted">Архив пуст.</div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content" id="deleteForm">
            @csrf
            @method('DELETE')
            <div class="modal-header">
                <h5 class="modal-title text-danger">Удалить запись</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="deleteText"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="submit" class="btn btn-danger">Удалить</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    const deleteModalEl = document.getElementById('deleteModal');
    const deleteForm = document.getElementById('deleteForm');
    const deleteText = document.getElementById('deleteText');
    const deleteModal = deleteModalEl ? new bootstrap.Modal(deleteModalEl) : null;

    document.querySelectorAll('.js-delete-entry').forEach(btn=>{
        btn.addEventListener('click', ()=>{
            if(!deleteModal) return;
            deleteForm.action = btn.dataset.url;
            deleteText.textContent = `Удалить запись #${btn.dataset.id} (${btn.dataset.name}) из архива без возможности восстановления?`;
            deleteModal.show();
        });
    });
});
</script>
@endpush
@endsection
