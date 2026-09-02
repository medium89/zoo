@extends('admin.index')

@section('content')
<header class="orders-archive-head">
        <div>
            <h1>Архив заказов</h1>
        </div>
        <a href="{{ route('admin.service-orders.index') }}" class="btn btn-outline-primary"><i class="fa fa-arrow-left"></i> К заказам</a>
 </header>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <x-admin.filters :action="route('admin.service-orders.archive.index')" :filters="$filters" placeholder="Клиент или питомец">
        <label class="admin-filter-bar__field">Услуга<select name="service" class="form-select"><option value="">Все</option><option value="передержка" @selected(($filters['service'] ?? '') === 'передержка')>Передержка</option><option value="выгул" @selected(($filters['service'] ?? '') === 'выгул')>Выгул</option><option value="уход" @selected(($filters['service'] ?? '') === 'уход')>Уход</option></select></label>
    </x-admin.filters>

    @forelse($orders as $order)
        <article class="archived-order-card">
            <div class="archived-order-card__main">
                <div>
                    <strong class="archived-order-card__client"><img class="archived-order-card__avatar" src="{{ $order->client?->avatarUrl() ?: asset('images/client-placeholder.svg') }}" alt=""> {{ $order->client?->name ?: 'Клиент не указан' }}</strong>
                    <span class="archived-order-card__period"><i class="fa fa-calendar-days"></i> {{ $order->start_date->locale('ru')->translatedFormat('j F Y') }} — {{ $order->end_date->locale('ru')->translatedFormat('j F Y') }}</span>
                </div>
                <div class="archived-order-card__animals">
                    @foreach($order->animals as $position)
                        <span>{{ $position->quantity > 1 ? $position->quantity.' × ' : '' }}{{ $position->animal?->name ?: $position->label ?: 'Без клички' }}@if($position->services->isNotEmpty()) — {{ $position->services->pluck('service_type')->map(fn ($type) => ucfirst($type))->join(', ') }}@endif</span>
                    @endforeach
                </div>
            </div>
            <div class="archived-order-card__aside">
                <strong>{{ number_format($order->daily_price, 0, '.', ' ') }} ₽<small>/ день</small></strong>
                <small>В архиве с {{ $order->archived_at?->format('d.m.Y') }}</small>
                <form method="POST" action="{{ route('admin.service-orders.destroy', $order) }}" class="js-delete-form" data-confirm="Удалить заказ из архива без возможности восстановления?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" aria-label="Удалить заказ"><i class="fa fa-trash"></i></button></form>
            </div>
        </article>
    @empty
        <div class="orders-archive-empty"><i class="fa fa-box-open"></i><h2>Архив пока пуст</h2><p>Завершённые заказы появятся здесь автоматически.</p></div>
    @endforelse
@push('styles')<style>
.orders-archive-head{display:flex;justify-content:space-between;align-items:flex-start;gap:18px;margin-bottom:26px}.orders-archive-head h1{margin:0 0 5px;font-size:2rem}.orders-archive-head p{margin:0;color:#697586}.archived-order-card{display:flex;justify-content:space-between;gap:20px;align-items:center;padding:17px 20px;margin-bottom:12px;border:1px solid #e2e8f0;border-radius:14px;background:#fff;box-shadow:0 4px 14px rgba(27,39,57,.04)}.archived-order-card__main{min-width:0}.archived-order-card__client{display:inline-flex;align-items:center;color:#263648;margin-right:16px}.archived-order-card__avatar{width:26px;height:26px;margin-right:7px;border-radius:50%;object-fit:cover;background:#eaf3ff}.archived-order-card__period{color:#687587;font-size:.87rem}.archived-order-card__animals{display:flex;flex-wrap:wrap;gap:7px;margin-top:10px}.archived-order-card__animals span{padding:5px 8px;border-radius:7px;background:#f4f7fa;color:#526276;font-size:.8rem}.archived-order-card__aside{display:grid;grid-template-columns:auto auto;align-items:center;gap:3px 12px;flex:0 0 auto;text-align:right}.archived-order-card__aside strong{font-size:1rem;color:#263648}.archived-order-card__aside strong small,.archived-order-card__aside>small{display:block;color:#7b8796;font-size:.72rem;font-weight:600}.archived-order-card__aside form{grid-row:1 / 3;grid-column:2;margin:0}.orders-archive-empty{text-align:center;padding:70px 20px;border:1px dashed #cfd8e5;border-radius:18px;background:#fff;color:#687586}.orders-archive-empty i{font-size:2rem;color:#9aa9bc}.orders-archive-empty h2{margin:12px 0 4px;font-size:1.2rem}.orders-archive-empty p{margin:0}@media(max-width:650px){.orders-archive-head,.archived-order-card{align-items:stretch;flex-direction:column}.orders-archive-head .btn{width:100%}.archived-order-card__aside{grid-template-columns:1fr auto;text-align:left}.archived-order-card__aside form{grid-column:2;grid-row:1 / 3}.archived-order-card__client{margin:0 0 7px}.archived-order-card__period{display:block}}#admin-content{font-weight:400!important}
</style>@endpush
@endsection
