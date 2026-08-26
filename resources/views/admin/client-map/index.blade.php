@extends('admin.index')

@section('content')
<div class="container-fluid client-node-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div><h1 class="mb-1">Карта клиентов</h1><p class="text-muted mb-0">Перетащите питомца на клиента, чтобы связать их.</p></div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#newMapAnimalModal"><i class="fa fa-plus me-1"></i>Питомец</button>
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#newMapClientModal"><i class="fa fa-plus me-1"></i>Клиент</button>
        </div>
    </div>

    <div class="client-node-toolbar">
        <span><i class="fa fa-arrows-up-down-left-right"></i> Тяните ноды за шапку</span><span><i class="fa fa-link"></i> Крестик на линии разрывает связь</span>
        <div class="client-node-zoom ms-sm-auto" aria-label="Масштаб карты">
            <button class="btn btn-sm btn-light" type="button" id="clientMapZoomOut" aria-label="Уменьшить масштаб"><i class="fa fa-minus"></i></button>
            <button class="btn btn-sm btn-light" type="button" id="clientMapZoomReset">100%</button>
            <button class="btn btn-sm btn-light" type="button" id="clientMapZoomIn" aria-label="Увеличить масштаб"><i class="fa fa-plus"></i></button>
        </div>
    </div>
    <div class="client-node-viewport" id="clientNodeViewport">
        <div class="client-node-canvas" id="clientNodeCanvas">
            <svg class="client-node-links" id="clientNodeLinks" aria-hidden="true"></svg>
            <div id="clientNodeLayer">
                @foreach($clients as $index => $client)
                    @php($clientX = $client->map_x ?? (100 + ($index % 4) * 430))
                    @php($clientY = $client->map_y ?? (100 + intdiv($index, 4) * 220))
                    <article class="client-node client-node--client" data-type="client" data-id="{{ $client->id }}" style="left: {{ $clientX }}px; top: {{ $clientY }}px">
                        <div class="client-node__head"><i class="fa fa-user"></i> Клиент</div>
                        <div class="client-node__body">
                            <div class="client-node__name">{{ $client->name }}</div>
                            <div class="client-node__meta">{{ $client->phone ?: 'Телефон не указан' }}</div>
                            <div class="client-node__hint">Перетащите сюда питомца</div>
                        </div>
                    </article>
                @endforeach
                @foreach($animals as $index => $animal)
                    @php($animalX = $animal->map_x ?? (100 + ($index % 6) * 360))
                    @php($animalY = $animal->map_y ?? (380 + intdiv($index, 6) * 190))
                    @php($photo = $animal->photos->first()?->path)
                    <article class="client-node client-node--animal" data-type="animal" data-id="{{ $animal->id }}" style="left: {{ $animalX }}px; top: {{ $animalY }}px">
                        <div class="client-node__head"><i class="fa fa-paw"></i> Питомец</div>
                        <div class="client-node__body">
                            @if($photo)
                                <img class="client-node__photo" src="{{ Storage::url($photo) }}" alt="">
                            @else
                                <span class="client-node__photo client-node__photo--empty">🐾</span>
                            @endif
                            <div class="client-node__name">{{ $animal->name }}</div>
                            <div class="client-node__meta">{{ $animal->client_id ? 'Привязан к клиенту' : 'Без хозяина' }}</div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
        <div class="client-node-confirm" id="clientMapUnlinkConfirm" hidden>
            <span>Разорвать связь?</span>
            <button class="btn btn-sm btn-danger" type="button" data-confirm-unlink>Да</button>
            <button class="btn btn-sm btn-light" type="button" data-cancel-unlink>Нет</button>
        </div>
    </div>
</div>

<div class="modal fade" id="newMapClientModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered map-form-dialog">
        <form class="modal-content map-form-modal" id="newMapClientForm">
            <div class="modal-header map-form-modal__header">
                <div><div class="map-form-modal__eyebrow"><i class="fa fa-user-plus"></i> Карта клиентов</div><h5 class="modal-title">Новый клиент</h5></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body map-form-modal__body">
                <p class="map-form-modal__lead">Карточка появится на поле сразу после создания.</p>
                <div class="map-form-field"><label for="mapClientName">Имя или ФИО <span>*</span></label><input id="mapClientName" class="form-control" name="name" placeholder="Например, Анастасия Иванова" autocomplete="name" required></div>
                <div class="map-form-field"><label for="mapClientPhone">Телефон</label><input id="mapClientPhone" class="form-control" name="phone" placeholder="+7 999 123-45-67" autocomplete="tel"></div>
                <div class="map-form-field"><label for="mapClientAddress">Адрес</label><input id="mapClientAddress" class="form-control" name="address" placeholder="Улица, дом, квартира" autocomplete="street-address"></div>
            </div>
            <div class="modal-footer map-form-modal__footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button><button class="btn btn-primary"><i class="fa fa-plus me-1"></i>Создать клиента</button></div>
        </form>
    </div>
</div>
<div class="modal fade" id="newMapAnimalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered map-form-dialog">
        <form class="modal-content map-form-modal" id="newMapAnimalForm" enctype="multipart/form-data">
            <div class="modal-header map-form-modal__header">
                <div><div class="map-form-modal__eyebrow"><i class="fa fa-paw"></i> Карта клиентов</div><h5 class="modal-title">Новый питомец</h5></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body map-form-modal__body">
                <p class="map-form-modal__lead">Добавьте минимум кличку — остальные данные можно заполнить позднее.</p>
                <div class="map-form-field"><label for="mapAnimalName">Кличка <span>*</span></label><input id="mapAnimalName" class="form-control" name="name" placeholder="Например, Дейзи" autocomplete="off" required></div>
                <div class="map-form-field"><label for="mapAnimalCategory">Категория</label><select id="mapAnimalCategory" class="form-select" name="category_id"><option value="">Не указана</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></div>
                <div class="map-form-field"><label for="mapAnimalPhoto">Фото</label><input id="mapAnimalPhoto" class="form-control" type="file" name="photo" accept="image/*"></div>
            </div>
            <div class="modal-footer map-form-modal__footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button><button class="btn btn-primary"><i class="fa fa-plus me-1"></i>Создать питомца</button></div>
        </form>
    </div>
</div>
<div class="modal fade" id="mapNodeEditorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered map-form-dialog">
        <form class="modal-content map-form-modal" id="mapNodeEditorForm">
            <div class="modal-header map-form-modal__header">
                <div><div class="map-form-modal__eyebrow" id="mapNodeEditorEyebrow"></div><h5 class="modal-title" id="mapNodeEditorTitle">Редактировать карточку</h5></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body map-form-modal__body" id="mapNodeEditorFields"></div>
            <div class="modal-footer map-form-modal__footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button><button class="btn btn-primary"><i class="fa fa-check me-1"></i>Сохранить</button></div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
.client-node-page{min-width:0}.client-node-toolbar{display:flex;flex-wrap:wrap;gap:18px;padding:10px 14px;border:1px solid #dfe7ef;border-bottom:0;border-radius:12px 12px 0 0;background:#fff;color:#6e7e90;font-size:.82rem}.client-node-viewport{height:calc(100vh - 245px);min-height:580px;overflow:hidden;position:relative;border:1px solid #dfe7ef;border-radius:0 0 12px 12px;background:#edf2f7;touch-action:none;cursor:grab}.client-node-viewport.is-panning{cursor:grabbing}.client-node-canvas{position:relative;width:2400px;height:1600px;transform-origin:0 0;will-change:transform;background-color:#f8fafc;background-image:radial-gradient(#cbd5e1 1px,transparent 1px);background-size:20px 20px}.client-node-links{position:absolute;inset:0;width:100%;height:100%;overflow:visible;pointer-events:none}.client-node-link{fill:none;stroke:#91a4b7;stroke-width:3}.client-node-unlink{pointer-events:all;cursor:pointer}.client-node-unlink circle{fill:#fff;stroke:#e1626d;stroke-width:2}.client-node-unlink text{fill:#d6404d;font-size:16px;font-weight:800;text-anchor:middle;dominant-baseline:central}.client-node{position:absolute;width:236px;border:1px solid #d9e2eb;border-radius:12px;background:#fff;box-shadow:0 9px 22px rgba(47,65,83,.12);overflow:hidden;user-select:none}.client-node--client{border-top:4px solid #3178c6}.client-node--animal{width:188px;border-top:4px solid #d38a2f}.client-node__head{display:flex;align-items:center;gap:8px;padding:9px 11px;cursor:grab;font-size:.72rem;font-weight:800;letter-spacing:.03em;text-transform:uppercase}.client-node--client .client-node__head{background:#edf6ff;color:#1f629e}.client-node--animal .client-node__head{background:#fff6e9;color:#aa6816}.client-node__body{padding:12px}.client-node__name{font-size:.94rem;font-weight:800;color:#35475a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.client-node__meta{margin-top:4px;color:#768699;font-size:.8rem}.client-node__photo{width:52px;height:52px;float:right;margin-left:10px;border-radius:10px;object-fit:cover;background:#fff4dc}.client-node__photo--empty{display:grid;place-items:center;font-size:23px}.client-node__hint{margin-top:8px;color:#91a0af;font-size:.72rem}.client-node.is-dragging{z-index:10;box-shadow:0 16px 32px rgba(38,62,87,.22);cursor:grabbing}.client-node.is-drop-target{outline:3px solid rgba(49,120,198,.38);outline-offset:4px}@media(max-width:767px){.client-node-viewport{height:calc(100vh - 230px);min-height:480px}.client-node-toolbar{gap:9px;font-size:.72rem}.client-node-page{padding-right:0;padding-left:0}}
.client-node__head{touch-action:none;-webkit-user-select:none;user-select:none}.client-node__actions{display:flex;align-items:center;gap:5px;margin-left:auto}.client-node__connect,.client-node__edit{width:26px;height:26px;padding:0;border:0;border-radius:50%;background:rgba(255,255,255,.9);color:inherit;font-size:13px;font-weight:700;line-height:24px;cursor:pointer;touch-action:none;box-shadow:0 1px 4px rgba(38,62,87,.15)}.client-node__connect{font-size:19px;cursor:crosshair}.client-node__connect:active,.client-node__edit:active{transform:scale(.92)}.client-node-link--preview{stroke:#3178c6;stroke-width:3;stroke-dasharray:7 6}.client-node-confirm{position:absolute;z-index:30;display:flex;align-items:center;gap:7px;padding:8px 9px;border:1px solid #dce5ee;border-radius:10px;background:#fff;box-shadow:0 10px 28px rgba(38,62,87,.2);font-size:.78rem;font-weight:700;white-space:nowrap}.client-node-confirm[hidden]{display:none}.client-node-confirm .btn{padding:3px 7px;font-size:.74rem}.client-node-zoom{display:flex;gap:4px}.client-node-zoom .btn{min-width:34px;font-weight:700}.map-form-dialog{max-width:460px}.map-form-modal{overflow:hidden;border:0;border-radius:16px;box-shadow:0 24px 64px rgba(28,45,64,.2)}.map-form-modal__header{align-items:flex-start;padding:22px 24px 18px;border-bottom:1px solid #edf1f5}.map-form-modal__header .btn-close{margin:2px 0 0 auto}.map-form-modal__eyebrow{margin-bottom:5px;color:#768699;font-size:.72rem;font-weight:800;letter-spacing:.04em;text-transform:uppercase}.map-form-modal__eyebrow i{margin-right:5px;color:#3178c6}.map-form-modal .modal-title{color:#2d3e50;font-size:1.28rem;font-weight:800}.map-form-modal__body{padding:20px 24px 24px}.map-form-modal__lead{margin:0 0 20px;color:#78889a;font-size:.88rem;line-height:1.45}.map-form-field{display:block;margin-top:16px}.map-form-field:first-of-type{margin-top:0}.map-form-field label{display:block;margin:0 0 7px;color:#435467;font-size:.82rem;font-weight:750}.map-form-field label span{color:#d9534f}.map-form-field .form-control,.map-form-field .form-select{min-height:44px;border-color:#d9e3ec;border-radius:9px;font-size:.94rem;box-shadow:none}.map-form-field .form-control:focus,.map-form-field .form-select:focus{border-color:#76a9dd;box-shadow:0 0 0 3px rgba(49,120,198,.12)}.map-form-modal__footer{display:flex;gap:9px;justify-content:flex-end;padding:15px 24px 20px;border-top:1px solid #edf1f5}.map-form-modal__footer .btn{min-height:40px;padding:8px 15px;font-weight:700}@media(max-width:575px){.map-form-dialog{margin:12px}.map-form-modal__header{padding:19px 19px 15px}.map-form-modal__body{padding:18px 19px 20px}.map-form-modal__footer{padding:14px 19px 18px}.map-form-modal__footer .btn{flex:1;padding-right:8px;padding-left:8px}}
.client-node__head{touch-action:none;-webkit-user-select:none;user-select:none}.client-node__actions{display:flex;align-items:center;gap:5px;margin-left:auto}.client-node__connect,.client-node__edit{width:26px;height:26px;padding:0;border:0;border-radius:50%;background:rgba(255,255,255,.9);color:inherit;font-size:13px;font-weight:700;line-height:24px;cursor:pointer;touch-action:none;box-shadow:0 1px 4px rgba(38,62,87,.15)}.client-node__connect{font-size:19px;cursor:crosshair}.client-node__connect:active,.client-node__edit:active{transform:scale(.92)}.client-node-link--preview{stroke:#3178c6;stroke-width:3;stroke-dasharray:7 6}.client-node-confirm{position:absolute;z-index:30;display:flex;align-items:center;gap:7px;padding:8px 9px;border:1px solid #dce5ee;border-radius:10px;background:#fff;box-shadow:0 10px 28px rgba(38,62,87,.2);font-size:.78rem;font-weight:700;white-space:nowrap}.client-node-confirm[hidden]{display:none}.client-node-confirm .btn{padding:3px 7px;font-size:.74rem}.client-node-zoom{display:flex;gap:4px}.client-node-zoom .btn{min-width:34px;font-weight:700}.map-form-dialog{max-width:460px}.map-form-modal{overflow:hidden;border:0;border-radius:16px;box-shadow:0 24px 64px rgba(28,45,64,.2)}.map-form-modal__header{align-items:flex-start;padding:22px 24px 18px;border-bottom:1px solid #edf1f5}.map-form-modal__header .btn-close{margin:2px 0 0 auto}.map-form-modal__eyebrow{margin-bottom:5px;color:#768699;font-size:.72rem;font-weight:800;letter-spacing:.04em;text-transform:uppercase}.map-form-modal__eyebrow i{margin-right:5px;color:#3178c6}.map-form-modal .modal-title{color:#2d3e50;font-size:1.28rem;font-weight:800}.map-form-modal__body{padding:20px 24px 24px}.map-form-modal__lead{margin:0 0 20px;color:#78889a;font-size:.88rem;line-height:1.45}.map-form-field{display:block;margin-top:16px}.map-form-field:first-of-type{margin-top:0}.map-form-field label{display:block;margin:0 0 7px;color:#435467;font-size:.82rem;font-weight:750}.map-form-field label span{color:#d9534f}.map-form-field .form-control,.map-form-field .form-select{min-height:44px;border-color:#d9e3ec;border-radius:9px;font-size:.94rem;box-shadow:none}.map-form-field .form-control:focus,.map-form-field .form-select:focus{border-color:#76a9dd;box-shadow:0 0 0 3px rgba(49,120,198,.12)}.map-form-modal__footer{display:flex;gap:9px;justify-content:flex-end;padding:15px 24px 20px;border-top:1px solid #edf1f5}.map-form-modal__footer .btn{min-height:40px;padding:8px 15px;font-weight:700}@media(max-width:575px){.map-form-dialog{margin:12px}.map-form-modal__header{padding:19px 19px 15px}.map-form-modal__body{padding:18px 19px 20px}.map-form-modal__footer{padding:14px 19px 18px}.map-form-modal__footer .btn{flex:1;padding-right:8px;padding-left:8px}}.client-node-viewport{background-color:#f8fafc;background-image:radial-gradient(#cbd5e1 1px,transparent 1px);background-size:20px 20px}.client-node-canvas{width:10000px;height:10000px;background:transparent}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const clients = @json($clientsPayload);
    const animals = @json($animalsPayload);
    const categories = @json($categories->map(fn ($category) => ['id' => $category->id, 'name' => $category->name])->values());
    const canvas = document.getElementById('clientNodeCanvas');
    const viewport = document.getElementById('clientNodeViewport');
    const layer = document.getElementById('clientNodeLayer');
    const links = document.getElementById('clientNodeLinks');
    const unlinkConfirm = document.getElementById('clientMapUnlinkConfirm');
    const nodeEditorModal = document.getElementById('mapNodeEditorModal');
    const nodeEditorForm = document.getElementById('mapNodeEditorForm');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const urls = {
        positions: '{{ route('admin.client-map.positions.save') }}',
        clients: '{{ route('admin.client-map.clients.store') }}',
        animals: '{{ route('admin.client-map.animals.store') }}',
        attach: '{{ url('/zooadmin/client-map/animals') }}',
    };
    let dragged = null;
    let linking = null;
    let pinch = null;
    let panning = null;
    let pan = {x: 0, y: 0};
    let pendingUnlink = null;
    let editingNode = null;
    let zoom = Number(localStorage.getItem('zooland-client-map-zoom') || 1);

    const defaults = (type, index) => type === 'client'
        ? { x: 100 + (index % 4) * 430, y: 100 + Math.floor(index / 4) * 220 }
        : { x: 100 + (index % 6) * 360, y: 380 + Math.floor(index / 6) * 190 };
    const normalise = (node, type, index) => Object.assign(node, {
        type, index,
        x: Number(node.x ?? defaults(type, index).x),
        y: Number(node.y ?? defaults(type, index).y),
    });
    clients.forEach((node, index) => normalise(node, 'client', index));
    animals.forEach((node, index) => normalise(node, 'animal', index));

    const escapeHtml = value => String(value || '').replace(/[&<>"']/g, char => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[char]));
    const request = (url, method = 'POST', body = null) => fetch(url, {
        method, body,
        credentials: 'same-origin',
        headers: {
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json',
            ...(typeof body === 'string' ? {'Content-Type': 'application/json'} : {}),
        },
    }).then(response => {
        if (!response.ok) throw new Error('request_failed');
        return response.json();
    });
    const applyZoom = value => {
        zoom = Math.max(.25, Math.min(1.5, value));
        canvas.style.transform = `translate(${pan.x}px, ${pan.y}px) scale(${zoom})`;
        viewport.style.backgroundSize = `${20 * zoom}px ${20 * zoom}px`;
        viewport.style.backgroundPosition = `${pan.x}px ${pan.y}px`;
        localStorage.setItem('zooland-client-map-zoom', zoom);
        document.getElementById('clientMapZoomReset').textContent = `${Math.round(zoom * 100)}%`;
    };
    document.getElementById('clientMapZoomOut').addEventListener('click', () => applyZoom(zoom - .1));
    document.getElementById('clientMapZoomIn').addEventListener('click', () => applyZoom(zoom + .1));
    document.getElementById('clientMapZoomReset').addEventListener('click', () => { pan = {x: 0, y: 0}; applyZoom(1); });
    applyZoom(zoom);

    const nodeElement = node => {
        const element = document.createElement('article');
        element.className = `client-node client-node--${node.type}`;
        element.dataset.type = node.type;
        element.dataset.id = node.id;
        element.style.left = `${node.x}px`;
        element.style.top = `${node.y}px`;
        if (node.type === 'client') {
            element.innerHTML = `<div class="client-node__head"><i class="fa fa-user"></i> Клиент<div class="client-node__actions"><button class="client-node__edit" type="button" aria-label="Редактировать клиента"><i class="fa fa-pen"></i></button><button class="client-node__connect" type="button" aria-label="Связать с питомцем">+</button></div></div><div class="client-node__body"><div class="client-node__name">${escapeHtml(node.name)}</div><div class="client-node__meta">${escapeHtml(node.phone || 'Телефон не указан')}</div><div class="client-node__hint">Потяните + к питомцу</div></div>`;
        } else {
            const photo = node.photo
                ? `<img class="client-node__photo" src="${escapeHtml(node.photo)}" alt="">`
                : '<span class="client-node__photo client-node__photo--empty">🐾</span>';
            element.innerHTML = `<div class="client-node__head"><i class="fa fa-paw"></i> Питомец<div class="client-node__actions"><button class="client-node__edit" type="button" aria-label="Редактировать питомца"><i class="fa fa-pen"></i></button><button class="client-node__connect" type="button" aria-label="Связать с клиентом">+</button></div></div><div class="client-node__body">${photo}<div class="client-node__name">${escapeHtml(node.name)}</div><div class="client-node__meta">${node.client_id ? 'Привязан к клиенту' : 'Без хозяина'}</div></div>`;
        }
        element.querySelector('.client-node__head').addEventListener('pointerdown', event => startDrag(event, node, element));
        element.querySelector('.client-node__connect').addEventListener('pointerdown', event => startLink(event, node));
        element.querySelector('.client-node__edit').addEventListener('click', event => { event.preventDefault(); event.stopPropagation(); openNodeEditor(node); });
        return element;
    };
    const linkPath = (animal, client) => {
        const animalCenter = animal.x + 94;
        const clientCenter = client.x + 118;
        const horizontalDirection = clientCenter >= animalCenter ? 1 : -1;
        const sx = animal.x + (horizontalDirection > 0 ? 188 : 0);
        const sy = animal.y + 64;
        const tx = client.x + (horizontalDirection > 0 ? 0 : 236);
        const ty = client.y + 64;
        const mid = (sx + tx) / 2;
        const direction = ty >= sy ? 1 : -1;
        const radius = Math.max(0, Math.min(64, Math.abs(ty - sy) / 2, Math.abs(tx - sx) / 3));
        const kappa = .55228475 * radius;
        if (radius < 1) return {d: `M ${sx} ${sy} H ${tx}`, x: mid, y: sy};
        return { d: `M ${sx} ${sy} H ${mid - horizontalDirection * radius} C ${mid - horizontalDirection * radius + horizontalDirection * kappa} ${sy}, ${mid} ${sy + direction * (radius - kappa)}, ${mid} ${sy + direction * radius} V ${ty - direction * radius} C ${mid} ${ty - direction * (radius - kappa)}, ${mid + horizontalDirection * (radius - kappa)} ${ty}, ${mid + horizontalDirection * radius} ${ty} H ${tx}`, x: mid, y: (sy + ty) / 2 };
    };
    const renderLinks = () => {
        links.replaceChildren();
        animals.filter(animal => animal.client_id).forEach(animal => {
            const client = clients.find(item => item.id === animal.client_id);
            if (!client) return;
            const link = linkPath(animal, client);
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('class', 'client-node-link'); path.setAttribute('d', link.d); links.append(path);
            const remove = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            remove.setAttribute('class', 'client-node-unlink'); remove.setAttribute('transform', `translate(${link.x} ${link.y})`);
            remove.innerHTML = '<circle r="12"></circle><text y="1">×</text>';
            remove.addEventListener('click', event => showUnlinkConfirm(animal, event)); links.append(remove);
        });
        if (linking) {
            const startX = linking.node.x + (linking.node.type === 'animal' ? 94 : 118);
            const startY = linking.node.y + 48;
            const endX = linking.x, endY = linking.y;
            const preview = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            preview.setAttribute('class', 'client-node-link client-node-link--preview');
            preview.setAttribute('d', `M ${startX} ${startY} C ${startX + 90} ${startY}, ${endX - 90} ${endY}, ${endX} ${endY}`);
            links.append(preview);
        }
    };
    const render = () => { layer.replaceChildren(...[...clients, ...animals].map(nodeElement)); renderLinks(); };
    const savePositions = () => request(urls.positions, 'POST', JSON.stringify({
        nodes: [...clients, ...animals].map(node => ({type: node.type, id: node.id, x: Math.round(node.x), y: Math.round(node.y)})),
    })).catch(() => console.warn('Не удалось сохранить положение нод'));
    const localPoint = event => {
        const rect = canvas.getBoundingClientRect();
        return {x: (event.clientX - rect.left) / zoom, y: (event.clientY - rect.top) / zoom};
    };
    const connectionTarget = (event, source) => {
        const point = localPoint(event);
        const candidates = source.type === 'animal' ? clients : animals;
        return candidates.find(node => {
            const width = node.type === 'client' ? 236 : 188;
            return point.x >= node.x && point.x <= node.x + width && point.y >= node.y && point.y <= node.y + 128;
        }) || null;
    };
    const startDrag = (event, node, element) => {
        if (event.target.closest('.client-node__connect, .client-node__edit')) return;
        event.preventDefault();
        const rect = canvas.getBoundingClientRect();
        dragged = {node, element, offset: {x: (event.clientX - rect.left) / zoom - node.x, y: (event.clientY - rect.top) / zoom - node.y}};
        element.classList.add('is-dragging');
        element.setPointerCapture?.(event.pointerId);
    };
    const startLink = (event, node) => {
        event.preventDefault(); event.stopPropagation();
        const point = localPoint(event);
        linking = {node, x: point.x, y: point.y};
        event.currentTarget.setPointerCapture?.(event.pointerId);
        renderLinks();
    };
    const openNodeEditor = node => {
        editingNode = node;
        const isClient = node.type === 'client';
        document.getElementById('mapNodeEditorEyebrow').innerHTML = isClient ? '<i class="fa fa-user"></i> Клиент' : '<i class="fa fa-paw"></i> Питомец';
        document.getElementById('mapNodeEditorTitle').textContent = isClient ? 'Редактировать клиента' : 'Редактировать питомца';
        const fields = document.getElementById('mapNodeEditorFields');
        if (isClient) {
            fields.innerHTML = `<div class="map-form-field"><label for="mapEditName">Имя или ФИО <span>*</span></label><input id="mapEditName" class="form-control" name="name" value="${escapeHtml(node.name)}" required></div><div class="map-form-field"><label for="mapEditPhone">Телефон</label><input id="mapEditPhone" class="form-control" name="phone" value="${escapeHtml(node.phone)}"></div><div class="map-form-field"><label for="mapEditAddress">Адрес</label><input id="mapEditAddress" class="form-control" name="address" value="${escapeHtml(node.address)}"></div>`;
        } else {
            const options = ['<option value="">Не указана</option>', ...categories.map(category => `<option value="${category.id}" ${Number(node.category_id) === Number(category.id) ? 'selected' : ''}>${escapeHtml(category.name)}</option>`)].join('');
            fields.innerHTML = `<div class="map-form-field"><label for="mapEditName">Кличка <span>*</span></label><input id="mapEditName" class="form-control" name="name" value="${escapeHtml(node.name)}" required></div><div class="map-form-field"><label for="mapEditCategory">Категория</label><select id="mapEditCategory" class="form-select" name="category_id">${options}</select></div>`;
        }
        bootstrap.Modal.getOrCreateInstance(nodeEditorModal).show();
    };
    nodeEditorForm.addEventListener('submit', event => {
        event.preventDefault();
        if (!editingNode) return;
        const node = editingNode;
        const data = new FormData(nodeEditorForm);
        data.append('_method', 'PATCH');
        const url = `${node.type === 'client' ? urls.clients : urls.animals}/${node.id}`;
        request(url, 'POST', data).then(result => {
            Object.assign(node, result);
            bootstrap.Modal.getOrCreateInstance(nodeEditorModal).hide();
            editingNode = null;
            render();
        });
    });
    viewport.addEventListener('pointerdown', event => {
        if (event.target.closest('.client-node, .client-node-unlink, #clientMapUnlinkConfirm')) return;
        event.preventDefault();
        panning = {x: event.clientX, y: event.clientY, panX: pan.x, panY: pan.y};
        viewport.classList.add('is-panning');
        viewport.setPointerCapture?.(event.pointerId);
    });
    document.addEventListener('pointermove', event => {
        if (pinch) return;
        if (panning) {
            pan = {x: panning.panX + event.clientX - panning.x, y: panning.panY + event.clientY - panning.y};
            applyZoom(zoom);
            return;
        }
        if (linking) {
            const point = localPoint(event);
            linking.x = point.x; linking.y = point.y;
            const target = connectionTarget(event, linking.node);
            layer.querySelectorAll('.client-node').forEach(item => item.classList.toggle('is-drop-target', target && String(target.id) === item.dataset.id && target.type === item.dataset.type));
            renderLinks();
            return;
        }
        if (!dragged) return;
        const rect = canvas.getBoundingClientRect(), node = dragged.node;
        node.x = Math.max(0, Math.min(2200, (event.clientX - rect.left) / zoom - dragged.offset.x));
        node.y = Math.max(0, Math.min(1500, (event.clientY - rect.top) / zoom - dragged.offset.y));
        dragged.element.style.left = `${node.x}px`; dragged.element.style.top = `${node.y}px`;
        renderLinks();
        layer.querySelectorAll('.client-node').forEach(item => item.classList.remove('is-drop-target'));
    });
    document.addEventListener('pointerup', event => {
        if (panning) { panning = null; viewport.classList.remove('is-panning'); return; }
        if (linking) {
            const source = linking.node, target = connectionTarget(event, source);
            linking = null;
            layer.querySelectorAll('.client-node').forEach(item => item.classList.remove('is-drop-target'));
            if (target) {
                const animal = source.type === 'animal' ? source : target;
                const client = source.type === 'client' ? source : target;
                const previousClientId = animal.client_id;
                animal.client_id = client.id;
                render();
                request(`${urls.attach}/${animal.id}/clients/${client.id}`).catch(() => {
                    animal.client_id = previousClientId;
                    render();
                    alert('Не удалось сохранить связь. Попробуйте ещё раз.');
                });
            } else {
                renderLinks();
            }
            return;
        }
        if (!dragged) return;
        const {element} = dragged;
        element.classList.remove('is-dragging');
        layer.querySelectorAll('.client-node').forEach(item => item.classList.remove('is-drop-target'));
        savePositions(); dragged = null;
    }, true);
    document.addEventListener('pointercancel', () => {
        panning = null; viewport.classList.remove('is-panning');
        if (linking) { linking = null; renderLinks(); }
        if (!dragged) return;
        dragged.element.classList.remove('is-dragging');
        savePositions(); dragged = null;
    }, true);
    const touchDistance = touches => Math.hypot(touches[0].clientX - touches[1].clientX, touches[0].clientY - touches[1].clientY);
    viewport.addEventListener('touchstart', event => {
        if (event.touches.length !== 2) return;
        event.preventDefault();
        if (dragged) { dragged.element.classList.remove('is-dragging'); dragged = null; }
        panning = null; viewport.classList.remove('is-panning');
        pinch = {distance: touchDistance(event.touches), zoom};
    }, {passive: false});
    viewport.addEventListener('touchmove', event => {
        if (!pinch || event.touches.length !== 2) return;
        event.preventDefault();
        applyZoom(pinch.zoom * touchDistance(event.touches) / pinch.distance);
    }, {passive: false});
    viewport.addEventListener('touchend', event => {
        if (event.touches.length < 2) pinch = null;
    });
    const hideUnlinkConfirm = () => { pendingUnlink = null; unlinkConfirm.hidden = true; };
    const showUnlinkConfirm = (animal, event) => {
        event.preventDefault(); event.stopPropagation();
        pendingUnlink = animal;
        const rect = viewport.getBoundingClientRect();
        unlinkConfirm.hidden = false;
        const width = unlinkConfirm.offsetWidth;
        unlinkConfirm.style.left = `${Math.max(8, Math.min(rect.width - width - 8, event.clientX - rect.left + 14))}px`;
        unlinkConfirm.style.top = `${Math.max(8, Math.min(rect.height - unlinkConfirm.offsetHeight - 8, event.clientY - rect.top - 18))}px`;
    };
    unlinkConfirm.querySelector('[data-cancel-unlink]').addEventListener('click', hideUnlinkConfirm);
    unlinkConfirm.querySelector('[data-confirm-unlink]').addEventListener('click', () => {
        const animal = pendingUnlink;
        hideUnlinkConfirm();
        if (!animal) return;
        request(`${urls.attach}/${animal.id}/client`, 'DELETE').then(() => { animal.client_id = null; render(); });
    });
    document.addEventListener('pointerdown', event => {
        if (!unlinkConfirm.hidden && !event.target.closest('#clientMapUnlinkConfirm, .client-node-unlink')) hideUnlinkConfirm();
    });
    const addForm = (id, url, type) => document.getElementById(id).addEventListener('submit', event => {
        event.preventDefault();
        const data = new FormData(event.currentTarget), initial = defaults(type, type === 'client' ? clients.length : animals.length);
        data.append('map_x', initial.x); data.append('map_y', initial.y);
        request(url, 'POST', data).then(result => {
            normalise(result, type, type === 'client' ? clients.length : animals.length);
            (type === 'client' ? clients : animals).push(result);
            event.currentTarget.reset(); bootstrap.Modal.getOrCreateInstance(document.getElementById(type === 'client' ? 'newMapClientModal' : 'newMapAnimalModal')).hide(); render();
        });
    });
    addForm('newMapClientForm', urls.clients, 'client'); addForm('newMapAnimalForm', urls.animals, 'animal'); render();
});
</script>
@endpush
