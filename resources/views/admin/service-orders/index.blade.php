@extends('admin.index')

@section('content')
<div class="container-fluid service-orders-page">
    <div class="mb-4">
        <div class="admin-breadcrumbs mb-2"><a href="{{ route('admin.dashboard') }}">Админка</a><span>/</span><span>Заказы на дому</span></div>
        <h1 class="mb-1">Заказы на дому</h1>
        <p class="text-muted mb-0">Здесь хранятся уходы и выгулы с несколькими животными, когда клички пока неизвестны.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($orders->isEmpty())
        <div class="card"><div class="card-body text-muted">Активных заказов пока нет.</div></div>
    @else
        <div class="service-orders-grid">
            @foreach($orders as $order)
                @php
                    $animals = $order->animals->map(fn ($animal) => $animal->quantity.' '.($animal->animal?->name ?: $animal->category?->name ?: $animal->label ?: 'животное'))->join(', ');
                    $isUpcoming = $order->start_date->isFuture();
                    $isCurrent = !$isUpcoming && $order->end_date->greaterThanOrEqualTo(today());
                @endphp
                <article class="service-order-card">
                    <div class="service-order-card__top">
                        <span class="service-order-card__type">{{ ucfirst($order->service_type) }}</span>
                        <span class="badge {{ $isCurrent ? 'text-bg-success' : ($isUpcoming ? 'text-bg-primary' : 'text-bg-secondary') }}">{{ $isCurrent ? 'Сейчас' : ($isUpcoming ? 'Запланирован' : 'Прошёл') }}</span>
                    </div>
                    <h2 class="service-order-card__animals">{{ $animals ?: 'Состав животных не указан' }}</h2>
                    <p class="service-order-card__dates">{{ $order->start_date->locale('ru')->translatedFormat('j F') }} — {{ $order->end_date->locale('ru')->translatedFormat('j F') }}</p>
                    <dl class="service-order-card__details">
                        <div><dt>Клиент</dt><dd>{{ $order->client?->name ?: 'Не указан' }}</dd></div>
                        <div><dt>Цена в день</dt><dd>{{ number_format($order->daily_price, 0, '.', ' ') }} ₽</dd></div>
                        @if($order->address)<div><dt>Адрес</dt><dd>{{ $order->address }}</dd></div>@endif
                        @if($order->note)<div><dt>Комментарий</dt><dd>{{ $order->note }}</dd></div>@endif
                    </dl>
                    <div class="service-order-card__actions">
                        <button type="button" class="btn btn-primary js-edit-service-order"
                            data-id="{{ $order->id }}" data-client-id="{{ $order->client_id }}" data-service-type="{{ $order->service_type }}"
                            data-units="{{ $order->units_per_day }}" data-price="{{ $order->daily_price }}"
                            data-start="{{ $order->start_date->toDateString() }}" data-end="{{ $order->end_date->toDateString() }}"
                            data-address="{{ $order->address }}" data-note="{{ $order->note }}"
                            data-animals='@json($order->animals->map(fn ($animal) => ["label" => $animal->animal?->name ?: $animal->category?->name ?: $animal->label, "quantity" => $animal->quantity])->values())'>Редактировать</button>
                        <form method="POST" action="{{ route('admin.service-orders.archive', $order) }}">@csrf<button class="btn btn-outline-secondary">В архив</button></form>
                        <form method="POST" action="{{ route('admin.service-orders.destroy', $order) }}" class="js-delete-form" data-confirm="Удалить этот заказ?">@csrf @method('DELETE')<button class="btn btn-outline-danger" aria-label="Удалить"><i class="fa fa-trash"></i></button></form>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>

<div class="modal fade" id="serviceOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg"><form method="POST" class="modal-content" id="serviceOrderForm">
        @csrf @method('PUT')
        <div class="modal-header"><h5 class="modal-title">Заказ на дому</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><div class="row g-3">
            <div class="col-md-6"><label class="form-label">Клиент</label><select class="form-select" name="client_id" id="orderClient"><option value="">Не указан</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Услуга</label><select class="form-select" name="service_type" id="orderService"><option value="уход">Уход</option><option value="выгул">Выгул</option><option value="передержка">Передержка</option></select></div>
            <div class="col-md-3"><label class="form-label">Раз в день</label><input class="form-control" type="number" name="units_per_day" id="orderUnits" min="1" max="24"></div>
            <div class="col-md-4"><label class="form-label">Начало</label><input class="form-control" type="date" name="start_date" id="orderStart" required></div>
            <div class="col-md-4"><label class="form-label">Окончание</label><input class="form-control" type="date" name="end_date" id="orderEnd" required></div>
            <div class="col-md-4"><label class="form-label">Цена в день, ₽</label><input class="form-control" type="number" name="daily_price" id="orderPrice" min="0" required></div>
            <div class="col-12"><label class="form-label">Животные</label><div id="orderAnimals" class="d-grid gap-2"></div><button class="btn btn-sm btn-outline-primary mt-2" type="button" id="addOrderAnimal">Добавить животное</button><div class="form-text">Например: «кошка», «собака» или «Пухля». Количество указывается рядом.</div></div>
            <div class="col-md-6"><label class="form-label">Адрес</label><textarea class="form-control" name="address" id="orderAddress" rows="2"></textarea></div>
            <div class="col-md-6"><label class="form-label">Комментарий</label><textarea class="form-control" name="note" id="orderNote" rows="2"></textarea></div>
        </div></div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button><button class="btn btn-success">Сохранить</button></div>
    </form></div>
</div>

@push('styles')
<style>
.service-orders-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:18px}.service-order-card{background:#fff;border:1px solid #e6e9ef;border-radius:16px;padding:20px;box-shadow:0 4px 18px rgba(28,39,64,.05)}.service-order-card__top,.service-order-card__actions{display:flex;gap:8px;align-items:center;justify-content:space-between}.service-order-card__type{font-weight:700;color:#925e16;text-transform:capitalize}.service-order-card__animals{font-size:1.2rem;margin:18px 0 4px}.service-order-card__dates{color:#667085;margin:0 0 18px}.service-order-card__details{margin:0;border-top:1px solid #eef0f4;padding-top:12px}.service-order-card__details div{display:flex;gap:12px;margin:6px 0}.service-order-card__details dt{color:#7b8492;font-weight:500;min-width:95px}.service-order-card__details dd{margin:0}.service-order-card__actions{justify-content:flex-start;margin-top:18px;flex-wrap:wrap}.service-order-card__actions form{margin:0}.order-animal-row{display:grid;grid-template-columns:1fr 110px auto;gap:8px}@media(max-width:480px){.service-orders-grid{grid-template-columns:1fr}.service-order-card{padding:16px}.order-animal-row{grid-template-columns:1fr 80px auto}}
</style>
@endpush
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{const modal=new bootstrap.Modal(document.getElementById('serviceOrderModal')),form=document.getElementById('serviceOrderForm'),animals=document.getElementById('orderAnimals');const add=(animal={})=>{const index=animals.children.length;const row=document.createElement('div');row.className='order-animal-row';row.innerHTML=`<input class="form-control" name="animals[${index}][label]" placeholder="Кошка, собака или кличка" value="${String(animal.label||'').replaceAll('&','&amp;').replaceAll('"','&quot;')}"><input class="form-control" type="number" name="animals[${index}][quantity]" min="1" value="${animal.quantity||1}"><button class="btn btn-outline-danger" type="button" aria-label="Удалить"><i class="fa fa-xmark"></i></button>`;row.querySelector('button').addEventListener('click',()=>row.remove());animals.append(row)};document.getElementById('addOrderAnimal').addEventListener('click',()=>add());document.querySelectorAll('.js-edit-service-order').forEach(button=>button.addEventListener('click',()=>{form.action=`{{ url('/zooadmin/service-orders') }}/${button.dataset.id}`;document.getElementById('orderClient').value=button.dataset.clientId||'';document.getElementById('orderService').value=button.dataset.serviceType;document.getElementById('orderUnits').value=button.dataset.units;document.getElementById('orderPrice').value=button.dataset.price;document.getElementById('orderStart').value=button.dataset.start;document.getElementById('orderEnd').value=button.dataset.end;document.getElementById('orderAddress').value=button.dataset.address;document.getElementById('orderNote').value=button.dataset.note;animals.innerHTML='';JSON.parse(button.dataset.animals||'[]').forEach(add);if(!animals.children.length)add();modal.show()}));});
</script>
@endpush
@endsection
