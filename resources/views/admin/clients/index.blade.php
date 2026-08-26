@extends('admin.index')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Клиенты</h1>
        <a href="{{ route('admin.clients.create') }}" class="btn btn-primary">Добавить</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($mapClients->isNotEmpty())
        <section class="card mb-4 client-map-card">
            <div class="card-header d-flex justify-content-between align-items-center gap-2">
                <span>Клиенты на карте</span>
                <span class="badge text-bg-light">{{ $mapClients->count() }}</span>
            </div>
            <div class="card-body p-0">
                @if($yandexMapsKey)
                    <div id="clientsMap" class="client-map" aria-label="Карта клиентов"></div>
                @else
                    <div class="p-3 text-muted">Чтобы показать адреса на карте, добавьте <code>YANDEX_MAPS_API_KEY</code> в файл <code>.env</code>.</div>
                @endif
            </div>
        </section>
    @endif

    @if($clients->count())
        <div class="admin-grid" style="--grid-cols: 80px 1.3fr 1fr 120px 120px 170px;">
            <div class="admin-grid-header">
                <div>#</div>
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
                                <a href="{{ route('admin.clients.edit', $client) }}" class="btn btn-sm btn-primary text-white"><i class="fa fa-pen"></i></a>
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

@if($mapClients->isNotEmpty() && $yandexMapsKey)
    @push('styles')
    <style>.client-map{height:420px;width:100%;border-radius:0 0 .375rem .375rem;overflow:hidden}@media(max-width:767px){.client-map{height:320px}}</style>
    @endpush
    @push('scripts')
    <script src="https://api-maps.yandex.ru/2.1/?apikey={{ urlencode($yandexMapsKey) }}&lang=ru_RU" defer></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const clients = @json($mapClientsPayload);
        const start = () => {
            if (!window.ymaps || !document.getElementById('clientsMap')) return;
            ymaps.ready(() => {
                const map = new ymaps.Map('clientsMap', {center: [53.3474, 83.7783], zoom: 10, controls: ['zoomControl', 'fullscreenControl']});
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
                    }).catch(() => {}).finally(() => {
                        completed += 1;
                        if (completed === clients.length && points.length) {
                            map.geoObjects.add(cluster);
                            map.setBounds(cluster.getBounds(), {checkZoomRange: true, zoomMargin: 36});
                        }
                    });
                });
            });
        };
        const waitForMaps = () => window.ymaps ? start() : window.setTimeout(waitForMaps, 100);
        waitForMaps();
    });
    </script>
    @endpush
@endif
@endsection
