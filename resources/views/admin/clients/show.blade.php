@extends('admin.index')

@section('content')
<div class="client-profile-view">
    @php
        $orders = $client->serviceOrders->sortByDesc('start_date');
        $activeOrdersCount = $orders->filter(fn ($order) => !$order->archived_at && $order->end_date?->endOfDay()->isFuture())->count();
        $monthNames = [1 => 'янв', 2 => 'фев', 3 => 'мар', 4 => 'апр', 5 => 'мая', 6 => 'июн', 7 => 'июл', 8 => 'авг', 9 => 'сен', 10 => 'окт', 11 => 'ноя', 12 => 'дек'];
        $shortDate = fn ($date) => $date ? $date->format('j').' '.$monthNames[(int) $date->format('n')] : '—';
        $wordForm = function (int $number, array $forms): string {
            $remainder100 = $number % 100;
            $remainder10 = $number % 10;
            if ($remainder100 >= 11 && $remainder100 <= 19) return $forms[2];
            if ($remainder10 === 1) return $forms[0];
            if ($remainder10 >= 2 && $remainder10 <= 4) return $forms[1];
            return $forms[2];
        };
        $capitalize = fn (string $value) => mb_strtoupper(mb_substr($value, 0, 1)).mb_substr($value, 1);
        $sourceLabels = ['telegram_bot' => 'Telegram', 'telegram' => 'Telegram', 'admin' => 'Админка', 'web' => 'Сайт'];
        $statusLabels = ['active' => 'В работе', 'planned' => 'Запланирован', 'finished' => 'Завершён', 'archived' => 'В архиве'];
    @endphp

    <div class="client-profile-view__heading">
        <h1>{{ $client->name }}</h1>
        <div class="client-profile-view__actions">
            <button type="button" class="btn btn-primary" data-admin-popup-target="#clientAnimalModal"><i class="fa fa-plus" aria-hidden="true"></i><span>Добавить питомца</span></button>
            <x-admin.actions-menu label="Действия с клиентом {{ $client->name }}"><a href="{{ route('admin.clients.edit', $client) }}" class="admin-actions-menu__item"><i class="fa fa-pen" aria-hidden="true"></i><span>Редактировать</span></a></x-admin.actions-menu>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <section class="client-profile-summary">
        <img src="{{ $client->avatarUrl() }}" alt="{{ $client->name }}" class="client-profile-summary__avatar">
        <div class="client-profile-summary__main">
            <div class="client-profile-summary__contacts">
                <div class="client-profile-contact">
                    <span class="client-profile-contact__icon"><i class="fa fa-phone" aria-hidden="true"></i></span>
                    <div><small>Телефон</small>@if($client->phone)<a href="tel:{{ preg_replace('/[^+\d]/', '', $client->phone) }}">{{ $client->phone }}</a>@else<strong class="is-empty">Не указан</strong>@endif</div>
                </div>
                <div class="client-profile-contact client-profile-contact--wide">
                    <span class="client-profile-contact__icon"><i class="fa fa-location-dot" aria-hidden="true"></i></span>
                    <div><small>Адрес</small><strong class="{{ $client->address ? '' : 'is-empty' }}">{{ $client->address ?: 'Не указан' }}</strong></div>
                </div>
            </div>
            <div class="client-profile-stats">
                <div><strong>{{ $client->animals->count() }}</strong><span>{{ $wordForm($client->animals->count(), ['питомец', 'питомца', 'питомцев']) }}</span></div>
                <div><strong>{{ $orders->count() }}</strong><span>{{ $wordForm($orders->count(), ['заказ', 'заказа', 'заказов']) }}</span></div>
                <div><strong>{{ $activeOrdersCount }}</strong><span>активных</span></div>
            </div>
            @if(!empty($client->tags))
                <div class="client-profile-summary__tags">@include('admin.partials.tags-list', ['tags' => $client->tags])</div>
            @endif
            <div class="client-profile-summary__meta"><i class="fa fa-clock" aria-hidden="true"></i> Клиент добавлен {{ $client->created_at->format('d.m.Y') }}</div>
        </div>
    </section>

    @if($client->note)
        <section class="client-profile-note">
            <div class="client-profile-section-title"><span><i class="fa fa-note-sticky" aria-hidden="true"></i> Заметка</span></div>
            <p>{{ $client->note }}</p>
        </section>
    @endif

    <section class="client-profile-section">
        <div class="client-profile-section-title">
            <span><i class="fa fa-paw" aria-hidden="true"></i> Питомцы</span>
            <b>{{ $client->animals->count() }}</b>
        </div>
        @if($client->animals->isNotEmpty())
            <div class="client-profile-pets">
                @foreach($client->animals as $animal)
                    @php
                        $animalRecords = $animal->serviceOrderAnimals->count() ?: $animal->boardings->count();
                        $animalPhoto = $animal->photos->first();
                    @endphp
                    <article class="client-profile-pet">
                        <a href="{{ route('admin.animals.show', $animal) }}" class="client-profile-pet__avatar" aria-label="Открыть карточку питомца {{ $animal->name }}">
                            @if($animalPhoto)
                                <img src="{{ Storage::url($animalPhoto->path) }}" alt="{{ $animal->name }}">
                            @else
                                <i class="fa fa-paw" aria-hidden="true"></i>
                            @endif
                        </a>
                        <div class="client-profile-pet__copy">
                            <a href="{{ route('admin.animals.show', $animal) }}">{{ $animal->name }}</a>
                            <span>{{ $animal->category?->name ?: 'Вид не указан' }}</span>
                            <small><i class="fa fa-calendar-check" aria-hidden="true"></i> {{ $animalRecords }} {{ $wordForm($animalRecords, ['запись', 'записи', 'записей']) }}</small>
                        </div>
                        <x-admin.actions-menu label="Действия с питомцем {{ $animal->name }}">
                            <a href="{{ route('admin.animals.show', $animal) }}" class="admin-actions-menu__item"><i class="fa fa-eye" aria-hidden="true"></i><span>Просмотреть</span></a>
                            <a href="{{ route('admin.animals.edit', $animal) }}" class="admin-actions-menu__item"><i class="fa fa-pen" aria-hidden="true"></i><span>Редактировать</span></a>
                            <form action="{{ route('admin.clients.animals.detach', [$client, $animal]) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="admin-actions-menu__item admin-actions-menu__item--danger js-unlink-trigger" data-confirm="Отвязать питомца «{{ $animal->name }}» от клиента? Питомец останется в базе."><i class="fa fa-link-slash" aria-hidden="true"></i><span>Отвязать питомца</span></button>
                            </form>
                        </x-admin.actions-menu>
                    </article>
                @endforeach
            </div>
        @else
            <div class="client-profile-empty"><i class="fa fa-paw" aria-hidden="true"></i><span>У клиента пока нет питомцев</span><button type="button" data-admin-popup-target="#clientAnimalModal">Добавить первого</button></div>
        @endif
    </section>

    <section class="client-profile-section">
        <div class="client-profile-section-title">
            <span><i class="fa fa-briefcase" aria-hidden="true"></i> Последние заказы</span>
            <b>{{ $orders->count() }}</b>
        </div>
        @if($orders->isNotEmpty())
            <div class="client-profile-orders">
                @foreach($orders->take(6) as $order)
                    @php
                        $orderPets = $order->animals->map(fn ($position) => $position->animal?->name ?: $position->label)->filter()->join(', ');
                        $orderServices = $order->animals->flatMap(fn ($position) => $position->services)->pluck('service_type')->filter()->unique()->map($capitalize)->join(', ');
                        $status = $order->archived_at ? 'archived' : ($order->status ?: ($order->end_date?->isPast() ? 'finished' : 'planned'));
                    @endphp
                    <article class="client-profile-order">
                        <span class="client-profile-order__icon"><i class="fa fa-calendar-day" aria-hidden="true"></i></span>
                        <div class="client-profile-order__main">
                            <strong>{{ $orderPets ?: 'Питомец не указан' }}</strong>
                            <span>{{ $orderServices ?: 'Услуга не указана' }}</span>
                        </div>
                        <div class="client-profile-order__period"><strong>{{ $shortDate($order->start_date) }} — {{ $shortDate($order->end_date) }}</strong><span>{{ $order->start_date?->format('Y') }}</span></div>
                        <span class="client-profile-order__status client-profile-order__status--{{ $status }}">{{ $statusLabels[$status] ?? 'Заказ' }}</span>
                        <small class="client-profile-order__source">{{ $sourceLabels[$order->source] ?? 'Админка' }}</small>
                    </article>
                @endforeach
            </div>
        @else
            <div class="client-profile-empty client-profile-empty--orders"><i class="fa fa-calendar-xmark" aria-hidden="true"></i><span>У клиента пока нет заказов</span></div>
        @endif
    </section>
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
