@extends('admin.index')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>{{ $client->name }}</h1>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary" data-admin-popup-target="#clientAnimalModal">Добавить питомца</button>
            <x-admin.actions-menu label="Действия с клиентом {{ $client->name }}"><a href="{{ route('admin.clients.edit', $client) }}" class="admin-actions-menu__item"><i class="fa fa-pen" aria-hidden="true"></i><span>Редактировать</span></a></x-admin.actions-menu>
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
                    <img src="{{ $client->avatarUrl() }}" alt="{{ $client->name }}" class="client-profile-avatar mb-3">
                    <p><strong>Телефон:</strong> {{ $client->phone ?: '—' }}</p>
                    <p><strong>Адрес:</strong> {{ $client->address ?: '—' }}</p>
                    @if(!empty($client->tags))
                        <div class="mb-3"><strong>Теги:</strong>@include('admin.partials.tags-list', ['tags' => $client->tags])</div>
                    @endif
                    <p class="mb-0"><strong>Заметка:</strong><br>{{ $client->note ?: '—' }}</p>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header">Питомцы</div>
                <div class="card-body">
                    @forelse($client->animals as $animal)
                        <div class="border rounded p-3 mb-2">
                            <div class="d-flex justify-content-between gap-2">
                                <div>
                                    <a href="{{ route('admin.animals.show', $animal) }}" class="fw-bold">{{ $animal->name }}</a>
                                    <div class="text-muted small">{{ $animal->category?->name ?: 'категория не указана' }} · записей: {{ $animal->boardings->count() }}</div>
                                </div>
                                <div class="d-flex align-items-start gap-2">
                                    @if($animal->photos->first())
                                        <img src="{{ Storage::url($animal->photos->first()->path) }}" alt="{{ $animal->name }}" style="width:54px;height:54px;object-fit:cover;border-radius:8px;">
                                    @endif
                                    <x-admin.actions-menu label="Действия с питомцем {{ $animal->name }}"><form action="{{ route('admin.clients.animals.detach', [$client, $animal]) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="admin-actions-menu__item admin-actions-menu__item--danger js-unlink-trigger" data-confirm="Отвязать питомца «{{ $animal->name }}» от клиента? Питомец останется в базе."><i class="fa fa-link-slash" aria-hidden="true"></i><span>Отвязать питомца</span></button>
                                    </form>
                                    </x-admin.actions-menu>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-muted">У клиента пока нет питомцев.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header">История записей</div>
        <div class="card-body">
            @if($client->boardings->count())
                <div class="table-responsive">
                    <table class="table align-middle boarding-history-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Питомец</th>
                                <th>Услуга</th>
                                <th>Период</th>
                                <th>Источник</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($client->boardings->sortByDesc('start_date') as $boarding)
                                <tr>
                                    <td>{{ $boarding->id }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            @if($boarding->animal?->photos->first())
                                                <img src="{{ Storage::url($boarding->animal->photos->first()->path) }}" alt="{{ $boarding->animal->name }}" style="width:38px;height:38px;object-fit:cover;border-radius:8px;">
                                            @endif
                                            @if($boarding->animal)
                                                <a href="{{ route('admin.animals.show', $boarding->animal) }}">{{ $boarding->animal->name }}</a>
                                            @else
                                                {{ $boarding->name }}
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ $boarding->service_type }}</td>
                                    <td>{{ $boarding->start_date->toDateString() }} — {{ $boarding->end_date->toDateString() }}</td>
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
<div class="modal fade admin-modal admin-secondary-modal" id="clientAnimalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form class="modal-content" method="POST" action="{{ route('admin.clients.animals.attach', $client) }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Питомец клиента {{ $client->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3 client-animal-search-field">
                    <label class="form-label" for="client-animal-search">Питомец</label>
                    <input class="form-control"
                           id="client-animal-search"
                           autocomplete="off"
                           placeholder="Начните вводить кличку"
                           data-client-animal-search
                           data-animal-options='@json($availableAnimalsPayload, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'>
                    <input type="hidden" name="animal_id" id="client-existing-animal">
                    <input type="hidden" name="new_animal_name" id="client-new-animal">
                    <div class="client-animal-search-results is-hidden" aria-live="polite"></div>
                    <div class="form-text">Выберите питомца из подсказок или введите новую кличку — он создастся и сразу привяжется.</div>
                </div>

                <div class="border-top pt-3 client-new-animal-details">
                    <div class="mb-3">
                        <label class="form-label" for="client-new-animal-category">Категория</label>
                        <select class="form-select" name="category_id" id="client-new-animal-category">
                            <option value="">Не указана</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="client-new-animal-note">Заметка</label>
                        <textarea class="form-control" name="note" id="client-new-animal-note" rows="2"></textarea>
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
@endsection

@push('styles')
<style>.client-profile-avatar{width:96px;height:96px;object-fit:cover;border-radius:18px;background:#eaf3ff}</style>
@endpush
