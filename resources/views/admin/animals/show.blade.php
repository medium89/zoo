@extends('admin.index')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>{{ $animal->name }}</h1>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary" data-admin-popup-target="#animalClientModal">Назначить хозяина</button>
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
                            <form action="{{ route('admin.animals.client.detach', $animal) }}" method="POST" class="d-inline ms-1">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-sm btn-danger btn-icon js-unlink-trigger" title="Отвязать хозяина" aria-label="Отвязать хозяина" data-confirm="Отвязать хозяина от питомца «{{ $animal->name }}»? Карточка клиента останется в базе."><i class="fa fa-xmark"></i></button>
                            </form>
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

<div class="modal fade admin-modal admin-secondary-modal" id="animalClientModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form class="modal-content" method="POST" action="{{ route('admin.animals.client.assign', $animal) }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Хозяин питомца {{ $animal->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3 client-animal-search-field">
                    <label class="form-label" for="animal-client-search">Хозяин</label>
                    <input class="form-control"
                           id="animal-client-search"
                           autocomplete="off"
                           placeholder="Начните вводить имя"
                           data-animal-client-search
                           data-client-options='@json($clientsPayload, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'>
                    <input type="hidden" name="client_id" id="animal-existing-client">
                    <input type="hidden" name="new_client_name" id="animal-new-client-name">
                    <div class="client-animal-search-results is-hidden" aria-live="polite"></div>
                    <div class="form-text">Выберите клиента из подсказок или введите новое имя — клиент создастся и сразу станет хозяином питомца.</div>
                </div>

                <div class="border-top pt-3 animal-new-client-details">
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

<div class="modal fade admin-modal--media" id="animalPhotoModal" tabindex="-1" aria-hidden="true">
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
