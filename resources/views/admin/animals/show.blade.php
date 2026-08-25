@extends('admin.index')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>{{ $animal->name }}</h1>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#animalClientModal">Назначить хозяина</button>
            <a href="{{ route('admin.animals.edit', $animal) }}" class="btn btn-primary">Редактировать</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">Информация</div>
                <div class="card-body">
                    <p><strong>Категория:</strong> {{ $animal->category?->name ?: '—' }}</p>
                    @if(!empty($animal->tags))
                        <div class="mb-3"><strong>Теги:</strong>@include('admin.partials.tags-list', ['tags' => $animal->tags])</div>
                    @endif
                    @if($animal->dog_size)
                        <p><strong>Размер:</strong> {{ $animal->dog_size === 'small' ? 'мелкая собака' : 'средняя или крупная собака' }}</p>
                    @endif
                    <p><strong>Хозяин:</strong>
                        @if($animal->client)
                            <a href="{{ route('admin.clients.show', $animal->client) }}">{{ $animal->client->name }}</a>
                        @else
                            —
                        @endif
                    </p>
                    <p><strong>Описание:</strong><br>{{ $animal->description ?: '—' }}</p>
                    <p class="mb-0"><strong>Заметки:</strong><br>{{ $animal->note ?: '—' }}</p>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header">Фото</div>
                <div class="card-body">
                    @if($animal->photos->count())
                        <div class="d-flex flex-wrap gap-3">
                            @foreach($animal->photos as $photo)
                                <div>
                                    <button type="button"
                                            class="btn p-0 border-0 js-animal-photo"
                                            data-image="{{ Storage::url($photo->path) }}"
                                            data-title="{{ $animal->name }}"
                                            aria-label="Увеличить фото {{ $animal->name }}">
                                        <img src="{{ Storage::url($photo->path) }}" alt="{{ $animal->name }}" style="width:140px;height:140px;object-fit:cover;border-radius:10px;cursor:zoom-in;">
                                    </button>
                                    <form action="{{ route('admin.animals.photos.destroy', [$animal, $photo]) }}" method="POST" class="mt-2 js-delete-form" data-confirm="Удалить фото?">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger w-100">Удалить</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-muted">Фото пока нет.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header">История записей</div>
        <div class="card-body">
            @if($animal->boardings->count())
                <div class="table-responsive">
                    <table class="table align-middle boarding-history-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Услуга</th>
                                <th>Период</th>
                                <th>Хозяин</th>
                                <th>Источник</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($animal->boardings as $boarding)
                                <tr>
                                    <td>{{ $boarding->id }}</td>
                                    <td>{{ $boarding->service_type }}</td>
                                    <td>{{ $boarding->start_date->toDateString() }} — {{ $boarding->end_date->toDateString() }}</td>
                                    <td>{{ $boarding->client?->name ?: $animal->client?->name ?: '—' }}</td>
                                    <td>{{ $boarding->source ?? 'admin' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-muted">Записей пока нет.</div>
            @endif
        </div>
    </div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const existing = document.getElementById('animal-existing-client');
    const fresh = document.getElementById('animal-new-client-name');
    fresh?.addEventListener('input', () => { if (fresh.value.trim()) existing.value = ''; });
    existing?.addEventListener('change', () => { if (existing.value) fresh.value = ''; });
});
</script>
@endpush

<div class="modal fade" id="animalClientModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ route('admin.animals.client.assign', $animal) }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Хозяин питомца {{ $animal->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <label class="form-label" for="animal-existing-client">Сохранённый клиент</label>
                <select class="form-select" name="client_id" id="animal-existing-client">
                    <option value="">Выберите из базы</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" @selected($animal->client_id === $client->id)>{{ $client->name }}{{ $client->phone ? ' · '.$client->phone : '' }}</option>
                    @endforeach
                </select>

                <div class="border-top mt-3 pt-3">
                    <div class="fw-semibold mb-2">Или создать нового</div>
                    <div class="mb-3">
                        <label class="form-label" for="animal-new-client-name">Имя / ФИО</label>
                        <input class="form-control" name="new_client_name" id="animal-new-client-name" maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="animal-new-client-phone">Телефон</label>
                        <input class="form-control" name="new_client_phone" id="animal-new-client-phone" maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="animal-new-client-note">Заметка</label>
                        <textarea class="form-control" name="new_client_note" id="animal-new-client-note" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button>
                <button class="btn btn-primary">Сохранить</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="animalPhotoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-dark">
            <div class="modal-header border-0">
                <h5 class="modal-title text-white" id="animalPhotoModalTitle"></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body pt-0 text-center">
                <img id="animalPhotoModalImage" src="" alt="" class="img-fluid rounded" style="max-height:75vh;">
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalElement = document.getElementById('animalPhotoModal');
    const image = document.getElementById('animalPhotoModalImage');
    const title = document.getElementById('animalPhotoModalTitle');
    const modal = modalElement ? new bootstrap.Modal(modalElement) : null;

    document.querySelectorAll('.js-animal-photo').forEach((button) => {
        button.addEventListener('click', function () {
            if (!modal) return;
            image.src = button.dataset.image;
            image.alt = button.dataset.title;
            title.textContent = button.dataset.title;
            modal.show();
        });
    });

    modalElement?.addEventListener('hidden.bs.modal', function () {
        image.src = '';
    });
});
</script>
@endpush
@endsection
