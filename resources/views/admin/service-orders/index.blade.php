@extends('admin.index')

@section('content')
<section class="orders-workspace" aria-labelledby="orders-workspace-title">
<header class="orders-head">
        <h1 id="orders-workspace-title" class="visually-hidden">Заказы и работа</h1>
        <div class="orders-create-region orders-head__actions" aria-label="Действия с заказами"><button class="btn btn-primary orders-create js-new-service-order d-none"><i class="fa fa-plus" aria-hidden="true"></i><span>Новый заказ</span></button><a class="btn btn-outline-secondary" href="{{ route('admin.service-orders.archive.index') }}"><i class="fa fa-box-archive" aria-hidden="true"></i><span>Архив заказов</span></a></div>
 </header>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(isset($errors) && $errors->any())<div class="alert alert-danger"><strong>Заказ не сохранён.</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <section class="orders-list-shell">
        <form class="orders-filters" method="GET" action="{{ route('admin.service-orders.index') }}">
            <label class="orders-filter orders-filter--search"><i class="fa fa-magnifying-glass"></i><input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Поиск по клиенту или питомцу"></label>
            @php
                $statusValue = $filters['status'] ?? '';
            @endphp
            <div class="orders-filter orders-filter--select orders-custom-select" data-filter-select>
                <span>Статус</span><input type="hidden" name="status" value="{{ $statusValue }}">
                <button class="orders-custom-select__toggle" type="button" aria-haspopup="listbox" aria-expanded="false"><span>{{ ['', 'active' => 'В работе', 'planned' => 'Запланирован', 'finished' => 'Прошёл'][$statusValue] ?? 'Все' }}</span><i class="fa fa-chevron-down" aria-hidden="true"></i></button>
                <div class="orders-custom-select__menu is-hidden" role="listbox" aria-label="Статус">
                    <button type="button" role="option" aria-selected="{{ $statusValue === '' ? 'true' : 'false' }}" data-value="">Все</button>
                    <button type="button" role="option" aria-selected="{{ $statusValue === 'active' ? 'true' : 'false' }}" data-value="active">В работе</button>
                    <button type="button" role="option" aria-selected="{{ $statusValue === 'planned' ? 'true' : 'false' }}" data-value="planned">Запланирован</button>
                    <button type="button" role="option" aria-selected="{{ $statusValue === 'finished' ? 'true' : 'false' }}" data-value="finished">Прошёл</button>
                </div>
            </div>
            @php
                $serviceValue = $filters['service'] ?? '';
            @endphp
            <div class="orders-filter orders-filter--select orders-custom-select" data-filter-select>
                <span>Услуга</span><input type="hidden" name="service" value="{{ $serviceValue }}">
                <button class="orders-custom-select__toggle" type="button" aria-haspopup="listbox" aria-expanded="false"><span>{{ ['', 'передержка' => 'Передержка', 'выгул' => 'Выгул', 'уход' => 'Уход'][$serviceValue] ?? 'Все' }}</span><i class="fa fa-chevron-down" aria-hidden="true"></i></button>
                <div class="orders-custom-select__menu is-hidden" role="listbox" aria-label="Услуга">
                    <button type="button" role="option" aria-selected="{{ $serviceValue === '' ? 'true' : 'false' }}" data-value="">Все</button>
                    <button type="button" role="option" aria-selected="{{ $serviceValue === 'передержка' ? 'true' : 'false' }}" data-value="передержка">Передержка</button>
                    <button type="button" role="option" aria-selected="{{ $serviceValue === 'выгул' ? 'true' : 'false' }}" data-value="выгул">Выгул</button>
                    <button type="button" role="option" aria-selected="{{ $serviceValue === 'уход' ? 'true' : 'false' }}" data-value="уход">Уход</button>
                </div>
            </div>
            <label class="orders-filter orders-filter--date"><i class="fa fa-calendar-days"></i><span>Период:</span><input name="from" type="date" value="{{ $filters['from'] ?? '' }}" aria-label="С"><span>—</span><input name="to" type="date" value="{{ $filters['to'] ?? '' }}" aria-label="По"></label>
            <a class="btn btn-outline-secondary orders-filter-reset" href="{{ route('admin.service-orders.index') }}"><i class="fa fa-arrow-rotate-left" aria-hidden="true"></i><span>Сбросить</span></a>
            <button class="visually-hidden" type="submit">Применить</button>
        </form>
        <section class="orders-table" aria-label="Активные заказы">
            <header class="orders-table__head"><span>Клиент</span><span>Питомцы</span><span>Услуги</span><span>Период</span><span>Стоимость</span><span>Статус</span><span class="visually-hidden">Действия</span></header>
    @forelse($orders as $order)
        @php
            $positions = $order->animals;
            $orderPayload = ['id' => $order->id, 'client_id' => $order->client_id, 'start' => $order->start_date->toDateString(), 'end' => $order->end_date->toDateString(), 'address' => $order->address, 'note' => $order->note,
                'animals' => $positions->map(fn ($position) => ['animal_id' => $position->animal_id, 'name' => $position->animal?->name, 'label' => $position->label, 'category_id' => $position->category_id, 'quantity' => $position->quantity, 'note' => $position->note, 'services' => $position->services->map(fn ($service) => ['service_type' => $service->service_type, 'units_per_day' => $service->units_per_day, 'unit_price' => $service->unit_price])->values()])->values()];
            $status = $order->end_date->lessThan(today()) ? ['Прошёл', 'is-finished'] : ($order->start_date->isFuture() ? ['Запланирован', 'is-planned'] : ['В работе', 'is-active']);
            $orderDays = $order->start_date->diffInDays($order->end_date) + 1;
            $orderTotal = $order->daily_price * $orderDays;
        @endphp
        <article class="orders-table__row">
            <div class="orders-cell orders-cell--client">@if($order->client)<img class="orders-client-avatar" src="{{ $order->client->avatarUrl() ?: asset('images/client-placeholder.svg') }}" alt="{{ $order->client->name }}">@else<span class="orders-client-avatar orders-client-avatar--empty" aria-hidden="true"><i class="fa fa-user"></i></span>@endif<div><strong>{{ $order->client?->name ?: 'Клиент не указан' }}</strong>@if($order->client?->phone)<small>{{ $order->client->phone }}</small>@endif</div></div>
            <div class="orders-cell orders-cell--animals">
                @foreach($positions as $position)
                    @php
                        $animalName = $position->animal?->name ?: $position->label ?: 'Без клички';
                        $categoryName = $position->animal?->category?->name ?: $position->category?->name ?: 'Другие';
                        $categoryImage = match(mb_strtolower($categoryName)) { 'кошки' => 'cat', 'собаки' => 'dog', 'грызуны' => 'rodent', 'птицы' => 'bird', 'рептилии' => 'reptile', 'рыбки' => 'fish', 'насекомые' => 'insect', 'пауки' => 'spider', default => 'other' };
                    @endphp
                    <div class="orders-pet"><span class="orders-pet__image">@if($position->animal?->photos->first())<img src="{{ Storage::url($position->animal->photos->first()->path) }}" alt="{{ $animalName }}">@else<img src="{{ asset('images/animal-types/'.$categoryImage.'.png') }}" alt="{{ $categoryName }}">@endif</span><span><strong>{{ $position->quantity > 1 ? '×'.$position->quantity.' ' : '' }}{{ $animalName }}</strong><small>{{ mb_strtolower($categoryName) }}</small></span></div>
                @endforeach
            </div>
            <div class="orders-cell orders-cell--services">@foreach($positions as $position) @foreach($position->services as $service)<span class="order-service-chip"><b>{{ ucfirst($service->service_type) }}</b>@if($service->service_type !== 'передержка')<small>{{ $service->units_per_day }} раз в день</small>@endif</span>@endforeach @endforeach</div>
            <div class="orders-cell orders-cell--period">{{ $order->start_date->locale('ru')->translatedFormat('j M') }} <span>—</span> {{ $order->end_date->locale('ru')->translatedFormat('j M') }}</div>
            <div class="orders-cell orders-cell--price"><strong>{{ number_format($orderTotal, 0, '.', ' ') }} ₽</strong><small>{{ $orderDays }} {{ trans_choice('день|дня|дней', $orderDays) }}</small></div>
            <div class="orders-cell"><span class="order-status {{ $status[1] }}">{{ $status[0] }}</span></div>
            <div class="orders-cell orders-cell--actions"><x-admin.actions-menu label="Действия с заказом"><button class="admin-actions-menu__item js-edit-service-order" type="button" data-order='@json($orderPayload)'><i class="fa fa-pen" aria-hidden="true"></i><span>Редактировать</span></button><form method="POST" action="{{ route('admin.service-orders.archive', $order) }}">@csrf<button class="admin-actions-menu__item" type="submit"><i class="fa fa-box-archive" aria-hidden="true"></i><span>В архив</span></button></form></x-admin.actions-menu></div>
        </article>
    @empty
        @php($hasOrderFilters = collect($filters)->except(['per_page', 'page'])->filter(fn ($value) => filled($value))->isNotEmpty())
        <div class="orders-empty"><i class="fa fa-clipboard-list" aria-hidden="true"></i><h2>{{ $hasOrderFilters ? 'Нет заказов по этим фильтрам' : 'Заказов пока нет' }}</h2><p>{{ $hasOrderFilters ? 'Попробуйте изменить условия поиска или сбросить их.' : 'Добавьте первый заказ с помощью кнопки «+».' }}</p>@if($hasOrderFilters)<a class="btn btn-outline-secondary" href="{{ route('admin.service-orders.index') }}">Сбросить фильтры</a>@endif</div>
        @endforelse
        </section>
    </section>
        @if($orders->total() > 0)
            <footer class="orders-list-footer" aria-label="Навигация по заказам">
                <span>Показано {{ $orders->firstItem() }}–{{ $orders->lastItem() }} из {{ $orders->total() }} {{ trans_choice('заказа|заказов|заказов', $orders->total()) }}</span>
                <form method="GET" action="{{ route('admin.service-orders.index') }}">
                    @foreach(request()->except(['per_page', 'page']) as $key => $value)
                        @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                    @endforeach
                    <label for="ordersPerPage">На странице</label>
                    <select id="ordersPerPage" name="per_page" class="form-select" onchange="this.form.submit()">
                        @foreach([10, 25, 50, 100] as $option)<option value="{{ $option }}" @selected((int) request('per_page', 25) === $option)>{{ $option }}</option>@endforeach
                    </select>
                </form>
                <div class="orders-list-footer__pagination">{{ $orders->onEachSide(1)->links('pagination::bootstrap-4') }}</div>
            </footer>
        @endif
</section>
<x-admin.fab label="Новый заказ" target=".js-new-service-order" />
<div class="modal fade admin-modal" id="serviceOrderModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable"><form method="POST" class="modal-content order-modal" id="serviceOrderForm">@csrf<input type="hidden" name="_method" id="orderMethod" value="POST">
    <div class="modal-header order-modal__header"><h5 class="modal-title" id="orderModalTitle"><i class="fa fa-pen" id="orderModalIcon" aria-hidden="true"></i><span id="orderModalTitleText">Новый заказ</span></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button></div>
    <div class="modal-body order-modal__body"><section class="order-modal__section order-details"><div class="order-client-summary" id="orderClientSummary"><span class="order-client-summary__avatar"><i class="fa fa-user"></i></span><strong id="orderClientSummaryName">Клиент не выбран</strong><span class="order-client-summary__period"><i class="fa fa-calendar"></i> <span id="orderClientSummaryPeriod">Укажите период</span></span></div><div class="order-details__grid"><div class="order-client"><label class="form-label" for="orderClient">Клиент</label><div class="order-client__controls"><select class="form-select" name="client_id" id="orderClient"><option value="">Выберите клиента</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach</select><button class="order-client__new" type="button" id="toggleQuickClient"><i class="fa fa-plus" aria-hidden="true"></i><span>Новый клиент</span></button></div><div class="quick-client__menu d-none" id="quickClientFields"><div class="small text-muted mb-2">Новый клиент будет добавлен при сохранении заказа.</div><label class="form-label">Имя<input class="form-control" name="new_client[name]" id="newClientName" autocomplete="name"></label><label class="form-label mt-2">Телефон<input class="form-control" name="new_client[phone]" id="newClientPhone" autocomplete="tel"></label><label class="form-label mt-2 mb-0">Комментарий<textarea class="form-control" name="new_client[note]" id="newClientNote" rows="2"></textarea></label></div></div><div class="order-dates"><label class="form-label">Начало<input class="form-control" type="date" name="start_date" id="orderStart" required></label><label class="form-label">Окончание<input class="form-control" type="date" name="end_date" id="orderEnd" required></label><label class="form-label order-dates__address">Адрес<input class="form-control" name="address" id="orderAddress" placeholder="Город, улица, дом"></label></div></div></section>
        <section class="order-modal__section order-pets"><div class="order-editor-head"><h6>Питомцы и услуги</h6></div><div id="orderAnimals" class="order-editor-list"></div><div class="order-add-pet"><button type="button" class="btn btn-outline-primary" id="addOrderAnimal"><i class="fa fa-plus"></i> Добавить питомца</button><div class="add-animal-popover is-hidden" id="addAnimalPopover"><label class="form-label">Питомец<input class="form-control" id="addAnimalQuery" autocomplete="off" placeholder="Начните вводить кличку"><div class="add-animal-results is-hidden" id="addAnimalResults"></div></label><button class="btn btn-primary btn-sm w-100 mt-3" type="button" id="confirmAddAnimal">Добавить</button></div></div></section>
        <section class="order-comment"><label class="form-label" for="orderNote">Комментарий к заказу</label><textarea class="form-control" name="note" id="orderNote" rows="2" placeholder="Введите комментарий…"></textarea></section><div class="order-summary" aria-live="polite"><span><i class="fa fa-calendar"></i><b id="orderSummaryDays">—</b> дней</span><span><i class="fa fa-paw"></i><b id="orderSummaryPets">0</b> питомцев</span><span><i class="fa fa-list"></i><b id="orderSummaryServices">0</b> услуг</span><strong id="orderSummaryTotal">0 ₽</strong></div>
    </div><div class="modal-footer order-modal__footer"><button type="submit" class="btn btn-outline-secondary order-modal__delete d-none" id="orderArchive" form="orderArchiveForm"><i class="fa fa-box-archive"></i> В архив</button><span class="flex-grow-1"></span><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button><button class="btn btn-success">Сохранить заказ</button></div>
</form></div></div><form method="POST" id="orderArchiveForm" class="d-none">@csrf</form>
<div class="modal fade admin-modal" id="petCardModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="petCardModalTitle">Питомец</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body p-0"><iframe id="petCardModalFrame" title="Карточка питомца" style="width:100%;height:70vh;border:0"></iframe></div></div></div></div>

@push('styles')<style>
/* List styles are isolated from the order editor below. */
.orders-workspace .orders-list-shell{position:relative;overflow:visible}.orders-workspace .orders-filters{display:grid;align-items:center}.orders-workspace .orders-filters>*{min-width:0}.orders-workspace .orders-filter{position:relative;display:flex;align-items:center;gap:8px;padding:0 12px;border:1px solid #dce4ed;color:#6c7a8a;white-space:nowrap;transition:border-color .16s ease,box-shadow .16s ease}.orders-workspace .orders-filter:focus-within{border-color:#86b7ec;box-shadow:0 0 0 3px rgba(51,125,211,.12)}.orders-workspace .orders-filter>span{font-weight:600!important;color:#718093}.orders-workspace .orders-filter input{min-width:0;flex:1;border:0;outline:0;background:transparent;color:#35475a;font:inherit;font-weight:600!important}.orders-workspace .orders-filter--select{gap:10px;padding-right:7px}.orders-workspace .orders-custom-select__toggle{display:flex;min-width:0;flex:1;align-items:center;justify-content:space-between;gap:10px;height:34px;padding:0 4px 0 0;border:0;background:transparent;color:#35475a;font:inherit;font-weight:600!important;text-align:left;cursor:pointer}.orders-workspace .orders-custom-select__toggle>span{overflow:hidden;text-overflow:ellipsis}.orders-workspace .orders-custom-select__toggle i{color:#8090a1;font-size:.7rem;transition:transform .16s ease}.orders-workspace .orders-custom-select.is-open .orders-custom-select__toggle i{transform:rotate(180deg)}.orders-workspace .orders-custom-select__menu{position:absolute;z-index:1095;top:calc(100% + 7px);right:0;left:0;display:grid;gap:3px;min-width:174px;padding:6px;background:#fff;border:1px solid #dce4ed;border-radius:10px;box-shadow:0 14px 30px rgba(28,43,61,.16)}.orders-workspace .orders-custom-select__menu.is-hidden{display:none}.orders-workspace .orders-custom-select__menu button{display:flex;align-items:center;width:100%;min-height:34px;padding:7px 9px;border:0;border-radius:6px;background:transparent;color:#45576b;font:inherit;font-size:.81rem;font-weight:600;text-align:left;cursor:pointer}.orders-workspace .orders-custom-select__menu button:hover,.orders-workspace .orders-custom-select__menu button:focus-visible{outline:0;background:#eef5fd;color:#1763b7}.orders-workspace .orders-custom-select__menu button[aria-selected="true"]{background:#e8f2ff;color:#1763b7}.orders-workspace .orders-table__head,.orders-workspace .orders-table__row{display:grid;align-items:center}.orders-workspace .orders-cell{min-width:0;color:#3e4d5e}.orders-workspace .orders-cell--client{display:flex;align-items:center}.orders-workspace .orders-client-avatar{display:grid;place-items:center;border-radius:50%;object-fit:cover}.orders-workspace .orders-cell--animals{display:grid}.orders-workspace .orders-pet{display:flex;align-items:center;min-width:0}.orders-workspace .orders-pet__image{display:grid;place-items:center;overflow:hidden;border-radius:50%}.orders-workspace .orders-pet__image img{width:100%;height:100%;object-fit:cover}.orders-workspace .orders-cell--period{white-space:nowrap}.orders-workspace .orders-cell--period span{color:#9aa5b2}.orders-workspace .orders-cell--actions{justify-self:end}.orders-workspace .order-actions-menu{position:relative}.orders-workspace .order-actions-menu__toggle{padding:0;border:0;color:#667586;text-decoration:none;display:grid;place-items:center}.orders-workspace .order-actions-menu__popup{position:absolute;right:0;padding:6px;background:#fff;border:1px solid #dce4ed;box-shadow:0 12px 28px rgba(28,43,61,.16)}.orders-workspace .order-actions-menu__popup.is-hidden{display:none}.orders-workspace .order-actions-menu__popup form{margin:0}.orders-workspace .order-actions-menu__popup button{display:flex;width:100%;align-items:center;gap:8px;padding:8px 9px;border:0;background:transparent;color:#48596c;text-align:left}.orders-workspace .order-actions-menu__popup button:hover{background:#f1f6fb;color:#1763b7}.orders-workspace .order-actions-menu__item span{display:inline!important;visibility:visible!important;opacity:1!important;text-indent:0!important;font:inherit!important;color:currentColor!important;white-space:nowrap}.orders-workspace .order-actions-menu__item i{width:16px;flex:0 0 16px;text-align:center}.orders-workspace .orders-empty{text-align:center;color:#687586}.orders-workspace .orders-empty i{font-size:2rem;color:#9aa9bc}.orders-workspace .orders-empty h2{margin:12px 0 4px;font-size:1.2rem}.orders-workspace .orders-empty p{margin:0 0 16px}.orders-workspace .is-active{background:#e4f8eb;color:#16713b}.orders-workspace .is-planned{background:#e8f1ff;color:#2365be}.orders-workspace .is-finished{background:#eef1f4;color:#637083}
/* Рабочее пространство заказов: список не наследует стили редактора модального окна. */
.orders-workspace{min-width:0;color:#334155}.orders-workspace .orders-head{align-items:center;margin:0 0 22px}.orders-workspace .orders-head h1{font-size:clamp(1.75rem,3vw,2.2rem);line-height:1.1;letter-spacing:-.035em}.orders-workspace .orders-head p{margin:6px 0 0;color:#718096;font-size:.9rem}.orders-workspace .orders-head__actions{display:flex;align-items:center;gap:9px}.orders-workspace .orders-head__actions .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:40px;padding:8px 13px;border-radius:10px;font-size:.86rem;font-weight:700!important;white-space:nowrap}
.orders-workspace .orders-list-shell{overflow:visible;border:1px solid #e6ebf1;border-radius:16px;background:#fff;box-shadow:0 8px 30px rgba(30,41,59,.045)}.orders-workspace .orders-filters{grid-template-columns:minmax(220px,1.45fr) minmax(135px,.8fr) minmax(135px,.8fr) minmax(230px,1.05fr) auto;gap:10px;padding:14px 16px;border-bottom:1px solid #edf0f4}.orders-workspace .orders-filter{height:40px;border-radius:9px;background:#fff;font-size:.8rem}.orders-workspace .orders-filter--date{padding-inline:10px}.orders-workspace .orders-filter--date input{max-width:91px}.orders-workspace .orders-filter-reset{display:inline-flex;align-items:center;justify-content:center;gap:7px;height:40px;padding:0 11px;border-radius:9px;font-size:.8rem}
.orders-workspace .orders-table__head,.orders-workspace .orders-table__row{grid-template-columns:minmax(155px,1fr) minmax(190px,1.25fr) minmax(145px,1.05fr) minmax(120px,.8fr) minmax(105px,.72fr) minmax(104px,.78fr) 36px;gap:12px}.orders-workspace .orders-table__head{min-height:45px;padding:0 16px;background:#f8fafc;color:#8190a2;font-size:.68rem;letter-spacing:.055em;text-transform:uppercase}.orders-workspace .orders-table__row{min-height:80px;padding:11px 16px;transition:background-color .15s ease}.orders-workspace .orders-table__row:hover{background:#fbfdff}.orders-workspace .orders-cell{font-size:.82rem}.orders-workspace .orders-cell--client{gap:9px}.orders-workspace .orders-client-avatar{width:35px;height:35px;flex-basis:35px;border:1px solid #edf1f5;background:#f3f6fa}.orders-workspace .orders-client-avatar--empty{color:#9aa8b8}.orders-workspace .orders-cell--client strong{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#28384a;font-size:.84rem}.orders-workspace .orders-cell small{font-size:.69rem}.orders-workspace .orders-cell--animals{gap:4px}.orders-workspace .orders-pet{gap:7px}.orders-workspace .orders-pet__image{width:29px;height:29px;flex-basis:29px}.orders-workspace .orders-pet strong{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.78rem}.orders-workspace .orders-cell--services{display:flex;flex-wrap:wrap;gap:5px}.orders-workspace .order-service-chip{display:inline-flex;align-items:center;gap:4px;min-height:25px;padding:3px 7px;border:1px solid #e1e8f1;border-radius:7px;background:#f8fafc;color:#485a6e;font-size:.68rem;line-height:1.1;white-space:nowrap}.orders-workspace .order-service-chip b{font-weight:700!important}.orders-workspace .order-service-chip small{margin:0;font-size:.64rem}.orders-workspace .orders-cell--period{font-size:.78rem;color:#526377}.orders-workspace .orders-cell--price strong{display:block;color:#27384a;font-size:.82rem}.orders-workspace .order-status{padding:5px 8px;border-radius:999px;font-size:.67rem;font-weight:700!important}.orders-workspace .order-actions-menu__toggle{width:32px;height:32px;border-radius:8px}.orders-workspace .order-actions-menu__popup{z-index:1070;top:35px;width:184px;border-radius:10px}.orders-workspace .order-actions-menu__popup button{font-size:.79rem}.orders-workspace .orders-list-footer{padding:13px 16px;border-top:1px solid #edf0f4;color:#7c8b9b;font-size:.76rem}.orders-workspace .orders-empty{margin:16px;border-radius:12px;background:#fafcff;padding:58px 20px}.orders-workspace .orders-empty h2{font-weight:700!important;color:#304155}
@media(max-width:1099px){.orders-workspace .orders-filters{grid-template-columns:minmax(0,1fr) minmax(0,1fr)}.orders-workspace .orders-filter--search{grid-column:1/-1}.orders-workspace .orders-table{padding:10px;background:#f7f9fc}.orders-workspace .orders-table__head{display:none}.orders-workspace .orders-table__row{display:grid;grid-template-columns:minmax(0,1.1fr) minmax(0,1fr) auto;gap:10px;min-width:0;margin:0 0 9px;padding:14px;border:1px solid #e6ebf1;border-radius:12px;background:#fff}.orders-workspace .orders-table__row:last-child{margin-bottom:0}.orders-workspace .orders-cell--client{grid-column:1/2}.orders-workspace .orders-cell--actions{grid-column:3;grid-row:1}.orders-workspace .orders-cell--animals{grid-column:1/2}.orders-workspace .orders-cell--services{grid-column:2;grid-row:1/3;align-content:start}.orders-workspace .orders-cell--period{grid-column:1}.orders-workspace .orders-cell--price{grid-column:2}.orders-workspace .orders-table__row>.orders-cell:nth-last-child(2){grid-column:3;grid-row:2;justify-self:end}.orders-workspace .orders-empty{margin:0}}
@media(max-width:767px){.orders-workspace .orders-head{align-items:stretch;gap:15px}.orders-workspace .orders-head__actions{display:grid;grid-template-columns:1fr 1.35fr}.orders-workspace .orders-head__actions .btn{min-width:0;padding-inline:9px;font-size:.8rem}.orders-workspace .orders-filters{grid-template-columns:1fr;padding:12px}.orders-workspace .orders-filter--search{grid-column:auto}.orders-workspace .orders-filter--date{display:grid;grid-template-columns:auto minmax(0,1fr) auto minmax(0,1fr);gap:5px;height:auto;min-height:40px}.orders-workspace .orders-filter--date input{max-width:none;width:100%}.orders-workspace .orders-filter-reset{width:100%}.orders-workspace .orders-table{padding:8px}.orders-workspace .orders-table__row{grid-template-columns:minmax(0,1fr) auto;gap:11px;padding:13px}.orders-workspace .orders-cell--client{grid-column:1}.orders-workspace .orders-cell--actions{grid-column:2;grid-row:1}.orders-workspace .orders-cell--animals{grid-column:1/-1;grid-template-columns:1fr}.orders-workspace .orders-cell--services{grid-column:1/-1;grid-row:auto}.orders-workspace .orders-cell--period{grid-column:1;grid-row:auto}.orders-workspace .orders-cell--price{grid-column:2;grid-row:auto;text-align:right}.orders-workspace .orders-cell--price::before{content:'Стоимость: ';color:#8793a1;font-weight:400}.orders-workspace .orders-table__row>.orders-cell:nth-last-child(2){grid-column:1;grid-row:auto;justify-self:start}.orders-workspace .order-actions-menu__popup{right:0;top:35px;z-index:1095}.orders-workspace .orders-list-footer{padding:12px}.orders-workspace .orders-empty{padding:44px 16px}}
@media(max-width:390px){.orders-workspace .orders-head h1{font-size:1.62rem}.orders-workspace .orders-head__actions .btn{font-size:.72rem}.orders-workspace .orders-head__actions .btn span{overflow:hidden;text-overflow:ellipsis}.orders-workspace .orders-filter{font-size:.76rem}}
</style>@endpush
@push('styles')<style>
#serviceOrderModal .modal-dialog{max-width:980px}#serviceOrderModal .modal-content{border:0;border-radius:12px;box-shadow:0 18px 60px rgba(15,35,60,.18);overflow:hidden}.order-modal__header{padding:17px 20px;border-bottom:1px solid #e8edf3}.order-modal__header .modal-title{display:flex;align-items:center;gap:12px;font-size:1.05rem;font-weight:700}.order-modal__header .modal-title i{color:#0d6efd;font-size:1rem}.order-modal__body{padding:18px 20px 22px;background:#fff}.order-modal__section{border:1px solid #dfe7f0;border-radius:11px;padding:16px;margin:0 0 16px}.order-modal__section h6,.order-comment .form-label{font-size:.82rem;font-weight:800;color:#293849;margin:0 0 11px}.order-details__grid{display:grid;gap:18px}.order-client{position:relative}.order-client>.form-label,.order-dates .form-label{display:block;font-size:.75rem;color:#68788b;font-weight:700;margin:0}.order-client__controls{display:grid;grid-template-columns:minmax(0,1fr) 146px;gap:12px;margin-top:5px}.order-client .form-select{height:40px;border-color:#dce5ef;font-size:.84rem;color:#58697d}.order-client__new{height:40px;border:1px solid #0d6efd;background:#fff;color:#0d6efd;border-radius:8px;display:flex;justify-content:center;align-items:center;gap:8px;font-size:.82rem;line-height:1;font-weight:600}.order-client__new i{font-size:.8rem}.order-dates{display:grid;grid-template-columns:162px 168px minmax(0,1fr);gap:12px}.order-dates .form-control{margin-top:5px;height:40px;font-size:.82rem}.order-dates__address{grid-column:auto}.quick-client__menu{position:fixed;z-index:1085;width:300px;padding:14px;border:1px solid #dbe4ef;background:#fff;border-radius:10px;box-shadow:0 14px 32px rgba(27,39,57,.2)}.order-editor-head{margin:0 0 10px}.order-editor-head h6{margin:0}.order-editor-head .btn{font-size:.8rem;padding:6px 10px}.order-editor-list{gap:12px}.animal-editor{padding:12px 14px;border-color:#dfe7f0;background:#fbfcfe;border-radius:10px}.animal-editor__fields{gap:10px}.animal-editor__fields>label{margin:0;font-size:.72rem;color:#6b7888;font-weight:700}.animal-editor__fields .form-control,.animal-editor__fields .form-select{height:34px;margin-top:4px;font-size:.82rem}.animal-editor__services{background:#fff;border:1px solid #e3eaf2;border-radius:9px;padding:10px;margin-top:12px}.animal-service-add{display:flex;justify-content:flex-end;position:relative}.animal-service-add .btn{font-size:.75rem;padding:4px 8px}.animal-service-list{gap:7px}.animal-service-row{grid-template-columns:210px 1fr 1fr 34px;gap:9px;align-items:end;padding:8px;border:0;background:#f8fafc}.animal-service-type{display:flex;align-items:center;gap:8px;font-size:.83rem;font-weight:700;color:#35465a}.animal-service-type::before{content:'✦';display:grid;place-items:center;width:28px;height:28px;border-radius:7px;background:#eaf2ff;color:#0d6efd}.animal-service-row .small{font-size:.7rem;color:#69798b;font-weight:700}.animal-service-row .form-control{height:33px;margin-top:3px;font-size:.8rem}.animal-service-row .btn{width:32px;height:33px;padding:0;display:grid;place-items:center}.order-comment{padding:0 10px}.order-comment .form-control{min-height:54px;font-size:.85rem}.order-modal__footer{padding:14px 20px;border-top:1px solid #e8edf3}.order-modal__footer .btn{font-size:.82rem;padding:8px 13px}.order-modal__delete{margin-right:auto}.add-animal-popover{top:38px}.animal-editor__quick-actions{align-self:end}.pet-action-menu .btn{height:34px;width:34px;font-size:0;padding:0}.pet-action-menu .btn::before{content:'⋯';font-size:21px;line-height:1}.pet-action-menu__popup{z-index:1090}.animal-editor__remove{height:34px!important;width:34px!important;align-self:end}.animal-search-results{z-index:1090}@media(max-width:767px){#serviceOrderModal .modal-dialog{margin:8px}.order-modal__body{padding:13px}.order-client__controls,.order-dates{grid-template-columns:1fr}.order-dates{gap:8px}.order-modal__section{padding:12px}.animal-editor__fields{grid-template-columns:minmax(0,1fr) 90px 66px 34px!important;gap:6px}.animal-editor__fields .animal-editor__quick-actions{grid-column:auto!important}.animal-editor__services{padding:8px}.animal-service-row{grid-template-columns:1fr 1fr 32px}.animal-service-type{grid-column:1/-1}.order-modal__footer{padding:12px}.order-modal__footer .btn{padding:7px 9px}.order-modal__delete{font-size:0!important}.order-modal__delete i{font-size:.85rem}}
</style>@endpush
@push('styles')<style>
.animal-editor__avatar{order:0;flex:0 0 46px;width:46px;height:46px;border-radius:50%;display:grid;place-items:center;align-self:end;background:#edf4ff;color:#0d6efd;font-size:1rem}.animal-editor__fields>label:has(.animal-search){order:1}.animal-editor__fields>label:has(.animal-category){order:3}.animal-editor__fields>label:has([name$="[quantity]"]){order:4}.animal-editor__quick-actions{order:2}.animal-editor__remove{order:5}@media(max-width:767px){.animal-editor__avatar{display:none}}
</style>@endpush
@push('styles')<style>
.order-add-pet{position:relative;margin-top:12px}.order-add-pet>#addOrderAnimal{width:100%;min-height:36px;border-style:dashed;border-color:#73a9ff;background:#f9fcff;color:#0d6efd;font-size:.8rem}.order-add-pet .add-animal-popover{top:44px;left:0}.animal-services__head{display:flex;align-items:center;justify-content:space-between;margin-bottom:7px}.animal-services__head>strong{font-size:.76rem;color:#35465a}.animal-service-row .form-select{height:33px;margin-top:3px;font-size:.78rem}
</style>@endpush
@push('styles')<style>
.add-animal-results{max-height:190px;overflow:auto;margin-top:6px;border:1px solid #dce5ef;border-radius:8px;background:#fff}.add-animal-results.is-hidden{display:none}.add-animal-result{display:block;width:100%;padding:8px 10px;border:0;background:transparent;color:#35465a;font-size:.82rem;text-align:left}.add-animal-result:hover{background:#f1f6ff;color:#0d6efd}.add-animal-empty{padding:8px 10px;color:#7b8898;font-size:.78rem}
</style>@endpush
@push('styles')<style>
.animal-service-row{background:transparent!important;border:0!important;padding:8px 0!important}.animal-service-type{background:transparent!important}.animal-service-type::before{display:none!important}.animal-service-type img{width:30px;height:30px;object-fit:contain;flex:0 0 30px}
</style>@endpush
@push('styles')<style>
/* Orders list presentation: a quiet table on wide screens and self-contained cards below 1100px. */
.orders-workspace .orders-head {
    display: flex;
    justify-content: space-between;
    gap: 20px;
}
.orders-workspace .orders-head > div:first-child,
.orders-workspace .orders-head h1,
.orders-workspace .orders-head__actions {
    min-width: 0;
}
.orders-workspace .orders-head__actions .btn-outline-secondary {
    border-color: #d8e0e9;
    background: #fff;
    color: #506276;
}
.orders-workspace .orders-head__actions .btn-outline-secondary:hover,
.orders-workspace .orders-head__actions .btn-outline-secondary:focus-visible {
    border-color: #a9bfd6;
    background: #f7faff;
    color: #2d5f91;
}
.orders-workspace .orders-head__actions .btn-primary {
    border-color: #5d78db;
    background: #5d78db;
    box-shadow: 0 6px 14px rgba(83, 108, 205, .2);
}
.orders-workspace .orders-create-region {
    display: flex;
    justify-content: flex-start;
    width: 100%;
}
.orders-workspace .orders-list-shell {
    overflow: visible;
    border-color: #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 10px 28px rgba(32, 52, 74, .055);
}
.orders-workspace .orders-filters {
    grid-template-columns: minmax(220px, 1.6fr) minmax(145px, .85fr) minmax(145px, .85fr) minmax(245px, 1.15fr) auto;
    gap: 9px;
    padding: 13px 14px;
    background: #fbfcfe;
    border-bottom-color: #e8edf3;
}
.orders-workspace .orders-filter {
    height: 42px;
    border-color: #d9e2eb;
    border-radius: 9px;
    background: #fff;
    font-size: .81rem;
}
.orders-workspace .orders-filter--date {
    padding-inline: 11px;
}
.orders-workspace .orders-filter--date input {
    max-width: 101px;
    font-size: .79rem;
}
.orders-workspace .orders-filter-reset {
    height: 42px;
    padding-inline: 12px;
    border-color: #d9e2eb;
    border-radius: 9px;
    color: #65758a;
    font-size: .8rem;
}
.orders-workspace .orders-table {
    overflow: visible;
    background: #fff;
}
.orders-workspace .orders-table__head,
.orders-workspace .orders-table__row {
    grid-template-columns: minmax(150px, 1.1fr) minmax(160px, 1.05fr) minmax(165px, 1.2fr) minmax(120px, .8fr) minmax(108px, .72fr) minmax(100px, .7fr) 40px;
    gap: 13px;
}
.orders-workspace .orders-table__head {
    min-height: 42px;
    padding: 0 18px;
    background: #f8fafc;
    color: #8a98a8;
    font-size: .66rem;
    letter-spacing: .06em;
}
.orders-workspace .orders-table__row {
    min-height: 92px;
    padding: 14px 18px;
    border-bottom: 1px solid #edf1f5;
}
.orders-workspace .orders-table__row:last-child {
    border-bottom: 0;
}
.orders-workspace .orders-table__row:hover {
    background: #fcfdff;
}
.orders-workspace .orders-cell {
    min-width: 0;
    font-size: .83rem;
}
.orders-workspace .orders-client-avatar {
    width: 40px;
    height: 40px;
    flex-basis: 40px;
    border-color: #e8edf3;
}
.orders-workspace .orders-cell--client {
    gap: 10px;
}
.orders-workspace .orders-cell--client > div,
.orders-workspace .orders-pet > span:last-child {
    min-width: 0;
}
.orders-workspace .orders-cell--client strong {
    font-size: .84rem;
}
.orders-workspace .orders-cell--animals {
    gap: 6px;
}
.orders-workspace .orders-pet {
    gap: 8px;
}
.orders-workspace .orders-pet__image {
    width: 32px;
    height: 32px;
    flex-basis: 32px;
}
.orders-workspace .orders-pet strong {
    font-size: .8rem;
}
.orders-workspace .orders-pet small {
    color: #8b98a8;
    font-size: .68rem;
}
.orders-workspace .orders-cell--services {
    align-content: center;
    gap: 5px;
}
.orders-workspace .order-service-chip {
    max-width: 100%;
    min-height: 27px;
    padding: 4px 8px;
    border-color: #e1e8f1;
    border-radius: 7px;
    background: #f8fafc;
    font-size: .69rem;
}
.orders-workspace .order-service-chip small {
    overflow-wrap: anywhere;
    font-size: .63rem;
}
.orders-workspace .orders-cell--period {
    color: #526477;
    font-size: .79rem;
    font-weight: 600;
}
.orders-workspace .orders-cell--price strong {
    font-size: .84rem;
}
.orders-workspace .orders-cell--price small {
    color: #8a98a8;
}
.orders-workspace .order-status {
    display: inline-flex;
    align-items: center;
    min-height: 26px;
    padding: 5px 9px;
    font-size: .67rem;
    white-space: nowrap;
}
.orders-workspace .order-actions-menu__toggle {
    width: 34px;
    height: 34px;
    border: 1px solid transparent;
    color: #738296;
}
.orders-workspace .order-actions-menu__toggle:hover,
.orders-workspace .order-actions-menu__toggle:focus-visible {
    border-color: #dce5ee;
    background: #f6f9fc;
    color: #3f658d;
    outline: 0;
}
.orders-workspace .order-actions-menu__popup {
    right: 0;
    top: 38px;
    width: 190px;
    padding: 6px;
    border-color: #dce5ee;
    border-radius: 10px;
}
.orders-workspace .orders-empty {
    margin: 14px;
    border: 1px dashed #d8e2ed;
    border-radius: 12px;
    background: #fbfcfe;
}

@media (max-width: 1350px) {
    .orders-workspace .orders-filters {
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    }
    .orders-workspace .orders-filter--search {
        grid-column: 1 / -1;
    }
    .orders-workspace .orders-table {
        padding: 10px;
        background: #f7f9fc;
    }
    .orders-workspace .orders-table__head {
        display: none;
    }
    .orders-workspace .orders-table__row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) max-content;
        gap: 11px 14px;
        min-width: 0;
        min-height: 0;
        margin: 0 0 10px;
        padding: 15px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 4px 14px rgba(34, 52, 73, .035);
    }
    .orders-workspace .orders-table__row:last-child {
        margin-bottom: 0;
    }
    .orders-workspace .orders-cell--client { grid-column: 1 / 3; grid-row: 1; }
    .orders-workspace .orders-cell--actions { grid-column: 3; grid-row: 1; }
    .orders-workspace .orders-cell--animals { grid-column: 1; grid-row: 2; }
    .orders-workspace .orders-cell--services { grid-column: 2 / 4; grid-row: 2; justify-content: flex-start; }
    .orders-workspace .orders-cell--period { grid-column: 1; grid-row: 3; }
    .orders-workspace .orders-cell--price { grid-column: 2; grid-row: 3; text-align: right; }
    .orders-workspace .orders-table__row > .orders-cell:nth-last-child(2) { grid-column: 3; grid-row: 3; justify-self: end; }
    .orders-workspace .order-actions-menu__popup { z-index: 1095; }
    .orders-workspace .orders-empty { margin: 0; }
}

@media (max-width: 767px) {
    .orders-workspace .orders-head {
        flex-direction: column;
        align-items: stretch;
        gap: 14px;
    }
    .orders-workspace .orders-head__actions {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1.35fr);
        width: 100%;
    }
    .orders-workspace .orders-head__actions .btn {
        min-width: 0;
        padding-inline: 8px;
        font-size: .78rem;
    }
    .orders-workspace .orders-head__actions .btn span {
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .orders-workspace .orders-filters {
        grid-template-columns: minmax(0, 1fr);
        padding: 12px;
    }
    .orders-workspace .orders-filter--search { grid-column: auto; }
    .orders-workspace .orders-filter--date {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto minmax(0, 1fr);
        gap: 5px;
        height: auto;
        min-height: 42px;
    }
    .orders-workspace .orders-filter--date input {
        width: 100%;
        max-width: none;
    }
    .orders-workspace .orders-filter-reset {
        width: 100%;
    }
    .orders-workspace .orders-table {
        padding: 8px;
    }
    .orders-workspace .orders-table__row {
        gap: 11px 10px;
        padding: 13px;
    }
    .orders-workspace .orders-cell--client strong {
        font-size: .82rem;
    }
    .orders-workspace .orders-client-avatar {
        width: 36px;
        height: 36px;
        flex-basis: 36px;
    }
    .orders-workspace .orders-cell--price::before {
        content: 'Стоимость: ';
        color: #8793a1;
        font-weight: 400;
    }
    .orders-workspace .order-service-chip {
        white-space: normal;
        overflow-wrap: anywhere;
    }
    .orders-workspace .order-actions-menu__popup {
        right: 0;
        top: 38px;
        max-width: calc(100vw - 32px);
    }
}

@media (max-width: 390px) {
    .orders-workspace .orders-head h1 { font-size: 1.62rem; }
    .orders-workspace .orders-head__actions .btn { font-size: .71rem; }
    .orders-workspace .orders-filter { font-size: .76rem; }
    .orders-workspace .orders-table__row { padding: 11px; }
    .orders-workspace .orders-cell { font-size: .78rem; }
    .orders-workspace .orders-cell--period { font-size: .75rem; }
    .orders-workspace .order-status { font-size: .64rem; }
}
</style>@endpush
@push('scripts')<script>
document.addEventListener('DOMContentLoaded',()=>{const modal=new bootstrap.Modal(document.getElementById('serviceOrderModal')),form=document.getElementById('serviceOrderForm'),positions=document.getElementById('orderAnimals'),saved=@json($animalsPayload),cats=@json($categoriesPayload),esc=v=>String(v||'').replaceAll('&','&amp;').replaceAll('"','&quot;'),types=['передержка','выгул','уход'];
const serviceIconBase='{{ asset('images/service-types') }}',serviceIcons={передержка:'boarding',выгул:'walking',уход:'care'};function serviceRow(box,index,service={}){let i=box.children.length,row=document.createElement('div'),frequency=Number(service.units_per_day||1),icon=serviceIcons[service.service_type]||'care';row.className='animal-service-row';row.innerHTML=`<span class="animal-service-type"><img src="${serviceIconBase}/${icon}.png" alt="">${service.service_type||''}</span><label class="small">Раз в день<select class="form-select" name="animals[${index}][services][${i}][units_per_day]">${[1,2,3,4,5,6].map(value=>`<option value="${value}" ${frequency===value?'selected':''}>${value} ${value===1?'раз':'раза'} в день</option>`).join('')}</select><input type="hidden" name="animals[${index}][services][${i}][service_type]" value="${service.service_type||''}"></label><label class="small">Цена за услугу, ₽<input class="form-control" type="number" min="0" name="animals[${index}][services][${i}][unit_price]" value="${service.unit_price||500}"></label><label class="small animal-service-cost-label">Стоимость<output class="animal-service-cost">0 ₽</output></label><button type="button" class="btn btn-sm btn-outline-danger" title="Убрать услугу"><i class="fa fa-xmark"></i></button>`;row.querySelector('button').onclick=()=>row.remove();box.append(row)}
function animalRow(position={}){let index=positions.children.length,row=document.createElement('section');row.className='animal-editor';let animalOpts='<option value="">Выбрать сохранённого</option>'+saved.map(a=>`<option value="${a.id}" ${String(a.id)===String(position.animal_id)?'selected':''}>${esc(a.name)}${a.client?' · '+esc(a.client):''}</option>`).join(''),catOpts='<option value="">Вид</option>'+cats.map(c=>`<option value="${c.id}" ${String(c.id)===String(position.category_id)?'selected':''}>${esc(c.name)}</option>`).join('');row.innerHTML=`<div class="animal-editor__fields"><span class="animal-editor__avatar"><i class="fa fa-paw"></i></span><label class="small">Сохранённый<select class="form-select animal-select" name="animals[${index}][animal_id]">${animalOpts}</select></label><label class="small">Или новый<input class="form-control animal-name" name="animals[${index}][name]" value="${esc(position.name)}" placeholder="${esc(position.label||'Кличка')}"></label><label class="small">Вид<select class="form-select animal-category" name="animals[${index}][category_id]">${catOpts}</select></label><label class="small">Количество<input class="form-control" type="number" min="1" name="animals[${index}][quantity]" value="${position.quantity||1}"></label><button type="button" class="animal-editor__remove btn btn-sm btn-outline-danger" title="Удалить питомца" aria-label="Удалить питомца"><i class="fa fa-xmark"></i></button></div><div class="animal-editor__services"><div class="animal-services__head"><strong>Услуги</strong><div class="animal-service-add"><button class="btn btn-outline-primary" type="button" aria-label="Добавить услугу" title="Добавить услугу"><i class="fa fa-plus"></i> Добавить услугу</button><div class="animal-service-add__menu is-hidden">${types.map(type=>`<button type="button" data-type="${type}">${type[0].toUpperCase()+type.slice(1)}</button>`).join('')}</div></div></div><div class="animal-service-list"></div></div>`;let fields=row.querySelector('.animal-editor__fields'),avatar=row.querySelector('.animal-editor__avatar'),selected=saved.find(item=>String(item.id)===String(position.animal_id)),category=cats.find(item=>String(item.id)===String(position.category_id||selected?.category_id)),identity=document.createElement('div');identity.className='animal-editor__identity';identity.innerHTML=`<span class="animal-editor__identity-copy"><strong>${esc(position.name||selected?.name||position.label||'Новый питомец')}</strong><small>${esc(category?.name||'Вид не выбран')}</small></span>`;identity.prepend(avatar);row.insertBefore(identity,fields);const updateIdentity=()=>{let name=row.querySelector('.animal-search,.animal-name')?.value?.trim()||row.querySelector('.animal-name')?.value?.trim()||'Новый питомец',selectedAnimal=saved.find(item=>String(item.id)===String(row.querySelector('.animal-select')?.value)),selectedCategory=cats.find(item=>String(item.id)===String(row.querySelector('.animal-category')?.value));identity.querySelector('strong').textContent=selectedAnimal?.name||name;identity.querySelector('small').textContent=selectedCategory?.name||'Вид не выбран'};row.querySelector('.animal-editor__remove').onclick=()=>{if(confirm('Удалить питомца из этого заказа?'))row.remove()};row.querySelector('.animal-select').onchange=e=>{let a=saved.find(item=>item.id==e.target.value);if(a){row.querySelector('.animal-name').value=a.name;row.querySelector('.animal-category').value=a.category_id||''}updateIdentity()};row.querySelector('.animal-category').onchange=updateIdentity;row.querySelector('.animal-name').oninput=updateIdentity;let list=row.querySelector('.animal-service-list'),serviceAdd=row.querySelector('.animal-service-add'),serviceMenu=row.querySelector('.animal-service-add__menu');serviceAdd.querySelector('.btn').onclick=event=>{event.stopPropagation();document.querySelectorAll('.animal-service-add__menu').forEach(menu=>{if(menu!==serviceMenu)menu.classList.add('is-hidden')});serviceMenu.classList.toggle('is-hidden')};serviceMenu.addEventListener('click',event=>event.stopPropagation());serviceMenu.querySelectorAll('[data-type]').forEach(button=>button.onclick=()=>{serviceRow(list,index,{service_type:button.dataset.type,units_per_day:1,unit_price:500});serviceMenu.classList.add('is-hidden')});(position.services||[{service_type:'уход',units_per_day:1,unit_price:500}]).forEach(service=>serviceRow(list,index,service));positions.append(row)}
window.addOrderAnimalRow=animalRow;function open(data){let isEdit=Boolean(data?.id),archiveButton=document.getElementById('orderArchive'),archiveForm=document.getElementById('orderArchiveForm');form.action=isEdit?`{{ url('/zooadmin/service-orders') }}/${data.id}`:`{{ route('admin.service-orders.store') }}`;document.getElementById('orderMethod').value=isEdit?'PUT':'POST';document.getElementById('orderModalTitleText').textContent=isEdit?'Редактировать заказ':'Новый заказ';document.getElementById('orderModalIcon').className=isEdit?'fa fa-pen':'fa fa-plus-circle';archiveButton.classList.toggle('d-none',!isEdit);if(isEdit)archiveForm.action=`{{ url('/zooadmin/service-orders') }}/${data.id}/archive`;document.getElementById('orderClient').value=data?.client_id||'';document.getElementById('orderStart').value=data?.start||'';document.getElementById('orderEnd').value=data?.end||'';document.getElementById('orderAddress').value=data?.address||'';document.getElementById('orderNote').value=data?.note||'';positions.innerHTML='';(data?.animals||[{}]).forEach(animalRow);['orderClient','orderStart','orderEnd'].forEach(id=>document.getElementById(id).dispatchEvent(new Event('change',{bubbles:true})));modal.show()}document.querySelector('.js-new-service-order').onclick=()=>open();document.querySelectorAll('.js-edit-service-order').forEach(button=>button.onclick=()=>open(JSON.parse(button.dataset.order)));document.getElementById('addOrderAnimal').onclick=()=>animalRow();document.addEventListener('click',()=>document.querySelectorAll('.animal-service-add__menu').forEach(menu=>menu.classList.add('is-hidden')));});
document.addEventListener('DOMContentLoaded',()=>{const clientFields=document.getElementById('quickClientFields'),toggleClient=document.getElementById('toggleQuickClient'),closeClientFields=()=>clientFields?.classList.add('d-none');toggleClient?.addEventListener('click',event=>{event.stopPropagation();if(!clientFields)return;let opening=clientFields.classList.contains('d-none');closeClientFields();if(!opening)return;let rect=toggleClient.getBoundingClientRect();clientFields.style.top=`${rect.bottom+8}px`;clientFields.style.left=`${Math.max(16,Math.min(rect.right-300,window.innerWidth-316))}px`;clientFields.classList.remove('d-none');document.getElementById('newClientName')?.focus()});clientFields?.addEventListener('click',event=>event.stopPropagation());document.addEventListener('click',closeClientFields);window.addEventListener('resize',closeClientFields);document.querySelectorAll('.js-new-service-order,.js-edit-service-order').forEach(button=>button.addEventListener('click',()=>{document.querySelectorAll('[name^="new_client"]').forEach(input=>input.value='');closeClientFields()}))});
</script>@endpush
@push('scripts')<script>
document.addEventListener('DOMContentLoaded',()=>{const root=document.getElementById('orderAnimals'),base='{{ url('/zooadmin/animals') }}';if(!root)return;const refresh=row=>{const select=row.querySelector('.animal-select'),field=row.querySelector('.animal-editor__quick-actions');if(!select)return;let actions=field;if(!actions){actions=document.createElement('div');actions.className='animal-editor__quick-actions';(row.querySelector('.animal-editor__identity')||select.closest('label')).append(actions)}if(!select.value){actions.innerHTML='';return}let url=`${base}/${select.value}`;actions.dataset.compact='1';actions.innerHTML=`<div class="pet-action-menu"><button type="button" class="btn btn-sm btn-outline-secondary pet-action-menu__toggle" aria-label="Действия с питомцем" aria-expanded="false"><i class="fa fa-ellipsis"></i></button><div class="pet-action-menu__popup is-hidden"><a href="${url}" target="_blank" rel="noopener">Открыть карточку</a><a href="${url}/edit" target="_blank" rel="noopener">Редактировать</a></div></div>`};root.addEventListener('change',event=>{if(event.target.matches('.animal-select'))refresh(event.target.closest('.animal-editor'))});new MutationObserver(records=>records.forEach(record=>record.addedNodes.forEach(node=>{if(node.nodeType===1&&node.matches('.animal-editor'))refresh(node)}))).observe(root,{childList:true})});
</script>@endpush
@push('scripts')<script>
document.addEventListener('DOMContentLoaded',()=>{const root=document.getElementById('orderAnimals'),button=document.getElementById('addOrderAnimal'),popover=document.getElementById('addAnimalPopover'),query=document.getElementById('addAnimalQuery'),results=document.getElementById('addAnimalResults'),confirm=document.getElementById('confirmAddAnimal'),animals=@json($animalsPayload),close=()=>{popover.classList.add('is-hidden');results?.classList.add('is-hidden')};if(!root||!button||!popover||!query||!results)return;let selected=null;const render=()=>{let value=query.value.trim().toLowerCase(),matches=animals.filter(animal=>!value||animal.name.toLowerCase().includes(value)).slice(0,8);results.replaceChildren();if(matches.length){matches.forEach(animal=>{let item=document.createElement('button');item.type='button';item.className='add-animal-result';item.textContent=animal.name+(animal.client?' · '+animal.client:'');item.addEventListener('mousedown',event=>{event.preventDefault();selected=animal;query.value=animal.name;results.classList.add('is-hidden')});results.append(item)})}else{let empty=document.createElement('div');empty.className='add-animal-empty';empty.textContent='Будет добавлен новый питомец';results.append(empty)}results.classList.remove('is-hidden')};button.onclick=event=>{event.stopPropagation();let opening=popover.classList.contains('is-hidden');popover.classList.toggle('is-hidden');if(opening){query.focus();render()}};popover.addEventListener('click',event=>event.stopPropagation());document.addEventListener('click',close);query.addEventListener('focus',render);query.addEventListener('input',()=>{selected=animals.find(animal=>animal.name.toLowerCase()===query.value.trim().toLowerCase())||null;render()});confirm.onclick=()=>{let name=query.value.trim();if(!name){query.focus();return}let rowData={animal_id:selected?.id||null,name:selected?selected.name:name,category_id:selected?.category_id||null,quantity:1,services:[]};window.addOrderAnimalRow(rowData);close();selected=null;query.value='';setTimeout(()=>root.lastElementChild?.querySelector('.animal-search')?.focus(),0)};root.addEventListener('click',event=>{let remove=event.target.closest('.animal-editor__head button');if(remove&&!confirm('Удалить питомца из этого заказа?'))event.preventDefault()});new MutationObserver(records=>records.forEach(record=>record.addedNodes.forEach(node=>{if(node.nodeType!==1||!node.matches('.animal-editor'))return;let service=node.querySelector('.animal-service-add .btn');if(service)service.innerHTML='<i class="fa fa-plus"></i> Добавить услугу';let remove=node.querySelector('.animal-editor__head button');if(remove)remove.onclick=event=>{if(confirm('Удалить питомца из этого заказа?'))node.remove()}}))).observe(root,{childList:true})});
</script>@endpush
@push('scripts')<script>
document.addEventListener('DOMContentLoaded',()=>document.getElementById('serviceOrderModal')?.addEventListener('shown.bs.modal',()=>document.querySelectorAll('#orderAnimals .animal-editor').forEach(row=>{let saved=row.querySelector('.animal-select'),name=row.querySelector('.animal-name');if(!saved?.value&&!name?.value?.trim())row.remove()})));
</script>@endpush
@push('scripts')<script>
document.addEventListener('DOMContentLoaded',()=>document.getElementById('orderAnimals')?.addEventListener('click',event=>{let button=event.target.closest('.animal-editor__head button');if(!button)return;event.preventDefault();event.stopImmediatePropagation();if(window.confirm('Удалить питомца из этого заказа?'))button.closest('.animal-editor')?.remove()},true));
</script>@endpush
@push('scripts')<script>
document.addEventListener('DOMContentLoaded',()=>{const root=document.getElementById('orderAnimals');if(!root)return;const enhance=row=>{if(row.dataset.searchReady)return;let select=row.querySelector('.animal-select'),name=row.querySelector('.animal-name');if(!select||!name)return;row.dataset.searchReady='1';let options=[...select.options].filter(option=>option.value).map(option=>({id:option.value,name:option.textContent.split(' · ')[0].trim(),label:option.textContent.trim()})),oldField=select.closest('label'),field=document.createElement('label'),input=document.createElement('input'),results=document.createElement('div');field.className='small animal-search-field';field.append('Кличка');input.className='form-control animal-search';input.autocomplete='off';input.placeholder='Начните вводить кличку';input.value=name.value||options.find(option=>option.id===select.value)?.name||'';results.className='animal-search-results is-hidden';oldField.replaceWith(field);field.append(input,results,select);name.closest('label').style.display='none';const render=()=>{let query=input.value.trim().toLowerCase(),matches=options.filter(option=>!query||option.name.toLowerCase().includes(query)).slice(0,8);results.replaceChildren();if(!matches.length){let empty=document.createElement('div');empty.className='animal-search-empty';empty.textContent='Сохранённых питомцев не найдено';results.append(empty)}else matches.forEach(option=>{let button=document.createElement('button');button.type='button';button.className='animal-search-result';button.textContent=option.label;button.addEventListener('mousedown',event=>{event.preventDefault();input.value=option.name;select.value=option.id;name.value='';results.classList.add('is-hidden');select.dispatchEvent(new Event('change',{bubbles:true}))});results.append(button)});results.classList.remove('is-hidden')};input.addEventListener('focus',render);input.addEventListener('input',()=>{let exact=options.find(option=>option.name.toLowerCase()===input.value.trim().toLowerCase());select.value=exact?.id||'';name.value=exact?'':input.value.trim();select.dispatchEvent(new Event('change',{bubbles:true}));render()});input.addEventListener('blur',()=>setTimeout(()=>results.classList.add('is-hidden'),150))};new MutationObserver(records=>records.forEach(record=>record.addedNodes.forEach(node=>{if(node.nodeType===1&&node.matches('.animal-editor'))enhance(node)}))).observe(root,{childList:true});root.querySelectorAll('.animal-editor').forEach(enhance)});
</script>@endpush
@push('scripts')<script>
document.addEventListener('DOMContentLoaded',()=>{const root=document.getElementById('orderAnimals');if(!root)return;const refine=row=>{let input=row.querySelector('.animal-search'),category=row.querySelector('.animal-category');if(input&&!input.closest('label')){let label=document.createElement('label');label.className='small';label.textContent='Питомец';input.replaceWith(label);label.append(input)}if(category)category.closest('label').style.display='block';let actions=row.querySelector('.animal-editor__quick-actions');if(actions&&actions.dataset.compact!=='1'){actions.dataset.compact='1';let links=[...actions.querySelectorAll('a')];if(!links.length)return;actions.innerHTML=`<div class="pet-action-menu"><button type="button" class="btn btn-sm btn-outline-secondary">Действия</button><div class="pet-action-menu__popup is-hidden">${links.map(link=>`<a href="${link.href}" target="_blank" rel="noopener">${link.textContent}</a>`).join('')}</div></div>`;let button=actions.querySelector('button'),menu=actions.querySelector('.pet-action-menu__popup');button.onclick=event=>{event.stopPropagation();menu.classList.toggle('is-hidden')};menu.onclick=event=>event.stopPropagation()} };new MutationObserver(records=>records.forEach(record=>record.addedNodes.forEach(node=>{if(node.nodeType===1&&node.matches('.animal-editor'))refine(node)}))).observe(root,{childList:true});root.querySelectorAll('.animal-editor').forEach(refine);document.addEventListener('click',()=>root.querySelectorAll('.pet-action-menu__popup').forEach(menu=>menu.classList.add('is-hidden')))});
</script>@endpush
@push('scripts')<script>
document.addEventListener('DOMContentLoaded',()=>{const root=document.getElementById('orderAnimals'),modalEl=document.getElementById('petCardModal');if(!root||!modalEl)return;const modal=new bootstrap.Modal(modalEl);root.addEventListener('click',event=>{let link=event.target.closest('.pet-action-menu__popup a');if(!link)return;event.preventDefault();event.stopImmediatePropagation();document.getElementById('petCardModalTitle').textContent=link.textContent.trim()==='Редактировать'?'Редактировать питомца':'Карточка питомца';document.getElementById('petCardModalFrame').src=link.href;modal.show()},true);modalEl.addEventListener('hidden.bs.modal',()=>document.getElementById('petCardModalFrame').src='')});
</script>@endpush
@push('scripts')<script>
document.addEventListener('DOMContentLoaded',()=>{const closeActions=()=>document.querySelectorAll('.order-actions-menu__popup').forEach(menu=>{menu.classList.add('is-hidden');menu.previousElementSibling?.setAttribute('aria-expanded','false')});document.querySelectorAll('.order-actions-menu__toggle').forEach(button=>{button.setAttribute('aria-expanded','false');button.addEventListener('click',event=>{event.stopPropagation();let menu=button.nextElementSibling,opening=menu.classList.contains('is-hidden');closeActions();if(opening){menu.classList.remove('is-hidden');button.setAttribute('aria-expanded','true')}})});document.querySelectorAll('.order-actions-menu__popup').forEach(menu=>menu.addEventListener('click',event=>event.stopPropagation()));document.addEventListener('click',closeActions);document.querySelectorAll('.orders-filters input[type="date"]').forEach(field=>field.addEventListener('change',()=>field.form.submit()));
const selects=[...document.querySelectorAll('[data-filter-select]')],closeSelects=except=>selects.forEach(select=>{if(select===except)return;select.classList.remove('is-open');select.querySelector('.orders-custom-select__menu')?.classList.add('is-hidden');select.querySelector('.orders-custom-select__toggle')?.setAttribute('aria-expanded','false')});
selects.forEach(select=>{const input=select.querySelector('input[type="hidden"]'),toggle=select.querySelector('.orders-custom-select__toggle'),menu=select.querySelector('.orders-custom-select__menu'),options=[...menu.querySelectorAll('[role="option"]')],open=()=>{closeSelects(select);select.classList.add('is-open');menu.classList.remove('is-hidden');toggle.setAttribute('aria-expanded','true');let chosen=options.find(option=>option.getAttribute('aria-selected')==='true');(chosen||options[0])?.focus()},close=()=>{select.classList.remove('is-open');menu.classList.add('is-hidden');toggle.setAttribute('aria-expanded','false')},choose=option=>{input.value=option.dataset.value||'';toggle.querySelector('span').textContent=option.textContent;options.forEach(item=>item.setAttribute('aria-selected',String(item===option)));close();input.form.submit()};toggle.addEventListener('click',event=>{event.stopPropagation();if(menu.classList.contains('is-hidden'))open();else close()});toggle.addEventListener('keydown',event=>{if(['ArrowDown','ArrowUp','Enter',' '].includes(event.key)){event.preventDefault();open()}});options.forEach((option,index)=>{option.addEventListener('click',()=>choose(option));option.addEventListener('keydown',event=>{let next;if(event.key==='Escape'){event.preventDefault();close();toggle.focus()}if(event.key==='Tab')close();if(event.key==='Enter'||event.key===' '){event.preventDefault();choose(option)}if(event.key==='ArrowDown')next=options[(index+1)%options.length];if(event.key==='ArrowUp')next=options[(index-1+options.length)%options.length];if(next){event.preventDefault();next.focus()}})});select.addEventListener('click',event=>event.stopPropagation())});document.addEventListener('click',()=>closeSelects());document.addEventListener('keydown',event=>{if(event.key==='Escape'){closeActions();closeSelects()}})});
</script>@endpush
@push('scripts')<script>
document.addEventListener('DOMContentLoaded',()=>{const root=document.getElementById('orderAnimals'),animals=@json($animalsPayload),categories=@json($categoriesPayload),assetBase='{{ asset('images/animal-types') }}',images={кошки:'cat',собаки:'dog',грызуны:'rodent',птицы:'bird',рыбки:'fish',рептилии:'reptile',пауки:'spider',насекомые:'insect'};if(!root)return;const paint=row=>{const avatar=row.querySelector('.animal-editor__avatar'),id=row.querySelector('.animal-select')?.value,animal=animals.find(item=>String(item.id)===String(id)),categoryId=row.querySelector('.animal-category')?.value||animal?.category_id,category=categories.find(item=>String(item.id)===String(categoryId)),key=images[String(category?.name||'').toLowerCase()]||'other',usePhoto=animal?.photo&&String(animal.category_id)===String(categoryId);if(!avatar)return;avatar.replaceChildren();const image=document.createElement('img');image.src=usePhoto?animal.photo:`${assetBase}/${key}.png`;image.alt=usePhoto?animal.name:(category?.name||'Питомец');avatar.append(image)};root.addEventListener('change',event=>{if(event.target.matches('.animal-select,.animal-category'))paint(event.target.closest('.animal-editor'))});new MutationObserver(records=>records.forEach(record=>record.addedNodes.forEach(node=>{if(node.nodeType===1&&node.matches('.animal-editor'))paint(node)}))).observe(root,{childList:true});root.querySelectorAll('.animal-editor').forEach(paint)});
</script>@endpush
@push('styles')<style>.animal-editor__avatar{overflow:hidden}.animal-editor__avatar img{width:100%;height:100%;object-fit:cover}.animal-editor__quick-actions{order:5!important;margin-left:0!important}.animal-editor__remove{order:6!important}</style>@endpush
@push('styles')<style>
/* Order editor: compact, sheet-like layout. Kept last to intentionally supersede legacy editor rules. */
#serviceOrderModal .modal-dialog{max-width:760px}#serviceOrderModal .modal-content{border-radius:8px}.order-modal__header{padding:11px 14px}.order-modal__header .modal-title{font-size:.85rem;color:#334155}.order-modal__header .modal-title i{font-size:.78rem}.order-modal__body{padding:10px 12px 12px;background:#fff}.order-modal__section{border:1px solid #e5edf6;border-radius:7px;padding:10px;margin:0 0 10px}.order-details{padding:0;overflow:hidden}.order-client-summary{height:43px;display:flex;align-items:center;gap:9px;padding:0 10px;border-bottom:1px solid #e7edf4;color:#34445a;font-size:.76rem}.order-client-summary__avatar{width:25px;height:25px;border-radius:50%;display:grid;place-items:center;background:#e7f0ff;color:#4b93fa;font-size:.75rem}.order-client-summary__period{margin-left:auto;color:#506278;font-size:.68rem;white-space:nowrap}.order-client-summary__period i{margin-right:4px}.order-details__grid{gap:9px;padding:9px 10px 10px}.order-client>.form-label,.order-dates .form-label{font-size:.61rem;color:#667990;line-height:1.15}.order-client__controls{grid-template-columns:minmax(0,1fr) 112px;gap:8px;margin-top:4px}.order-client .form-select,.order-client__new,.order-dates .form-control{height:27px;border-color:#d9e4f1;font-size:.66rem;border-radius:4px}.order-client__new{font-size:.65rem;border-color:#76a6ff}.order-dates{grid-template-columns:134px 134px minmax(0,1fr);gap:8px}.order-dates .form-control{margin-top:4px;padding:3px 7px}.order-pets{border:0;padding:0;background:transparent}.order-editor-list{gap:8px}.animal-editor{display:block!important;padding:9px 10px!important;border:1px solid #e5edf6!important;border-radius:7px!important;background:#fff!important}.animal-editor__fields{display:flex!important;align-items:end;gap:8px;padding:0!important}.animal-editor__avatar{order:0!important;align-self:center;width:31px;height:31px;flex:0 0 31px;font-size:.8rem}.animal-editor__fields>label,.animal-editor__fields>label:has(.animal-search),.animal-editor__fields>label:has(.animal-category),.animal-editor__fields>label:has([name$="[quantity]"]){font-size:.61rem;color:#667990;font-weight:700}.animal-editor__fields>label:has(.animal-search){order:1!important;flex:1 1 180px}.animal-editor__quick-actions{order:2!important;align-self:end;margin:0 -1px 0 -5px!important}.animal-editor__fields>label:has(.animal-category){order:3!important;flex:0 0 135px}.animal-editor__fields>label:has([name$="[quantity]"]){order:4!important;flex:0 0 68px}.animal-editor__fields .form-control,.animal-editor__fields .form-select{height:27px;margin-top:4px;padding:3px 7px;font-size:.66rem;border-color:#d9e4f1;border-radius:4px}.animal-editor__quick-actions .btn{width:27px;height:27px;border-radius:4px}.animal-editor__quick-actions .btn::before{font-size:17px}.animal-editor__head{display:none!important}.animal-editor__remove{order:5!important;flex:0 0 27px;width:27px!important;height:27px!important;align-self:end!important;border-radius:4px!important}.animal-editor__services{padding:8px 0 0!important;margin-top:8px!important;border-top:1px solid #edf2f7!important;border-radius:0!important;background:transparent!important}.animal-services__head{margin-bottom:4px}.animal-services__head>strong{font-size:.62rem}.animal-service-add .btn{height:24px;padding:0 7px!important;border-radius:4px!important;font-size:.62rem}.animal-service-add .btn i{font-size:.6rem}.animal-service-list{gap:0;margin-top:0}.animal-service-row{display:grid!important;grid-template-columns:130px 140px 150px 1fr 27px!important;gap:7px;padding:5px 0 0!important;align-items:end!important}.animal-service-type{height:27px!important;justify-content:flex-start!important;padding:0!important;font-size:.65rem!important}.animal-service-type img{width:24px!important;height:24px!important}.animal-service-row label,.animal-service-row .small{font-size:.59rem!important;color:#667990!important}.animal-service-row label:last-of-type::after{content:'Стоимость';display:block;position:absolute;transform:translateY(-43px);font-size:.59rem;color:#667990;font-weight:700}.animal-service-row label:last-of-type{position:relative}.animal-service-row .form-control,.animal-service-row .form-select{height:27px!important;margin-top:3px!important;padding:3px 7px!important;font-size:.66rem!important;border-color:#d9e4f1}.animal-service-row>button{width:27px!important;height:27px!important}.order-add-pet{margin-top:8px}.order-add-pet>#addOrderAnimal{min-height:24px;font-size:.62rem;border-radius:4px}.order-comment{padding:1px 10px 0}.order-comment .form-label{font-size:.63rem;margin-bottom:4px}.order-comment .form-control{min-height:34px;padding:6px 8px;font-size:.67rem;border-color:#d9e4f1;border-radius:4px}.order-summary{display:grid;grid-template-columns:repeat(3,1fr) 1.25fr;align-items:center;margin-top:8px;border:1px solid #dce8f5;border-radius:4px;background:#f8fbff;min-height:28px;color:#455b73;font-size:.64rem;text-align:center}.order-summary span{border-right:1px solid #dce8f5}.order-summary b{margin-left:4px}.order-summary strong{font-size:.72rem;color:#253c58}.order-modal__footer{padding:10px 14px;border-top:1px solid #e7edf4}.order-modal__footer .btn{font-size:.66rem;padding:6px 10px;border-radius:4px}.order-modal__delete{margin-right:auto}
@media(max-width:767px){#serviceOrderModal .modal-dialog{margin:5px}.order-client-summary__period{display:none}.order-dates{grid-template-columns:1fr 1fr}.order-dates__address{grid-column:1/-1}.animal-editor__fields{flex-wrap:wrap}.animal-editor__fields>label:has(.animal-search){flex:1 1 calc(100% - 80px)}.animal-editor__fields>label:has(.animal-category){flex:1 1 42%}.animal-service-row{grid-template-columns:1fr 1fr 27px!important}.animal-service-type{grid-column:1/-1}.animal-service-row label:last-of-type::after{display:none}.order-summary{grid-template-columns:repeat(3,1fr)}.order-summary strong{grid-column:1/-1;border-top:1px solid #dce8f5;padding:6px}}
</style>@endpush
@push('scripts')<script>
document.addEventListener('DOMContentLoaded',()=>{const form=document.getElementById('serviceOrderForm'),root=document.getElementById('orderAnimals');if(!form||!root)return;const money=value=>`${new Intl.NumberFormat('ru-RU').format(value)} ₽`,fmt=value=>{if(!value)return '';let [y,m,d]=value.split('-');return d&&m&&y?`${d}.${m}.${y}`:value};const refresh=()=>{let client=form.querySelector('#orderClient'),start=form.querySelector('#orderStart')?.value,end=form.querySelector('#orderEnd')?.value,clientName=client?.selectedOptions?.[0]?.textContent||'Клиент не выбран';document.getElementById('orderClientSummaryName').textContent=client?.value?clientName:'Клиент не выбран';document.getElementById('orderClientSummaryPeriod').textContent=start&&end?`${fmt(start)} - ${fmt(end)}`:'Укажите период';let days=0;if(start&&end){let a=new Date(`${start}T00:00:00`),b=new Date(`${end}T00:00:00`);days=Math.max(0,Math.round((b-a)/86400000)+1)}let pets=[...root.querySelectorAll('.animal-editor')],services=[...root.querySelectorAll('.animal-service-row')],total=services.reduce((sum,row)=>{let price=Number(row.querySelector('input[name$="[unit_price]"]')?.value||0),units=Number(row.querySelector('select[name$="[units_per_day]"]')?.value||1),quantity=Number(row.closest('.animal-editor')?.querySelector('input[name$="[quantity]"]')?.value||1),cost=price*units*quantity*days;row.querySelector('.animal-service-cost').textContent=money(cost);return sum+cost},0);document.getElementById('orderSummaryDays').textContent=days||'—';document.getElementById('orderSummaryPets').textContent=pets.length;document.getElementById('orderSummaryServices').textContent=services.length;document.getElementById('orderSummaryTotal').textContent=money(total)};form.addEventListener('input',refresh);form.addEventListener('change',refresh);new MutationObserver(records=>{let changed=records.some(record=>[...record.addedNodes,...record.removedNodes].some(node=>node.nodeType===1&&(node.matches?.('.animal-editor,.animal-service-row')||node.querySelector?.('.animal-editor,.animal-service-row'))));if(changed)refresh()}).observe(root,{childList:true,subtree:true});document.getElementById('serviceOrderModal')?.addEventListener('shown.bs.modal',refresh);window.setTimeout(refresh,0)});
</script>@endpush
@push('styles')<style>
.animal-service-cost-label::after{display:none!important}.animal-service-cost{display:flex;align-items:center;height:27px;margin-top:3px;padding:3px 7px;border:1px solid #d9e4f1;border-radius:4px;background:#f8fbff;color:#334a64;font-size:.66rem;font-weight:700;white-space:nowrap}
@media(max-width:767px){.animal-service-row .animal-service-cost-label{grid-column:1 / 3}}
</style>@endpush
@push('styles')<style>
/* Final editor hierarchy: the animal's identity is read before its editable fields. */
#serviceOrderModal .modal-dialog{max-width:780px}
.order-editor-head{display:none!important}
.order-modal__body{padding:10px 12px 12px}
.order-details{margin-bottom:9px!important}
.animal-editor{padding:0!important;overflow:visible}
.animal-editor__identity{display:flex;align-items:center;gap:8px;min-height:43px;padding:6px 10px;border-bottom:1px solid #e7edf4}
.animal-editor__identity .animal-editor__avatar{display:grid!important;position:static!important;order:initial!important;width:28px!important;height:28px!important;flex:0 0 28px!important;align-self:center!important;margin:0!important;border-radius:50%!important}
.animal-editor__identity-copy{display:grid;line-height:1.1;min-width:0}
.animal-editor__identity-copy strong{font-size:.72rem;color:#34445a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.animal-editor__identity-copy small{font-size:.61rem;color:#718196;margin-top:2px}
.animal-editor__identity-link{margin-left:auto;font-size:.62rem;font-weight:700;color:#0d6efd;white-space:nowrap}
.animal-editor__identity .animal-editor__quick-actions{order:initial!important;margin:0 0 0 2px!important;align-self:center!important}
.animal-editor__identity .pet-action-menu .btn{height:27px!important;width:27px!important}
.animal-editor__identity .pet-action-menu{position:relative;z-index:1090}
.animal-editor__identity .pet-action-menu__popup,.animal-editor__services .animal-service-add__menu,.order-add-pet .add-animal-popover,.animal-search-field .animal-search-results{z-index:1091!important}
.animal-editor__fields{padding:8px 10px!important;gap:8px!important}
.animal-editor__fields>.animal-editor__avatar{display:none!important}
.animal-editor__fields>label:has(.animal-search){flex:1 1 260px!important}
.animal-editor__fields>label:has(.animal-category){flex:0 0 155px!important}
.animal-editor__fields>label:has([name$="[quantity]"]){flex:0 0 72px!important}
.animal-editor__remove{margin-left:auto!important}
.animal-editor__services{padding:7px 10px 9px!important;margin-top:0!important}
.animal-services__head{margin-bottom:2px!important}
.animal-services__head>strong{font-size:.61rem!important}
.animal-service-add .btn{font-size:.62rem!important;height:24px!important}
.animal-service-row{grid-template-columns:128px 138px 144px minmax(84px,1fr) 27px!important;gap:7px!important;padding:4px 0 0!important}
.animal-service-type{font-size:.65rem!important}
.order-add-pet{margin-top:8px!important}.order-add-pet>#addOrderAnimal{min-height:24px!important;font-size:.62rem!important}
.order-comment{padding:0 10px!important}.order-comment .form-control{min-height:35px!important}
.order-summary{margin-top:8px!important}.order-modal__footer{padding:10px 14px!important}
@media(max-width:767px){.animal-editor__identity{padding:7px 9px}.animal-editor__identity-link{display:none}.animal-editor__fields>label:has(.animal-search){flex:1 1 calc(100% - 80px)!important}.animal-editor__fields>label:has(.animal-category){flex:1 1 45%!important}.animal-service-row{grid-template-columns:1fr 1fr 27px!important}.animal-service-type{grid-column:1/-1}.animal-editor__services{padding:7px 9px 9px!important}}
</style>@endpush
@push('scripts')<script>
document.addEventListener('DOMContentLoaded',()=>{const root=document.getElementById('orderAnimals');if(!root)return;const closeMenus=except=>root.querySelectorAll('.pet-action-menu__popup').forEach(menu=>{if(menu!==except)menu.classList.add('is-hidden')});root.addEventListener('click',event=>{const toggle=event.target.closest('.pet-action-menu__toggle');if(toggle){event.preventDefault();event.stopPropagation();const menu=toggle.closest('.pet-action-menu')?.querySelector('.pet-action-menu__popup');if(!menu)return;const opening=menu.classList.contains('is-hidden');closeMenus(menu);menu.classList.toggle('is-hidden',!opening);toggle.setAttribute('aria-expanded',String(opening));return}if(event.target.closest('.pet-action-menu__popup'))event.stopPropagation()});document.addEventListener('click',()=>closeMenus());root.addEventListener('input',event=>{if(!event.target.matches('.animal-search,.animal-name'))return;const row=event.target.closest('.animal-editor'),name=row?.querySelector('.animal-editor__identity-copy strong');if(name)name.textContent=event.target.value.trim()||'Новый питомец'});root.addEventListener('change',event=>{if(!event.target.matches('.animal-category'))return;const row=event.target.closest('.animal-editor'),text=event.target.selectedOptions[0]?.textContent||'Вид не выбран',category=row?.querySelector('.animal-editor__identity-copy small');if(category)category.textContent=text})});
</script>@endpush
@push('styles')<style>
/* Readable type scale for the order editor. */
#serviceOrderModal .order-modal__header .modal-title{font-size:1rem}
#serviceOrderModal .order-client-summary{font-size:.86rem}
#serviceOrderModal .order-client-summary__period{font-size:.78rem}
#serviceOrderModal .order-client>.form-label,#serviceOrderModal .order-dates .form-label,#serviceOrderModal .animal-editor__fields>label{font-size:.74rem}
#serviceOrderModal .order-client .form-select,#serviceOrderModal .order-client__new,#serviceOrderModal .order-dates .form-control,#serviceOrderModal .animal-editor__fields .form-control,#serviceOrderModal .animal-editor__fields .form-select{font-size:.84rem}
#serviceOrderModal .animal-editor__identity-copy strong{font-size:.86rem}
#serviceOrderModal .animal-editor__identity-copy small{font-size:.73rem}
#serviceOrderModal .animal-services__head>strong,#serviceOrderModal .animal-service-row label,#serviceOrderModal .animal-service-row .small{font-size:.74rem!important}
#serviceOrderModal .animal-service-row label:last-of-type::after{font-size:.74rem}
#serviceOrderModal .animal-service-type,#serviceOrderModal .animal-service-row .form-control,#serviceOrderModal .animal-service-row .form-select{font-size:.82rem!important}
#serviceOrderModal .animal-service-add .btn,#serviceOrderModal .order-add-pet>#addOrderAnimal{font-size:.78rem!important}
#serviceOrderModal .order-comment .form-label{font-size:.76rem}
#serviceOrderModal .order-comment .form-control{font-size:.84rem}
#serviceOrderModal .order-summary{font-size:.76rem}
#serviceOrderModal .order-summary strong{font-size:.86rem}
#serviceOrderModal .order-modal__footer .btn{font-size:.8rem}
</style>@endpush
@push('styles')<style>
/* The editor now uses a comfortable desktop scale instead of a compressed sheet. */
#serviceOrderModal .modal-dialog{max-width:980px}
#serviceOrderModal .order-modal__body{padding:16px 18px 18px}
#serviceOrderModal .order-client-summary{min-height:50px;padding:0 14px}
#serviceOrderModal .order-details__grid{padding:12px 14px 14px;gap:12px}
#serviceOrderModal .order-client__controls{grid-template-columns:minmax(0,1fr) 148px;gap:10px}
#serviceOrderModal .order-client .form-select,#serviceOrderModal .order-client__new,#serviceOrderModal .order-dates .form-control{height:36px;line-height:1.25}
#serviceOrderModal .order-dates{grid-template-columns:160px 160px minmax(0,1fr);gap:10px}
#serviceOrderModal .animal-editor__identity{min-height:50px;padding:8px 14px}
#serviceOrderModal .animal-editor__identity .animal-editor__avatar{width:34px!important;height:34px!important;flex-basis:34px!important}
#serviceOrderModal .animal-editor__fields{padding:11px 14px!important;gap:10px!important}
#serviceOrderModal .animal-editor__fields .form-control,#serviceOrderModal .animal-editor__fields .form-select{height:36px;line-height:1.25}
#serviceOrderModal .animal-editor__quick-actions .btn,#serviceOrderModal .animal-editor__remove{width:36px!important;height:36px!important}
#serviceOrderModal .animal-editor__services{padding:10px 14px 13px!important}
#serviceOrderModal .animal-service-add .btn,#serviceOrderModal .order-add-pet>#addOrderAnimal{height:31px!important;padding:0 10px!important}
#serviceOrderModal .animal-service-row{grid-template-columns:150px 150px 165px minmax(96px,1fr) 36px!important;gap:9px;padding-top:8px!important}
#serviceOrderModal .animal-service-type,#serviceOrderModal .animal-service-row .form-control,#serviceOrderModal .animal-service-row .form-select,#serviceOrderModal .animal-service-row>button{height:36px!important;line-height:1.25}
#serviceOrderModal .order-comment{padding:4px 14px 0!important}
#serviceOrderModal .order-comment .form-control{min-height:46px;padding:9px 10px}
#serviceOrderModal .order-summary{min-height:38px;margin-top:12px}
#serviceOrderModal .order-modal__footer{padding:13px 18px}
@media(max-width:767px){#serviceOrderModal .modal-dialog{max-width:none}.order-client__controls,.order-dates{grid-template-columns:1fr!important}.animal-editor__fields{padding:10px!important}.animal-service-row{grid-template-columns:minmax(0,1fr) minmax(0,1fr) 36px!important}.animal-service-type{grid-column:1/-1}}
</style>@endpush
@push('styles')<style>
.orders-workspace .orders-list-footer{display:flex;flex-wrap:wrap;align-items:center;justify-content:flex-start;gap:12px 18px}
.orders-workspace .orders-list-footer form{display:inline-flex;align-items:center;gap:8px;margin:0}
.orders-workspace .orders-list-footer label{margin:0;font-weight:700}
.orders-workspace .orders-list-footer select{width:auto;min-width:78px;height:34px;border-color:#d8e2ec;font-size:.78rem}
.orders-workspace .orders-list-footer .pagination{margin:0}
.orders-workspace .orders-list-footer__pagination{min-width:0;max-width:100%;overflow-x:auto;overflow-y:hidden}
.orders-workspace .orders-list-footer__pagination{margin-left:auto}
.orders-workspace .orders-list-footer__pagination .pagination{flex-wrap:nowrap;width:max-content}
@media(max-width:575px){.orders-workspace .orders-list-footer{align-items:stretch;flex-direction:column}.orders-workspace .orders-list-footer form{justify-content:space-between}.orders-workspace .orders-list-footer__pagination{margin-left:0}.orders-workspace .orders-list-footer .pagination{justify-content:center}}
</style>@endpush
@endsection
