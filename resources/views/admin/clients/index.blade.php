@extends('admin.index')

@section('content')
<section class="clients-workspace">
    <header class="clients-workspace__header">
        <h1 class="visually-hidden">Клиенты</h1>
        <button type="button" class="btn btn-primary clients-workspace__create d-none" id="newClientWithPets"><i class="fa fa-plus" aria-hidden="true"></i><span>Новый клиент</span></button>
    </header>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <x-admin.filters :action="route('admin.clients.index')" :filters="$filters" placeholder="Имя, телефон или адрес" :auto="true">
        <label class="admin-filter-bar__field">Питомцы<select name="animals" class="form-select"><option value="">Все</option><option value="with" @selected(($filters['animals'] ?? '') === 'with')>Есть питомцы</option><option value="without" @selected(($filters['animals'] ?? '') === 'without')>Без питомцев</option></select></label>
        <label class="admin-filter-bar__field">Адрес<select name="address" class="form-select"><option value="">Все</option><option value="with" @selected(($filters['address'] ?? '') === 'with')>Указан</option><option value="without" @selected(($filters['address'] ?? '') === 'without')>Не указан</option></select></label>
    </x-admin.filters>

    @if($clients->count())
        <section class="clients-workspace__list" aria-label="Список клиентов">
            <div class="admin-entity-list" style="--entity-cols: 54px minmax(180px,1.25fr) minmax(130px,1fr) 90px 90px 150px; --entity-cols-mobile: 54px minmax(0,1fr) 120px;">
                <div class="admin-entity-list__head">
                    <div></div>
                    <div>Клиент</div>
                    <div>Телефон</div>
                    <div>Питомцы</div>
                    <div>Записи</div>
                    <div class="text-end">Действия</div>
                </div>
                <div class="admin-entity-list__body">
                    @foreach($clients as $client)
                        <div class="admin-entity-list__row">
                            <div><img src="{{ $client->avatarUrl() }}" alt="{{ $client->name }}" class="admin-entity-list__avatar client-list-avatar"></div>
                            <div class="admin-entity-list__primary" data-label="Клиент">
                                <a href="{{ route('admin.clients.show', $client) }}">{{ $client->name }}</a>
                                @include('admin.partials.tags-list', ['tags' => $client->tags])
                            </div>
                            <div class="admin-entity-list__muted" data-label="Телефон">{{ $client->phone ?: '—' }}</div>
                            <div data-label="Питомцы" @if(!$client->animals_count) aria-label="Питомцев пока нет" @endif>{{ $client->animals_count ?: 'Нет' }}</div>
                            <div data-label="Записи">{{ $client->boardings_count ?: 'Нет' }}</div>
                            <div class="admin-entity-list__actions actions" data-label="Действия">
                                <x-admin.actions-menu label="Действия с клиентом {{ $client->name }}">
                                    <a href="{{ route('admin.clients.show', $client) }}" class="admin-actions-menu__item"><i class="fa fa-eye" aria-hidden="true"></i><span>Просмотреть</span></a>
                                    <button type="button" class="admin-actions-menu__item js-edit-client-with-pets" data-client='@json($clientsPayload[$client->id])'><i class="fa fa-pen" aria-hidden="true"></i><span>Редактировать</span></button>
                                    <form action="{{ route('admin.clients.destroy', $client) }}" method="POST" class="js-delete-form" data-confirm="Удалить клиента? Связанные питомцы и записи останутся без хозяина.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="admin-actions-menu__item admin-actions-menu__item--danger"><i class="fa fa-trash" aria-hidden="true"></i><span>Удалить</span></button>
                                    </form>
                                </x-admin.actions-menu>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        <footer class="admin-entity-list__footer">
            <span>Показано {{ $clients->firstItem() }}–{{ $clients->lastItem() }} из {{ $clients->total() }} {{ trans_choice('клиента|клиентов|клиентов', $clients->total()) }}</span>
            <form method="GET" action="{{ route('admin.clients.index') }}">
                @foreach(request()->except(['per_page', 'page']) as $key => $value)
                    @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                @endforeach
                <label for="clientsPerPage">На странице</label>
                <select id="clientsPerPage" name="per_page" class="form-select" onchange="this.form.submit()">
                    @foreach([10, 25, 50, 100] as $option)<option value="{{ $option }}" @selected((int) request('per_page', 25) === $option)>{{ $option }}</option>@endforeach
                </select>
            </form>
            <div class="admin-entity-list__footer-pagination">{{ $clients->onEachSide(1)->links('pagination::bootstrap-4') }}</div>
        </footer>
    @elseif(collect($filters)->except(['per_page', 'page'])->filter(fn ($value) => filled($value))->isNotEmpty())
        <section class="clients-workspace__empty" aria-labelledby="clientsEmptyFilteredTitle"><i class="fa fa-magnifying-glass" aria-hidden="true"></i><h2 id="clientsEmptyFilteredTitle">Ничего не нашли</h2><p>Попробуйте изменить запрос или снять часть фильтров.</p><a href="{{ route('admin.clients.index') }}" class="btn btn-outline-primary">Сбросить фильтры</a></section>
    @else
        <section class="clients-workspace__empty" aria-labelledby="clientsEmptyTitle"><i class="fa fa-user-group" aria-hidden="true"></i><h2 id="clientsEmptyTitle">Клиентов пока нет</h2><p>Добавьте первого клиента и его питомцев с помощью кнопки «+».</p></section>
    @endif

    @if($mapClients->isNotEmpty())
        <section class="clients-workspace__map client-map-card" aria-labelledby="clientsMapTitle">
            <div class="clients-workspace__map-head"><div><h2 id="clientsMapTitle">Клиенты на карте</h2><p>Показаны клиенты с заполненным адресом.</p></div><span class="clients-workspace__map-count">{{ $mapClients->count() }}</span></div>
            @if($yandexMapsKey)
                <div class="client-map-wrap">
                    <div id="clientsMap" class="client-map" aria-label="Карта клиентов"></div>
                    <div id="clientsMapStatus" class="client-map-status" role="status">Загружаем карту…</div>
                </div>
            @else
                <div class="clients-workspace__map-empty">Чтобы показать адреса на карте, добавьте <code>YANDEX_MAPS_API_KEY</code> в файл <code>.env</code>.</div>
            @endif
        </section>
    @endif
</section>
<x-admin.fab label="Добавить клиента" target="#newClientWithPets" />

<div class="modal fade admin-modal" id="clientCreateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable client-create-dialog">
        <form class="modal-content client-create-modal" action="{{ route('admin.clients.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" id="clientCreateMethod" value="POST">
            <div class="modal-header client-create-modal__header">
                <div><div class="client-create-modal__eyebrow"><i class="fa fa-user-plus"></i> Клиенты</div><h5 class="modal-title" id="clientCreateTitle">Новый клиент</h5></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body client-create-modal__body">
                <section class="client-create-section">
                    <div class="client-create-section__title">Данные клиента</div>
                    <div class="client-create-fields">
                        <label>Имя или ФИО <b>*</b><input class="form-control" name="name" required autocomplete="name" placeholder="Например, Анастасия Иванова"></label>
                        <label>Телефон<input class="form-control" name="phone" autocomplete="tel" placeholder="+7 999 123-45-67"></label>
                        <label class="client-create-fields__wide">Адрес<input class="form-control" name="address" autocomplete="street-address" placeholder="Улица, дом, квартира" data-address-suggest></label>
                        <label class="client-create-fields__wide">Комментарий<textarea class="form-control" name="note" rows="2" placeholder="Важные детали о клиенте"></textarea></label>
                        <label class="client-create-fields__wide">Фото клиента<input class="form-control" type="file" name="photos[]" accept="image/*" multiple></label>
                    </div>
                </section>
                <section class="client-create-section client-create-pets">
                    <div class="client-create-pets__head"><div><div class="client-create-section__title mb-1">Питомцы</div><p>Добавьте одного или несколько питомцев сразу.</p></div><button type="button" class="btn btn-outline-primary btn-sm" id="addClientAnimal"><i class="fa fa-plus"></i> Питомец</button></div>
                    <div id="clientCreateAnimals" class="client-create-pets__list"></div>
                </section>
            </div>
            <div class="modal-footer client-create-modal__footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button><button class="btn btn-primary"><i class="fa fa-check me-1"></i>Создать клиента</button></div>
        </form>
    </div>
</div>

@if($mapClients->isNotEmpty() && $yandexMapsKey)
    @push('styles')
    <style>.client-map-wrap{position:relative}.client-map{height:420px;width:100%;border-radius:0 0 .375rem .375rem;overflow:hidden}.client-map-status{position:absolute;inset:0;display:grid;place-items:center;padding:20px;background:#fff;color:#6b7b8d;font-size:.88rem;text-align:center}.client-map-status.is-hidden{display:none}@media(max-width:767px){.client-map{height:320px}}</style>
    @endpush
    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const clients = @json($mapClientsPayload);
        const container = document.getElementById('clientsMap');
        const status = document.getElementById('clientsMapStatus');
        const setStatus = (message = '') => {
            if (!status) return;
            status.textContent = message;
            status.classList.toggle('is-hidden', message === '');
        };
        const initMap = () => {
            if (!window.ymaps || !container || container.dataset.ready === '1') return;
            ymaps.ready(() => {
                try {
                    container.dataset.ready = '1';
                    const map = new ymaps.Map('clientsMap', {center: [53.3474, 83.7783], zoom: 10, controls: ['zoomControl', 'fullscreenControl']});
                    setStatus();
                    const cluster = new ymaps.Clusterer({preset: 'islands#blueClusterIcons'});
                    const points = [];
                    let completed = 0;
                    clients.forEach((client) => {
                        ymaps.geocode(client.address, {results: 1}).then((result) => {
                            const geoObject = result.geoObjects.get(0);
                            if (!geoObject) return;
                            const point = new ymaps.Placemark(geoObject.geometry.getCoordinates(), {
                                balloonContentHeader: client.name,
                                balloonContentBody: `${client.address}${client.phone ? `<br>${client.phone}` : ''}`,
                                hintContent: client.name,
                            });
                            points.push(point);
                            cluster.add(point);
                        }, () => {}).then(() => {
                            completed += 1;
                            if (completed === clients.length && points.length) {
                                map.geoObjects.add(cluster);
                                map.setBounds(cluster.getBounds(), {checkZoomRange: true, zoomMargin: 36});
                            }
                        });
                    });
                } catch (_) {
                    setStatus('Не удалось создать карту. Проверьте настройки ключа Яндекс.Карт.');
                }
            });
        };
        const script = document.createElement('script');
        script.src = 'https://api-maps.yandex.ru/2.1/?apikey={{ urlencode($yandexMapsKey) }}&lang=ru_RU{{ $yandexSuggestKey ? '&suggest_apikey='.urlencode($yandexSuggestKey).'&load=SuggestView' : '' }}';
        script.async = true;
        script.onload = initMap;
        script.onerror = () => setStatus('Не удалось загрузить Яндекс.Карты. Проверьте интернет, VPN или ограничения ключа для zooland22.ru.');
        document.head.append(script);
    });
    </script>
    @endpush
@endif
@endsection

@push('styles')
<style>
.client-workspace-card__pets--empty{color:#8a99a8;font-size:.74rem;font-weight:650}
.clients-workspace{padding-bottom:32px}.clients-workspace__header{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;margin:0 0 22px}.clients-workspace__eyebrow{margin:0 0 4px;color:#7990a6;font-size:.72rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.clients-workspace__title-row{display:flex;align-items:center;gap:10px}.clients-workspace h1{margin:0;color:#27384b;font-size:clamp(1.65rem,3vw,2.1rem);font-weight:800;letter-spacing:-.035em}.clients-workspace__count,.clients-workspace__map-count{display:inline-grid;place-items:center;min-width:28px;height:28px;padding:0 8px;border-radius:999px;background:#edf4fb;color:#52708d;font-size:.78rem;font-weight:800}.clients-workspace__create{display:inline-flex;align-items:center;gap:8px;min-height:42px;padding:0 15px;font-size:.86rem;font-weight:750;white-space:nowrap}.clients-workspace .admin-filter-bar{margin-bottom:20px}.clients-workspace__list{border:1px solid #e0e8f0;border-radius:16px;background:#fff;box-shadow:0 8px 22px rgba(40,66,92,.045);overflow:visible}.clients-workspace__list-head{display:flex;justify-content:space-between;gap:12px;padding:16px 18px;border-bottom:1px solid #e9eef4;color:#35485d;font-size:.88rem;font-weight:800}.clients-workspace__list-head span:last-child{color:#8190a0;font-size:.75rem;font-weight:700}.clients-card-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1px;background:#e9eef4}.client-workspace-card{position:relative;min-width:0;padding:17px 18px;background:#fff;transition:background .18s ease,box-shadow .18s ease}.client-workspace-card:hover{background:#fcfdff;box-shadow:inset 0 0 0 1px #dceafa}.client-workspace-card__top{display:flex;align-items:flex-start;gap:11px}.client-workspace-card__avatar{width:42px;height:42px;flex:0 0 42px;border-radius:13px;object-fit:cover;background:#eef4fa}.client-workspace-card__identity{display:grid;min-width:0;gap:3px;padding-top:1px}.client-workspace-card__name{overflow:hidden;color:#2b4056;font-size:.96rem;font-weight:800;line-height:1.25;text-decoration:none;text-overflow:ellipsis;white-space:nowrap}.client-workspace-card__name:hover{color:#1769c2;text-decoration:underline}.client-workspace-card__phone,.client-workspace-card__muted{overflow:hidden;color:#708398;font-size:.78rem;line-height:1.25;text-decoration:none;text-overflow:ellipsis;white-space:nowrap}.client-workspace-card__phone:hover{color:#1769c2;text-decoration:underline}.client-card-menu{position:relative;margin-left:auto;flex:0 0 auto}.client-card-menu__toggle{display:grid;width:32px;height:32px;padding:0;place-items:center;border:0;border-radius:9px;color:#718399;background:transparent}.client-card-menu__toggle:hover,.client-card-menu__toggle[aria-expanded=true]{color:#3479bd;background:#edf5fd}.client-card-menu__popover{position:absolute;z-index:20;top:calc(100% + 5px);right:0;width:164px;padding:5px;border:1px solid #dfe8f1;border-radius:11px;background:#fff;box-shadow:0 14px 30px rgba(31,54,79,.18)}.client-card-menu__item{display:flex;width:100%;align-items:center;gap:9px;padding:8px 9px;border:0;border-radius:7px;background:transparent;color:#42586e;font-size:.79rem;font-weight:700;line-height:1.25;text-align:left;text-decoration:none}.client-card-menu__item:hover,.client-card-menu__item:focus-visible{background:#eef5fc;color:#256cae}.client-card-menu__item--danger{color:#c74750}.client-card-menu__item--danger:hover,.client-card-menu__item--danger:focus-visible{background:#fff0f0;color:#b73b45}.client-workspace-card__tags{min-height:0;margin:12px 0 0}.client-workspace-card__tags .admin-tags{gap:4px}.client-workspace-card__address{display:flex;gap:7px;margin:12px 0 0;color:#6f8194;font-size:.78rem;line-height:1.4}.client-workspace-card__address i{margin-top:2px;color:#8aa5bf}.client-workspace-card__pets{display:flex;flex-wrap:wrap;gap:5px;margin:12px 0 0}.client-workspace-card__pet{max-width:100%;overflow:hidden;padding:4px 8px;border:1px solid #dbe9f4;border-radius:999px;background:#f6faff;color:#52708d;font-size:.72rem;font-weight:750;line-height:1.1;text-overflow:ellipsis;white-space:nowrap}.client-workspace-card__pet--more{background:#f1f5f8;color:#657b8e}.client-workspace-card__records{display:flex;align-items:center;gap:6px;margin:13px 0 0;color:#8090a0;font-size:.73rem;font-weight:700}.client-workspace-card__records i{color:#6d9dce}.clients-workspace__pagination{display:flex;justify-content:center;margin:20px 0 0}.clients-workspace__pagination .pagination{margin:0}.clients-workspace__empty{display:grid;justify-items:center;padding:56px 20px;border:1px dashed #d7e3ee;border-radius:16px;background:#fbfdff;color:#718397;text-align:center}.clients-workspace__empty>i{margin:0 0 12px;color:#78a6d1;font-size:1.65rem}.clients-workspace__empty h2{margin:0;color:#40566c;font-size:1.05rem;font-weight:800}.clients-workspace__empty p{max-width:380px;margin:7px 0 17px;font-size:.85rem}.clients-workspace__empty .btn{display:inline-flex;align-items:center;gap:7px;font-size:.82rem;font-weight:750}.clients-workspace__map{margin-top:28px;border:1px solid #e0e8f0;border-radius:16px;background:#fff;box-shadow:0 8px 22px rgba(40,66,92,.045);overflow:hidden}.clients-workspace__map-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:18px}.clients-workspace__map-head h2{margin:0;color:#35495e;font-size:1rem;font-weight:800}.clients-workspace__map-head p{margin:4px 0 0;color:#7a8b9d;font-size:.78rem}.clients-workspace__map-empty{padding:18px;border-top:1px solid #e9eef4;color:#6f8194;font-size:.82rem}.clients-workspace .client-map{border-radius:0;height:380px}.clients-workspace .client-map-status{font-size:.84rem}
@media(max-width:1199px){.clients-card-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:767px){.clients-workspace__header{align-items:flex-start;gap:14px;margin-bottom:17px}.clients-workspace__create{min-height:39px;padding:0 11px;font-size:.78rem}.clients-workspace__list-head{padding:14px}.clients-card-grid{grid-template-columns:1fr}.client-workspace-card{padding:15px 14px}.clients-workspace .client-map{height:320px}.clients-workspace__map{margin-top:20px}}@media(max-width:390px){.clients-workspace__header{flex-direction:column}.clients-workspace__create{width:100%;justify-content:center}.client-workspace-card__avatar{width:39px;height:39px;flex-basis:39px}.client-workspace-card__name{max-width:170px}.client-card-menu__popover{right:-2px}}
#clientCreateModal .modal-dialog{max-width:980px}.client-create-modal{overflow:hidden;border:0;border-radius:12px;box-shadow:0 18px 60px rgba(15,35,60,.18)}.client-create-modal__header{align-items:flex-start;padding:17px 20px;border-bottom:1px solid #e8edf3}.client-create-modal__header .btn-close{margin:2px 0 0 auto}.client-create-modal__eyebrow{margin-bottom:5px;color:#5d7893;font-size:.72rem;font-weight:800;letter-spacing:.04em;text-transform:uppercase}.client-create-modal__eyebrow i{color:#3178c6}.client-create-modal .modal-title{color:#2e4054;font-size:1.05rem;font-weight:800}.client-create-modal__body{padding:18px 20px 22px}.client-create-section{padding:16px;border:1px solid #dfe7f0;border-radius:11px}.client-create-section+.client-create-section{margin-top:16px}.client-create-section__title{color:#304255;font-size:.82rem;font-weight:800}.client-create-fields{display:grid;grid-template-columns:1fr 1fr;gap:13px;margin-top:14px}.client-create-fields label,.client-animal-editor label{display:block;margin:0;color:#68788b;font-size:.75rem;font-weight:700}.client-create-fields b{color:#d9534f}.client-create-fields .form-control,.client-animal-editor .form-control,.client-animal-editor .form-select{height:40px;margin-top:5px;border-color:#d9e3ec;border-radius:8px;font-size:.84rem;box-shadow:none}.client-create-fields input[type=file].form-control{height:auto;padding:7px}.client-create-fields textarea.form-control{height:auto;min-height:58px;padding-top:8px}.client-create-fields__wide{grid-column:1/-1}.client-create-pets{background:#fbfcfe}.client-create-pets__head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px}.client-create-pets__head p{margin:0;color:#7d8c9d;font-size:.78rem}.client-create-pets__list{display:grid;gap:10px;margin-top:13px}.client-animal-editor{display:grid;grid-template-columns:minmax(0,1.3fr) minmax(130px,.7fr) 34px;gap:10px;align-items:end;padding:12px 14px;border:1px solid #dfe7f0;border-radius:10px;background:#fff}.client-animal-editor .btn{width:34px;height:40px;padding:0;display:grid;place-items:center}.client-animal-editor__hint{grid-column:1/-1;margin-top:-4px;color:#8b99a8;font-size:.7rem}.client-create-modal__footer{gap:9px;padding:14px 20px;border-top:1px solid #e8edf3}.client-create-modal__footer .btn{min-height:39px;font-size:.82rem;font-weight:700}.client-list-avatar{width:38px;height:38px;object-fit:cover;border-radius:50%;background:#eaf3ff}@media(max-width:575px){#clientCreateModal .modal-dialog{margin:8px}.client-create-modal__header,.client-create-modal__body{padding-right:13px;padding-left:13px}.client-create-modal__footer{padding-right:13px;padding-left:13px}.client-create-fields{grid-template-columns:1fr}.client-create-fields__wide{grid-column:auto}.client-animal-editor{grid-template-columns:minmax(0,1fr) 34px;padding:10px}.client-animal-editor label:nth-child(2){grid-column:1/-1;grid-row:2}.client-animal-editor .btn{grid-column:2;grid-row:1}.client-create-modal__footer .btn{flex:1;padding-right:8px;padding-left:8px}}
</style>
@endpush

@push('styles')
<style>
    /* The shared entity list is the visual shell; this wrapper only keeps map/list spacing. */
    .clients-workspace__list { border: 0; border-radius: 0; background: transparent; box-shadow: none; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modalElement = document.getElementById('clientCreateModal');
    const openButton = document.getElementById('newClientWithPets');
    const addButton = document.getElementById('addClientAnimal');
    const root = document.getElementById('clientCreateAnimals');
    if (!modalElement || !openButton || !addButton || !root) return;

    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const animals = @json($animalsPayload);
    const categories = @json($categories->map(fn ($category) => ['id' => $category->id, 'name' => $category->name])->values());
    const dataList = document.createElement('datalist');
    dataList.id = 'clientAnimalsList';
    animals.forEach(animal => dataList.append(new Option(animal.name)));
    document.body.append(dataList);
    let position = 0;

    const field = (caption, input) => {
        const label = document.createElement('label');
        label.append(caption);
        label.append(input);
        return label;
    };
    const addAnimal = (data = {}) => {
        const index = position++;
        const row = document.createElement('div');
        row.className = 'client-animal-editor';
        const name = document.createElement('input');
        name.className = 'form-control js-client-animal-name';
        name.name = 'animals[' + index + '][name]';
        name.placeholder = 'Кличка или новый питомец';
        name.autocomplete = 'off';
        name.setAttribute('list', 'clientAnimalsList');
        name.value = data.name || '';
        const animalId = document.createElement('input');
        animalId.type = 'hidden';
        animalId.name = 'animals[' + index + '][animal_id]';
        animalId.value = data.id || '';
        const category = document.createElement('select');
        category.className = 'form-select';
        category.name = 'animals[' + index + '][category_id]';
        category.append(new Option('Вид не указан', ''));
        categories.forEach(item => category.append(new Option(item.name, item.id)));
        category.value = data.category_id || '';
        const remove = document.createElement('button');
        remove.className = 'btn btn-danger';
        remove.type = 'button';
        remove.setAttribute('aria-label', 'Убрать питомца');
        remove.innerHTML = '<i class="fa fa-xmark"></i>';
        const forceNew = document.createElement('input');
        forceNew.type = 'checkbox';
        forceNew.className = 'form-check-input me-1';
        const forceNewLabel = document.createElement('label');
        forceNewLabel.className = 'client-animal-editor__hint';
        forceNewLabel.append(forceNew, ' Новый питомец, даже если кличка уже есть в базе');
        const syncSavedAnimal = () => {
            const needle = name.value.trim().toLocaleLowerCase();
            const found = !forceNew.checked && animals.find(animal => animal.name.toLocaleLowerCase() === needle);
            animalId.value = found ? found.id : '';
            if (found && found.category_id) category.value = found.category_id;
        };
        name.addEventListener('input', syncSavedAnimal);
        forceNew.addEventListener('change', syncSavedAnimal);
        remove.addEventListener('click', () => row.remove());
        row.append(field('Питомец', name), animalId, field('Вид', category), remove, forceNewLabel);
        root.append(row);
    };

    addButton.addEventListener('click', addAnimal);
    const openCreate = () => {
        const form = modalElement.querySelector('form');
        form.reset();
        form.action = '{{ route('admin.clients.store') }}';
        document.getElementById('clientCreateMethod').value = 'POST';
        document.getElementById('clientCreateTitle').textContent = 'Новый клиент';
        root.replaceChildren();
        position = 0;
        addAnimal();
        modal.show();
    };
    openButton.addEventListener('click', openCreate);
    document.querySelector('.js-open-first-client')?.addEventListener('click', openCreate);

    const closeClientMenus = (except = null) => {
        document.querySelectorAll('.client-card-menu').forEach(menu => {
            if (menu === except) return;
            const toggle = menu.querySelector('.client-card-menu__toggle');
            const popover = menu.querySelector('.client-card-menu__popover');
            toggle?.setAttribute('aria-expanded', 'false');
            if (popover) popover.hidden = true;
        });
    };
    document.querySelectorAll('.client-card-menu__toggle').forEach(toggle => toggle.addEventListener('click', (event) => {
        event.stopPropagation();
        const menu = toggle.closest('.client-card-menu');
        const popover = menu?.querySelector('.client-card-menu__popover');
        if (!menu || !popover) return;
        const willOpen = popover.hidden;
        closeClientMenus(menu);
        popover.hidden = !willOpen;
        toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        if (willOpen) popover.querySelector('a, button')?.focus();
    }));
    document.addEventListener('click', event => {
        if (!event.target.closest('.client-card-menu')) closeClientMenus();
    });
    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;
        const openMenu = [...document.querySelectorAll('.client-card-menu__popover')].find(popover => !popover.hidden);
        if (!openMenu) return;
        const toggle = openMenu.closest('.client-card-menu')?.querySelector('.client-card-menu__toggle');
        closeClientMenus();
        toggle?.focus();
    });
    document.querySelectorAll('.js-edit-client-with-pets').forEach(button => button.addEventListener('click', () => {
        closeClientMenus();
        const data = JSON.parse(button.dataset.client);
        const form = modalElement.querySelector('form');
        form.reset();
        form.action = '{{ url('/zooadmin/clients') }}/' + data.id;
        document.getElementById('clientCreateMethod').value = 'PUT';
        document.getElementById('clientCreateTitle').textContent = 'Редактировать клиента';
        form.querySelector('[name="name"]').value = data.name || '';
        form.querySelector('[name="phone"]').value = data.phone || '';
        form.querySelector('[name="address"]').value = data.address || '';
        form.querySelector('[name="note"]').value = data.note || '';
        root.replaceChildren();
        position = 0;
        (data.animals || []).forEach(addAnimal);
        modal.show();
    }));
});
</script>
@endpush
