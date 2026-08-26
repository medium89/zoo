@extends('admin.index')

@section('content')
<div class="container-fluid client-node-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div><h1 class="mb-1">Карта клиентов</h1><p class="text-muted mb-0">Перетащите питомца на клиента, чтобы связать их.</p></div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#newMapAnimalModal"><i class="fa fa-paw me-1"></i>Питомец</button>
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
    </div>
</div>

<div class="modal fade" id="newMapClientModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><form class="modal-content" id="newMapClientForm"><div class="modal-header"><h5 class="modal-title">Новый клиент</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">Имя / ФИО<input class="form-control" name="name" required></label><label class="form-label mt-3">Телефон<input class="form-control" name="phone"></label><label class="form-label mt-3">Адрес<input class="form-control" name="address"></label></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button><button class="btn btn-primary">Создать</button></div></form></div></div>
<div class="modal fade" id="newMapAnimalModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><form class="modal-content" id="newMapAnimalForm" enctype="multipart/form-data"><div class="modal-header"><h5 class="modal-title">Новый питомец</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">Кличка<input class="form-control" name="name" required></label><label class="form-label mt-3">Категория<select class="form-select" name="category_id"><option value="">Не указана</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></label><label class="form-label mt-3">Фото<input class="form-control" type="file" name="photo" accept="image/*"></label></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button><button class="btn btn-primary">Создать</button></div></form></div></div>
@endsection

@push('styles')
<style>
.client-node-page{min-width:0}.client-node-toolbar{display:flex;flex-wrap:wrap;gap:18px;padding:10px 14px;border:1px solid #dfe7ef;border-bottom:0;border-radius:12px 12px 0 0;background:#fff;color:#6e7e90;font-size:.82rem}.client-node-viewport{height:calc(100vh - 245px);min-height:580px;overflow:auto;border:1px solid #dfe7ef;border-radius:0 0 12px 12px;background:#edf2f7}.client-node-canvas{position:relative;width:2400px;height:1600px;background-color:#f8fafc;background-image:radial-gradient(#cbd5e1 1px,transparent 1px);background-size:20px 20px}.client-node-links{position:absolute;inset:0;width:100%;height:100%;overflow:visible;pointer-events:none}.client-node-link{fill:none;stroke:#91a4b7;stroke-width:3}.client-node-unlink{pointer-events:all;cursor:pointer}.client-node-unlink circle{fill:#fff;stroke:#e1626d;stroke-width:2}.client-node-unlink text{fill:#d6404d;font-size:16px;font-weight:800;text-anchor:middle;dominant-baseline:central}.client-node{position:absolute;width:236px;border:1px solid #d9e2eb;border-radius:12px;background:#fff;box-shadow:0 9px 22px rgba(47,65,83,.12);overflow:hidden;user-select:none}.client-node--client{border-top:4px solid #3178c6}.client-node--animal{width:188px;border-top:4px solid #d38a2f}.client-node__head{display:flex;align-items:center;gap:8px;padding:9px 11px;cursor:grab;font-size:.72rem;font-weight:800;letter-spacing:.03em;text-transform:uppercase}.client-node--client .client-node__head{background:#edf6ff;color:#1f629e}.client-node--animal .client-node__head{background:#fff6e9;color:#aa6816}.client-node__body{padding:12px}.client-node__name{font-size:.94rem;font-weight:800;color:#35475a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.client-node__meta{margin-top:4px;color:#768699;font-size:.8rem}.client-node__photo{width:52px;height:52px;float:right;margin-left:10px;border-radius:10px;object-fit:cover;background:#fff4dc}.client-node__photo--empty{display:grid;place-items:center;font-size:23px}.client-node__hint{margin-top:8px;color:#91a0af;font-size:.72rem}.client-node.is-dragging{z-index:10;box-shadow:0 16px 32px rgba(38,62,87,.22);cursor:grabbing}.client-node.is-drop-target{outline:3px solid rgba(49,120,198,.38);outline-offset:4px}@media(max-width:767px){.client-node-viewport{height:calc(100vh - 230px);min-height:480px}.client-node-toolbar{gap:9px;font-size:.72rem}.client-node-page{padding-right:0;padding-left:0}}
.client-node__head{touch-action:none;-webkit-user-select:none;user-select:none}.client-node-zoom{display:flex;gap:4px}.client-node-zoom .btn{min-width:34px;font-weight:700}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const clients = @json($clientsPayload);
    const animals = @json($animalsPayload);
    const canvas = document.getElementById('clientNodeCanvas');
    const viewport = document.getElementById('clientNodeViewport');
    const layer = document.getElementById('clientNodeLayer');
    const links = document.getElementById('clientNodeLinks');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const urls = {
        positions: '{{ route('admin.client-map.positions.save') }}',
        clients: '{{ route('admin.client-map.clients.store') }}',
        animals: '{{ route('admin.client-map.animals.store') }}',
        attach: '{{ url('/zooadmin/client-map/animals') }}',
    };
    let dragged = null;
    let pinch = null;
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
        canvas.style.zoom = zoom;
        localStorage.setItem('zooland-client-map-zoom', zoom);
        document.getElementById('clientMapZoomReset').textContent = `${Math.round(zoom * 100)}%`;
    };
    document.getElementById('clientMapZoomOut').addEventListener('click', () => applyZoom(zoom - .1));
    document.getElementById('clientMapZoomIn').addEventListener('click', () => applyZoom(zoom + .1));
    document.getElementById('clientMapZoomReset').addEventListener('click', () => applyZoom(1));
    applyZoom(zoom);

    const nodeElement = node => {
        const element = document.createElement('article');
        element.className = `client-node client-node--${node.type}`;
        element.dataset.type = node.type;
        element.dataset.id = node.id;
        element.style.left = `${node.x}px`;
        element.style.top = `${node.y}px`;
        if (node.type === 'client') {
            element.innerHTML = `<div class="client-node__head"><i class="fa fa-user"></i> Клиент</div><div class="client-node__body"><div class="client-node__name">${escapeHtml(node.name)}</div><div class="client-node__meta">${escapeHtml(node.phone || 'Телефон не указан')}</div><div class="client-node__hint">Перетащите сюда питомца</div></div>`;
        } else {
            const photo = node.photo
                ? `<img class="client-node__photo" src="${escapeHtml(node.photo)}" alt="">`
                : '<span class="client-node__photo client-node__photo--empty">🐾</span>';
            element.innerHTML = `<div class="client-node__head"><i class="fa fa-paw"></i> Питомец</div><div class="client-node__body">${photo}<div class="client-node__name">${escapeHtml(node.name)}</div><div class="client-node__meta">${node.client_id ? 'Привязан к клиенту' : 'Без хозяина'}</div></div>`;
        }
        element.querySelector('.client-node__head').addEventListener('pointerdown', event => startDrag(event, node, element));
        return element;
    };
    const linkPath = (animal, client) => {
        const sx = animal.x + 94, sy = animal.y + 54, tx = client.x + 118, ty = client.y + 48;
        const mid = (sx + tx) / 2;
        const direction = ty >= sy ? 1 : -1;
        const radius = Math.max(0, Math.min(72, Math.abs(ty - sy) / 2, Math.abs(tx - sx) / 4));
        const kappa = .55228475 * radius;
        return { d: `M ${sx} ${sy} H ${mid - radius} C ${mid - radius + kappa} ${sy}, ${mid} ${sy + direction * (radius - kappa)}, ${mid} ${sy + direction * radius} V ${ty - direction * radius} C ${mid} ${ty - direction * (radius - kappa)}, ${mid + radius - kappa} ${ty}, ${mid + radius} ${ty} H ${tx}`, x: mid, y: (sy + ty) / 2 };
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
            remove.addEventListener('click', () => detach(animal)); links.append(remove);
        });
    };
    const render = () => { layer.replaceChildren(...[...clients, ...animals].map(nodeElement)); renderLinks(); };
    const savePositions = () => request(urls.positions, 'POST', JSON.stringify({
        nodes: [...clients, ...animals].map(node => ({type: node.type, id: node.id, x: Math.round(node.x), y: Math.round(node.y)})),
    })).catch(() => console.warn('Не удалось сохранить положение нод'));
    const dropTarget = event => {
        const rect = canvas.getBoundingClientRect();
        const x = (event.clientX - rect.left) / zoom;
        const y = (event.clientY - rect.top) / zoom;
        return clients.find(client => x >= client.x && x <= client.x + 236 && y >= client.y && y <= client.y + 128) || null;
    };
    const startDrag = (event, node, element) => {
        event.preventDefault();
        const rect = canvas.getBoundingClientRect();
        dragged = {node, element, offset: {x: (event.clientX - rect.left) / zoom - node.x, y: (event.clientY - rect.top) / zoom - node.y}};
        element.classList.add('is-dragging');
        element.setPointerCapture?.(event.pointerId);
    };
    document.addEventListener('pointermove', event => {
        if (pinch) return;
        if (!dragged) return;
        const rect = canvas.getBoundingClientRect(), node = dragged.node;
        node.x = Math.max(0, Math.min(2200, (event.clientX - rect.left) / zoom - dragged.offset.x));
        node.y = Math.max(0, Math.min(1500, (event.clientY - rect.top) / zoom - dragged.offset.y));
        dragged.element.style.left = `${node.x}px`; dragged.element.style.top = `${node.y}px`;
        renderLinks();
        const target = dropTarget(event);
        layer.querySelectorAll('.client-node--client').forEach(item => item.classList.toggle('is-drop-target', target && String(target.id) === item.dataset.id));
    });
    document.addEventListener('pointerup', event => {
        if (!dragged) return;
        const {node, element} = dragged, target = dropTarget(event);
        element.classList.remove('is-dragging');
        layer.querySelectorAll('.client-node--client').forEach(item => item.classList.toggle('is-drop-target', target && String(target.id) === item.dataset.id));
        if (node.type === 'animal' && target && node.client_id !== target.id) {
            const previousClientId = node.client_id;
            node.client_id = target.id;
            render();
            request(`${urls.attach}/${node.id}/clients/${target.id}`).catch(() => {
                node.client_id = previousClientId;
                render();
                alert('Не удалось сохранить связь. Попробуйте ещё раз.');
            });
        }
        savePositions(); dragged = null;
    }, true);
    document.addEventListener('pointercancel', () => {
        if (!dragged) return;
        dragged.element.classList.remove('is-dragging');
        savePositions(); dragged = null;
    }, true);
    const touchDistance = touches => Math.hypot(touches[0].clientX - touches[1].clientX, touches[0].clientY - touches[1].clientY);
    viewport.addEventListener('touchstart', event => {
        if (event.touches.length !== 2) return;
        event.preventDefault();
        if (dragged) { dragged.element.classList.remove('is-dragging'); dragged = null; }
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
    const detach = animal => {
        if (!confirm(`Отвязать ${animal.name} от клиента?`)) return;
        request(`${urls.attach}/${animal.id}/client`, 'DELETE').then(() => { animal.client_id = null; render(); });
    };
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
