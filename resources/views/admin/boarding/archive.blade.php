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
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Кличка</th>
                                <th>Описание</th>
                                <th>Тип услуги</th>
                                <th>Период</th>
                                <th>Архивировано</th>
                                <th class="text-end">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($archived as $row)
                                <tr>
                                    <td>{{ $row->id }}</td>
                                    <td>{{ $row->name }}</td>
                                    <td>{{ $row->description }}</td>
                                    <td>{{ $row->service_type }}</td>
                                    <td>{{ $row->start_date->toDateString() }} — {{ $row->end_date->toDateString() }}</td>
                                    <td>{{ optional($row->archived_at)->format('d.m.Y H:i') }}</td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end flex-wrap gap-1">
                                            <form action="{{ route('admin.boarding.restore', $row) }}" method="POST">
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
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
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
