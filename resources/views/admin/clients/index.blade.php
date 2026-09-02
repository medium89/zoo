@extends('admin.index')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Клиенты</h1>
        <button type="button" class="btn btn-primary" id="newClientWithPets"><i class="fa fa-plus me-1"></i>Добавить клиента</button>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <x-admin.filters :action="route('admin.clients.index')" :filters="$filters" placeholder="Имя, телефон или адрес">
        <label class="admin-filter-bar__field">Питомцы<select name="animals" class="form-select"><option value="">Все</option><option value="with" @selected(($filters['animals'] ?? '') === 'with')>Есть питомцы</option><option value="without" @selected(($filters['animals'] ?? '') === 'without')>Без питомцев</option></select></label>
        <label class="admin-filter-bar__field">Адрес<select name="address" class="form-select"><option value="">Все</option><option value="with" @selected(($filters['address'] ?? '') === 'with')>Указан</option><option value="without" @selected(($filters['address'] ?? '') === 'without')>Не указан</option></select></label>
    </x-admin.filters>

    @if($mapClients->isNotEmpty())
        <section class="card mb-4 client-map-card">
            <div class="card-header d-flex justify-content-between align-items-center gap-2">
                <span>Клиенты на карте</span>
                <span class="badge text-bg-light">{{ $mapClients->count() }}</span>
            </div>
            <div class="card-body p-0">
                @if($yandexMapsKey)
                    <div class="client-map-wrap">
                        <div id="clientsMap" class="client-map" aria-label="Карта клиентов"></div>
                        <div id="clientsMapStatus" class="client-map-status" role="status">Загружаем карту…</div>
                    </div>
                @else
                    <div class="p-3 text-muted">Чтобы показать адреса на карте, добавьте <code>YANDEX_MAPS_API_KEY</code> в файл <code>.env</code>.</div>
                @endif
            </div>
        </section>
    @endif

    @if($clients->count())
        <div class="admin-grid" style="--grid-cols: 64px 54px 1.3fr 1fr 120px 120px 170px;">
            <div class="admin-grid-header">
                <div>#</div>
                <div></div>
                <div>Клиент</div>
                <div>Телефон</div>
                <div>Питомцы</div>
                <div>Записи</div>
                <div class="text-end">Действия</div>
            </div>
            <div class="admin-grid-body">
                @foreach($clients as $client)
                    <div class="admin-grid-row">
                        <div class="text-muted">{{ $client->id }}</div>
                        <div><img src="{{ $client->avatarUrl() }}" alt="{{ $client->name }}" class="client-list-avatar"></div>
                        <div>
                            <a href="{{ route('admin.clients.show', $client) }}">{{ $client->name }}</a>
                            @include('admin.partials.tags-list', ['tags' => $client->tags])
                        </div>
                        <div>{{ $client->phone ?: '—' }}</div>
                        <div>{{ $client->animals_count }}</div>
                        <div>{{ $client->boardings_count }}</div>
                        <div class="actions">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-eye"></i></a>
                                <button type="button" class="btn btn-sm btn-primary text-white js-edit-client-with-pets" data-client='@json($clientsPayload[$client->id])' aria-label="Редактировать клиента"><i class="fa fa-pen"></i></button>
                                <form action="{{ route('admin.clients.destroy', $client) }}" method="POST" class="d-inline js-delete-form" data-confirm="Удалить клиента? Связанные питомцы и записи останутся без хозяина.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        {{ $clients->links() }}
    @else
        <div class="text-muted">Клиентов пока нет.</div>
    @endif
</div>

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
#clientCreateModal .modal-dialog{max-width:980px}.client-create-modal{overflow:hidden;border:0;border-radius:12px;box-shadow:0 18px 60px rgba(15,35,60,.18)}.client-create-modal__header{align-items:flex-start;padding:17px 20px;border-bottom:1px solid #e8edf3}.client-create-modal__header .btn-close{margin:2px 0 0 auto}.client-create-modal__eyebrow{margin-bottom:5px;color:#5d7893;font-size:.72rem;font-weight:800;letter-spacing:.04em;text-transform:uppercase}.client-create-modal__eyebrow i{color:#3178c6}.client-create-modal .modal-title{color:#2e4054;font-size:1.05rem;font-weight:800}.client-create-modal__body{padding:18px 20px 22px}.client-create-section{padding:16px;border:1px solid #dfe7f0;border-radius:11px}.client-create-section+.client-create-section{margin-top:16px}.client-create-section__title{color:#304255;font-size:.82rem;font-weight:800}.client-create-fields{display:grid;grid-template-columns:1fr 1fr;gap:13px;margin-top:14px}.client-create-fields label,.client-animal-editor label{display:block;margin:0;color:#68788b;font-size:.75rem;font-weight:700}.client-create-fields b{color:#d9534f}.client-create-fields .form-control,.client-animal-editor .form-control,.client-animal-editor .form-select{height:40px;margin-top:5px;border-color:#d9e3ec;border-radius:8px;font-size:.84rem;box-shadow:none}.client-create-fields input[type=file].form-control{height:auto;padding:7px}.client-create-fields textarea.form-control{height:auto;min-height:58px;padding-top:8px}.client-create-fields__wide{grid-column:1/-1}.client-create-pets{background:#fbfcfe}.client-create-pets__head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px}.client-create-pets__head p{margin:0;color:#7d8c9d;font-size:.78rem}.client-create-pets__list{display:grid;gap:10px;margin-top:13px}.client-animal-editor{display:grid;grid-template-columns:minmax(0,1.3fr) minmax(130px,.7fr) 34px;gap:10px;align-items:end;padding:12px 14px;border:1px solid #dfe7f0;border-radius:10px;background:#fff}.client-animal-editor .btn{width:34px;height:40px;padding:0;display:grid;place-items:center}.client-animal-editor__hint{grid-column:1/-1;margin-top:-4px;color:#8b99a8;font-size:.7rem}.client-create-modal__footer{gap:9px;padding:14px 20px;border-top:1px solid #e8edf3}.client-create-modal__footer .btn{min-height:39px;font-size:.82rem;font-weight:700}.client-list-avatar{width:38px;height:38px;object-fit:cover;border-radius:50%;background:#eaf3ff}@media(max-width:575px){#clientCreateModal .modal-dialog{margin:8px}.client-create-modal__header,.client-create-modal__body{padding-right:13px;padding-left:13px}.client-create-modal__footer{padding-right:13px;padding-left:13px}.client-create-fields{grid-template-columns:1fr}.client-create-fields__wide{grid-column:auto}.client-animal-editor{grid-template-columns:minmax(0,1fr) 34px;padding:10px}.client-animal-editor label:nth-child(2){grid-column:1/-1;grid-row:2}.client-animal-editor .btn{grid-column:2;grid-row:1}.client-create-modal__footer .btn{flex:1;padding-right:8px;padding-left:8px}}
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
    document.querySelectorAll('.js-edit-client-with-pets').forEach(button => button.addEventListener('click', () => {
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
