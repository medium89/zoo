@extends('admin.index')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>{{ $animal->name }}</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.animals.edit', $animal) }}" class="btn btn-primary">Редактировать</a>
            <a href="{{ route('admin.animals.index') }}" class="btn btn-secondary">Назад</a>
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
                    <p><strong>Вид:</strong> {{ $animal->species ?: '—' }}</p>
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
                    <table class="table align-middle">
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
