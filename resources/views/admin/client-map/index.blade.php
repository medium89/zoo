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

    <div class="client-node-toolbar"><span><i class="fa fa-arrows-up-down-left-right"></i> Тяните ноды за шапку</span><span><i class="fa fa-link"></i> Крестик на линии разрывает связь</span></div>
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
</style>
@endpush

@push('scripts')
<script>
</script>
@endpush
