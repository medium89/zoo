@extends('admin.index')

@section('content')
@php
    $positions = $animal->serviceOrderAnimals->filter(fn ($position) => $position->serviceOrder)->sortByDesc(fn ($position) => $position->serviceOrder->start_date);
    $activeOrdersCount = $positions->filter(fn ($position) => !$position->serviceOrder->archived_at && $position->serviceOrder->end_date?->endOfDay()->isFuture())->count();
    $months = [1 => 'янв', 2 => 'фев', 3 => 'мар', 4 => 'апр', 5 => 'мая', 6 => 'июн', 7 => 'июл', 8 => 'авг', 9 => 'сен', 10 => 'окт', 11 => 'ноя', 12 => 'дек'];
    $shortDate = fn ($date) => $date ? $date->format('j').' '.$months[(int) $date->format('n')] : '—';
    $sourceLabels = ['telegram_bot' => 'Telegram', 'telegram' => 'Telegram', 'admin' => 'Админка', 'web' => 'Сайт'];
    $statusLabels = ['active' => 'В работе', 'planned' => 'Запланирован', 'finished' => 'Завершён', 'archived' => 'В архиве'];
    $categoryName = mb_strtolower((string) $animal->category?->name);
    $imageKey = str_contains($categoryName, 'кош') ? 'cat' : (str_contains($categoryName, 'соб') ? 'dog' : (str_contains($categoryName, 'грыз') ? 'rodent' : (str_contains($categoryName, 'птиц') ? 'bird' : (str_contains($categoryName, 'рыб') ? 'fish' : (str_contains($categoryName, 'репт') ? 'reptile' : (str_contains($categoryName, 'паук') ? 'spider' : (str_contains($categoryName, 'насек') ? 'insect' : 'other')))))));
    $mainPhoto = $animal->photos->first();
    $profileImage = $mainPhoto ? Storage::url($mainPhoto->path) : asset('images/animal-types/'.$imageKey.'.png');
@endphp

<div class="client-profile-view animal-profile-view">
    <div class="client-profile-view__heading">
        <h1>{{ $animal->name }}</h1>
        <div class="client-profile-view__actions">
            <button type="button" class="btn btn-primary" data-admin-popup-target="#animalClientModal"><i class="fa fa-user-plus"></i><span>{{ $animal->client ? 'Сменить хозяина' : 'Назначить хозяина' }}</span></button>
            <x-admin.actions-menu label="Действия с питомцем {{ $animal->name }}"><a href="{{ route('admin.animals.edit', $animal) }}" class="admin-actions-menu__item"><i class="fa fa-pen"></i><span>Редактировать</span></a></x-admin.actions-menu>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <section class="client-profile-summary animal-profile-summary">
        <button type="button" class="animal-profile-summary__photo js-animal-photo" data-image="{{ $profileImage }}" data-title="{{ $animal->name }}" aria-label="Увеличить фото {{ $animal->name }}"><img src="{{ $profileImage }}" alt="{{ $animal->name }}" class="client-profile-summary__avatar"></button>
        <div class="client-profile-summary__main">
            <div class="client-profile-summary__contacts">
                <div class="client-profile-contact"><span class="client-profile-contact__icon"><i class="fa fa-paw"></i></span><div><small>Вид</small><strong class="{{ $animal->category ? '' : 'is-empty' }}">{{ $animal->category?->name ?: 'Не указан' }}</strong></div></div>
                <div class="client-profile-contact client-profile-contact--wide"><span class="client-profile-contact__icon"><i class="fa fa-user"></i></span><div><small>Хозяин</small>@if($animal->client)<a href="{{ route('admin.clients.show', $animal->client) }}">{{ $animal->client->name }}{{ $animal->client->phone ? ' · '.$animal->client->phone : '' }}</a>@else<strong class="is-empty">Не назначен</strong>@endif</div></div>
            </div>
            <div class="client-profile-stats"><div><strong>{{ $positions->count() }}</strong><span>заказов</span></div><div><strong>{{ $activeOrdersCount }}</strong><span>активных</span></div><div><strong>{{ $animal->photos->count() }}</strong><span>фото</span></div></div>
            @if(!empty($animal->tags))<div class="client-profile-summary__tags">@include('admin.partials.tags-list', ['tags' => $animal->tags])</div>@endif
            <div class="client-profile-summary__meta"><i class="fa fa-clock"></i> Питомец добавлен {{ $animal->created_at->format('d.m.Y') }}@if($animal->dog_size) · {{ $animal->dog_size === 'small' ? 'мелкая собака' : 'средняя или крупная собака' }}@endif</div>
        </div>
    </section>

    @if($animal->client)
        <section class="client-profile-section">
            <div class="client-profile-section-title"><span><i class="fa fa-user"></i> Хозяин</span></div>
            <div class="animal-profile-owner">
                <img src="{{ $animal->client->avatarUrl() }}" alt="{{ $animal->client->name }}">
                <div><a href="{{ route('admin.clients.show', $animal->client) }}">{{ $animal->client->name }}</a><span>{{ $animal->client->phone ?: 'Телефон не указан' }}</span></div>
                <x-admin.actions-menu label="Действия с хозяином"><a href="{{ route('admin.clients.show', $animal->client) }}" class="admin-actions-menu__item"><i class="fa fa-eye"></i><span>Просмотреть</span></a><form action="{{ route('admin.animals.client.detach', $animal) }}" method="POST">@csrf @method('DELETE')<button type="button" class="admin-actions-menu__item admin-actions-menu__item--danger js-unlink-trigger" data-confirm="Отвязать хозяина от питомца «{{ $animal->name }}»? Карточка клиента останется в базе."><i class="fa fa-link-slash"></i><span>Отвязать хозяина</span></button></form></x-admin.actions-menu>
            </div>
        </section>
    @endif

    @if($animal->description || $animal->note)
        <section class="animal-profile-notes">
            @if($animal->description)<div class="client-profile-note"><div class="client-profile-section-title"><span><i class="fa fa-circle-info"></i> О питомце</span></div><p>{{ $animal->description }}</p></div>@endif
            @if($animal->note)<div class="client-profile-note"><div class="client-profile-section-title"><span><i class="fa fa-note-sticky"></i> Заметка</span></div><p>{{ $animal->note }}</p></div>@endif
        </section>
    @endif

    <section class="client-profile-section">
        <div class="client-profile-section-title"><span><i class="fa fa-images"></i> Фотографии</span><b>{{ $animal->photos->count() }}</b></div>
        @if($animal->photos->isNotEmpty())
            <div class="animal-profile-gallery">
                @foreach($animal->photos as $photo)
                    <article><button type="button" class="js-animal-photo" data-image="{{ Storage::url($photo->path) }}" data-title="{{ $animal->name }}"><img src="{{ Storage::url($photo->path) }}" alt="{{ $animal->name }}"></button><x-admin.actions-menu label="Действия с фото"><form action="{{ route('admin.animals.photos.destroy', [$animal, $photo]) }}" method="POST" class="js-delete-form" data-confirm="Удалить фото?">@csrf @method('DELETE')<button class="admin-actions-menu__item admin-actions-menu__item--danger" type="submit"><i class="fa fa-trash"></i><span>Удалить фото</span></button></form></x-admin.actions-menu></article>
                @endforeach
            </div>
        @else
            <div class="client-profile-empty"><i class="fa fa-camera"></i><span>Фотографий пока нет</span><a href="{{ route('admin.animals.edit', $animal) }}">Добавить фото</a></div>
        @endif
    </section>

    <section class="client-profile-section">
        <div class="client-profile-section-title"><span><i class="fa fa-briefcase"></i> Последние заказы</span><b>{{ $positions->count() }}</b></div>
        @if($positions->isNotEmpty())
            <div class="client-profile-orders">
                @foreach($positions->take(6) as $position)
                    @php
                        $order = $position->serviceOrder;
                        $services = $position->services->pluck('service_type')->filter()->unique()->map(fn ($value) => mb_strtoupper(mb_substr($value, 0, 1)).mb_substr($value, 1))->join(', ');
                        $status = $order->archived_at ? 'archived' : ($order->status ?: ($order->end_date?->isPast() ? 'finished' : 'planned'));
                    @endphp
                    <article class="client-profile-order"><span class="client-profile-order__icon"><i class="fa fa-calendar-day"></i></span><div class="client-profile-order__main"><strong>{{ $services ?: 'Услуга не указана' }}</strong><span>{{ $position->quantity > 1 ? $position->quantity.' питомца' : ($animal->client?->name ?: 'Клиент не указан') }}</span></div><div class="client-profile-order__period"><strong>{{ $shortDate($order->start_date) }} — {{ $shortDate($order->end_date) }}</strong><span>{{ $order->start_date?->format('Y') }}</span></div><span class="client-profile-order__status client-profile-order__status--{{ $status }}">{{ $statusLabels[$status] ?? 'Заказ' }}</span><small class="client-profile-order__source">{{ $sourceLabels[$order->source] ?? 'Админка' }}</small></article>
                @endforeach
            </div>
        @else
            <div class="client-profile-empty client-profile-empty--orders"><i class="fa fa-calendar-xmark"></i><span>У питомца пока нет заказов</span></div>
        @endif
    </section>
</div>

<div class="modal fade admin-modal admin-secondary-modal" id="animalClientModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"><form class="modal-content" method="POST" action="{{ route('admin.animals.client.assign', $animal) }}">@csrf<div class="modal-header"><h5 class="modal-title">Хозяин питомца {{ $animal->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button></div><div class="modal-body"><div class="mb-3 client-animal-search-field"><label class="form-label" for="animal-client-search">Хозяин</label><input class="form-control" id="animal-client-search" autocomplete="off" placeholder="Начните вводить имя" data-animal-client-search data-client-options='@json($clientsPayload, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'><input type="hidden" name="client_id" id="animal-existing-client"><input type="hidden" name="new_client_name" id="animal-new-client-name"><div class="client-animal-search-results is-hidden" aria-live="polite"></div><div class="form-text">Выберите клиента или введите новое имя — клиент создастся и станет хозяином.</div></div><div class="border-top pt-3 animal-new-client-details"><div class="mb-3"><label class="form-label" for="animal-new-client-phone">Телефон</label><input class="form-control" name="new_client_phone" id="animal-new-client-phone"></div><div><label class="form-label" for="animal-new-client-note">Заметка</label><textarea class="form-control" name="new_client_note" id="animal-new-client-note" rows="2"></textarea></div></div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button><button class="btn btn-primary">Сохранить</button></div></form></div></div>

<div class="modal fade admin-modal--media" id="animalPhotoModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content bg-dark"><div class="modal-header border-0"><h5 class="modal-title text-white" id="animalPhotoModalTitle"></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button></div><div class="modal-body pt-0 text-center"><img id="animalPhotoModalImage" src="" alt="" class="img-fluid rounded" style="max-height:75vh;"></div></div></div></div>
@endsection
