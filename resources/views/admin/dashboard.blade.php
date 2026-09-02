@extends('admin.index')

@section('content')
@php
    $activeTotal = array_sum($summary['active']);
    $speciesColors = ['#7c5ce6', '#24a8a3', '#f4a261', '#3b82f6', '#e76f51', '#d16ba5', '#7a9e46', '#a3aab6', '#5a8dee', '#c0883e'];
    $speciesTotal = array_sum($species);
    $offset = 0;
    $segments = [];
    foreach ($species as $key => $count) {
        if (!$count || !$speciesTotal) continue;
        $next = $offset + ($count / $speciesTotal * 100);
        $color = $speciesColors[array_search($key, array_keys($species), true) % count($speciesColors)];
        $segments[] = $color.' '.$offset.'% '.$next.'%';
        $offset = $next;
    }
    $donut = $segments ? 'conic-gradient('.implode(', ', $segments).')' : '#e8edf2';
    $activeCategories = collect($summary['active'])
        ->filter()
        ->map(fn ($count, $key) => ($speciesLabels[$key] ?? 'Не указана').' '.$count)
        ->implode(' · ');
@endphp

<div class="dashboard-page">
    <header class="dashboard-head">
        <div>
            <h1>Главная</h1>
        </div>
        <div class="dashboard-head__actions">
            <div class="dashboard-periods" aria-label="Период аналитики">
                @foreach(['week' => 'Неделя', 'month' => 'Месяц', 'quarter' => 'Квартал', 'year' => 'Год'] as $key => $label)
                    <a href="{{ route('admin.dashboard', ['period' => $key]) }}" class="{{ $period === $key ? 'is-active' : '' }}">{{ $label }}</a>
                @endforeach
            </div>
            <button type="button" class="btn btn-dark dashboard-tariffs-button" data-bs-toggle="modal" data-bs-target="#tariffsModal">
                <i class="fa fa-sliders me-2"></i>Тарифы
            </button>
        </div>
    </header>

    @if(session('success'))
        <div class="alert alert-success dashboard-alert">{{ session('success') }}</div>
    @endif

    <section class="dashboard-summary" aria-label="Основные показатели">
        <article class="dashboard-stat dashboard-stat--accent">
            <span class="dashboard-stat__icon"><i class="fa fa-paw"></i></span>
            <p>Сейчас в работе</p>
            <strong>{{ $activeTotal }}</strong>
            <small>{{ $activeCategories ?: 'Нет активных записей' }}</small>
        </article>
        <article class="dashboard-stat">
            <span class="dashboard-stat__icon dashboard-stat__icon--blue"><i class="fa fa-user-plus"></i></span>
            <p>Новые клиенты</p>
            <strong>{{ $summary['new_clients'] }}</strong>
            <small>{{ $periodLabel }}</small>
        </article>
        <article class="dashboard-stat">
            <span class="dashboard-stat__icon dashboard-stat__icon--orange"><i class="fa fa-calendar-check"></i></span>
            <p>Рабочих дней</p>
            <strong>{{ $summary['working_days'] }}</strong>
            <small>{{ $summary['pet_days'] }} питомце-дн.</small>
        </article>
        <article class="dashboard-stat dashboard-stat--income">
            <span class="dashboard-stat__icon dashboard-stat__icon--green"><i class="fa fa-ruble-sign"></i></span>
            <p>Ожидаемая выручка</p>
            <strong>{{ number_format($summary['revenue'], 0, '.', ' ') }} ₽</strong>
        </article>
    </section>

    <section class="dashboard-grid">
        <article class="dashboard-card dashboard-card--chart">
            <div class="dashboard-card__head">
                <div>
                    <h2>График нагрузки</h2>
                </div>
                <span class="dashboard-card__hint">{{ $periodLabel }}</span>
            </div>
            <div id="workloadChart" class="workload-chart" data-chart='@json($chart)' aria-label="График нагрузки"></div>
        </article>

        <article class="dashboard-card dashboard-card--species">
            <div class="dashboard-card__head">
                <div>
                    <h2>Животные</h2>
                </div>
            </div>
            <div class="species-chart">
                <div class="species-donut" style="background: {{ $donut }};">
                    <div class="species-donut__center"><strong>{{ $speciesTotal }}</strong><span>питомцев</span></div>
                </div>
                <div class="species-legend">
                    @foreach($species as $key => $count)
                        <div class="species-legend__item">
                            <span style="background: {{ $speciesColors[$loop->index % count($speciesColors)] }};"></span>
                            <span>{{ $speciesLabels[$key] }}</span>
                            <strong>{{ $count }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>
        </article>

        <article class="dashboard-card dashboard-card--upcoming">
            <div class="dashboard-card__head">
                <div>
                    <p class="dashboard-card__eyebrow">Ближайшие 7 дней</p>
                    <h2>Заезды и выезды</h2>
                </div>
                <a href="{{ route('admin.boarding.index') }}">К календарю <i class="fa fa-arrow-right"></i></a>
            </div>
            <div class="upcoming-list">
                @forelse($upcoming as $event)
                    <div class="upcoming-event">
                        <span class="upcoming-event__date">{{ $event['date'] }}</span>
                        <span class="upcoming-event__type {{ $event['type'] === 'Заезд' ? 'is-arrival' : 'is-departure' }}">{{ $event['type'] }}</span>
                        <div class="upcoming-event__info"><strong>{{ $event['name'] }}</strong><small>{{ $event['service'] }}</small></div>
                    </div>
                @empty
                    <div class="dashboard-empty">На ближайшие семь дней заездов и выездов нет.</div>
                @endforelse
            </div>
        </article>
    </section>
</div>

<div class="modal fade admin-modal" id="tariffsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form method="POST" action="{{ route('admin.dashboard.tariffs.update') }}" class="modal-content tariffs-modal">
            @csrf
            <input type="hidden" name="period" value="{{ $period }}">
            <div class="modal-header">
                <div>
                    <p class="dashboard-card__eyebrow mb-1">Стандартные цены</p>
                    <h5 class="modal-title">Тарифы услуг</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-4">Новые записи получают цену из тарифа. Цену можно изменить в самой записи; уже сохранённые цены не меняются.</p>
                @foreach(['передержка' => 'Передержка, за сутки', 'выгул' => 'Выгул, за один раз', 'уход' => 'Уход, за один раз'] as $service => $label)
                    <section class="tariff-group">
                        <h6>{{ $label }}</h6>
                        <div class="row g-3">
                            @foreach(['cat' => 'Кошка', 'dog_small' => 'Мелкая собака', 'dog_large' => 'Средняя/крупная собака', 'small_pet' => 'Грызуны, птицы, рыбки', 'other' => 'Другие животные'] as $group => $groupLabel)
                                <label class="col-md-4 tariff-field">
                                    <span>{{ $groupLabel }}</span>
                                    <div class="input-group">
                                        <input type="number" min="0" max="100000" step="1" class="form-control" name="tariffs[{{ $service }}][{{ $group }}]" value="{{ $tariffs[$service][$group] }}" required>
                                        <span class="input-group-text">₽</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="submit" class="btn btn-dark">Сохранить тарифы</button>
            </div>
        </form>
    </div>
</div>

<style>
    .dashboard-page { max-width: 1500px; margin: 0 auto; color: #24303d; }
    .dashboard-head { display:flex; justify-content:space-between; gap:24px; align-items:flex-start; margin-bottom:26px; }
    .dashboard-kicker, .dashboard-card__eyebrow { margin:0 0 5px; color:#778596; font-size:.72rem; font-weight:800; letter-spacing:.1em; text-transform:uppercase; }
    .dashboard-head h1 { margin:0; font-size:clamp(1.65rem, 3vw, 2.25rem); font-weight:800; letter-spacing:-.04em; }
    .dashboard-subtitle { margin:7px 0 0; color:#667487; }
    .dashboard-head__actions { display:flex; align-items:center; gap:12px; flex-wrap:wrap; justify-content:flex-end; }
    .dashboard-periods { display:flex; padding:4px; border:1px solid #dce3ea; border-radius:12px; background:#fff; }
    .dashboard-periods a { padding:7px 10px; border-radius:8px; color:#687789; font-size:.85rem; font-weight:700; text-decoration:none; }
    .dashboard-periods a.is-active { background:#283644; color:#fff; box-shadow:0 3px 8px rgba(25,36,49,.16); }
    .dashboard-tariffs-button { border-radius:12px; padding:10px 14px; white-space:nowrap; }
    .dashboard-alert { margin-bottom:20px; }
    .dashboard-summary { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:16px; margin-bottom:16px; }
    .dashboard-stat, .dashboard-card { border:1px solid #e1e7ec; border-radius:20px; background:#fff; box-shadow:0 10px 28px rgba(32, 47, 61, .055); }
    .dashboard-stat { position:relative; min-height:168px; padding:20px; overflow:hidden; }
    .dashboard-stat--accent { border-color:#dcd5f9; background:linear-gradient(145deg,#fff 0%,#f5f2ff 100%); }
    .dashboard-stat--income { border-color:#caebdc; background:linear-gradient(145deg,#fff 0%,#effaf4 100%); }
    .dashboard-stat p { margin:0 0 10px; color:#6e7d8d; font-size:.86rem; font-weight:700; }
    .dashboard-stat strong { display:block; margin-bottom:7px; font-size:clamp(1.65rem,2.4vw,2.15rem); font-weight:800; line-height:1; letter-spacing:-.04em; }
    .dashboard-stat small { color:#788697; font-size:.77rem; }
    .dashboard-stat__icon { position:absolute; top:18px; right:18px; display:grid; width:36px; height:36px; place-items:center; border-radius:12px; background:#ece8ff; color:#7358d8; }
    .dashboard-stat__icon--blue { background:#e7f2ff; color:#287bc2; }.dashboard-stat__icon--orange{background:#fff0dc;color:#d47b17}.dashboard-stat__icon--green{background:#ddf5e8;color:#258958}
    .dashboard-grid { display:grid; min-width:0; grid-template-columns:minmax(0, 1.65fr) minmax(300px, .85fr); gap:16px; }
    .dashboard-card { min-width:0; padding:22px; }.dashboard-card--chart { min-height:350px; overflow:hidden; }.dashboard-card--upcoming { grid-column:1/-1; }
    .dashboard-card__head { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }.dashboard-card h2{margin:0;font-size:1.08rem;font-weight:800;}.dashboard-card__hint{padding:6px 9px;border-radius:999px;background:#f1f4f7;color:#6a7888;font-size:.75rem;font-weight:700}.dashboard-card__head a{color:#3a617f;font-size:.8rem;font-weight:700;text-decoration:none;white-space:nowrap}
    .dashboard-card__note { margin:13px 0 0; color:#8a96a4; font-size:.76rem; line-height:1.45; }
    .workload-chart { display:flex; width:100%; min-width:0; align-items:flex-end; gap:5px; height:220px; margin-top:25px; padding:10px 2px 26px; overflow:hidden; border-bottom:1px solid #e9edf1; }
    .workload-chart__item { position:relative; display:flex; flex:1 1 0; min-width:0; height:100%; align-items:flex-end; }.workload-chart__bar{width:100%;min-width:0;min-height:3px;border:0;border-radius:7px 7px 2px 2px;background:linear-gradient(180deg,#7b67df 0%,#9b90e8 100%);cursor:pointer;transition:filter .15s,transform .15s}.workload-chart__bar:hover{filter:brightness(.93);transform:translateY(-2px)}.workload-chart__label{position:absolute;bottom:-18px;left:0;width:100%;overflow:hidden;color:#8a96a4;font-size:.65rem;text-align:center;text-overflow:clip;white-space:nowrap}
    .species-chart { display:flex; align-items:center; gap:22px; min-height:250px; }.species-donut{position:relative;flex:0 0 154px;width:154px;height:154px;border-radius:50%;display:grid;place-items:center}.species-donut::after{position:absolute;inset:27px;border-radius:50%;background:#fff;content:''}.species-donut__center{position:relative;z-index:1;text-align:center}.species-donut__center strong{display:block;font-size:1.55rem}.species-donut__center span{color:#7c8895;font-size:.73rem}.species-legend{display:grid;gap:9px;width:100%}.species-legend__item{display:grid;grid-template-columns:10px 1fr auto;gap:8px;align-items:center;color:#677588;font-size:.81rem}.species-legend__item>span:first-child{width:9px;height:9px;border-radius:50%}.species-legend__item strong{color:#263440;font-size:.82rem}
    .upcoming-list { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; margin-top:18px; }.upcoming-event{display:grid;grid-template-columns:minmax(0,1fr) auto;grid-template-areas:"info type" "date date";align-items:start;gap:10px 12px;padding:14px;border:1px solid #e3e9ee;border-radius:15px;background:linear-gradient(145deg,#fff,#fbfcfd);box-shadow:0 5px 14px rgba(34,51,67,.035)}.upcoming-event__info{grid-area:info;min-width:0}.upcoming-event__date{grid-area:date;padding-top:9px;border-top:1px solid #edf1f4;color:#728195;font-size:.75rem;font-weight:700;white-space:nowrap}.upcoming-event__type{grid-area:type;padding:5px 8px;border-radius:999px;font-size:.67rem;font-weight:800;line-height:1;white-space:nowrap}.upcoming-event__type.is-arrival{background:#e3f6eb;color:#257546}.upcoming-event__type.is-departure{background:#fff0e0;color:#aa611c}.upcoming-event strong{display:block;overflow:hidden;font-size:.92rem;line-height:1.2;text-overflow:ellipsis;white-space:nowrap}.upcoming-event small{display:block;margin-top:4px;color:#8995a3;font-size:.74rem}.dashboard-empty{padding:20px;border:1px dashed #d6dde4;border-radius:12px;color:#788695;font-size:.86rem}
    .tariff-group + .tariff-group{margin-top:24px;padding-top:22px;border-top:1px solid #edf0f3}.tariff-group h6{margin-bottom:14px;font-weight:800}.tariff-field>span{display:block;margin-bottom:6px;color:#627083;font-size:.82rem;font-weight:700}
    @media (max-width:1199px){.dashboard-summary{grid-template-columns:repeat(2,minmax(0,1fr))}.dashboard-grid{grid-template-columns:1fr}.dashboard-card--upcoming{grid-column:auto}.upcoming-list{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media (max-width:767px){.dashboard-head{flex-direction:column}.dashboard-head__actions{justify-content:space-between;width:100%}.dashboard-periods{width:100%;justify-content:space-between}.dashboard-periods a{padding:7px 8px;font-size:.76rem}.dashboard-summary{grid-template-columns:1fr}.dashboard-stat{min-height:142px}.dashboard-card{padding:17px;border-radius:16px}.species-chart{gap:16px}.species-donut{flex-basis:128px;width:128px;height:128px}.species-donut::after{inset:23px}.upcoming-list{grid-template-columns:1fr}.workload-chart{height:180px;gap:2px;padding-right:0;padding-left:0}.workload-chart__label{font-size:.56rem}.dashboard-card--chart{min-height:300px}}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const chart = document.getElementById('workloadChart');
    if (!chart) return;
    const data = JSON.parse(chart.dataset.chart || '[]');
    const max = Math.max(...data.map(item => item.units), 1);
    const visibleLabels = data.length > 15 ? Math.ceil(data.length / 8) : 1;

    chart.innerHTML = data.map((item, index) => {
        const height = Math.max(3, (item.units / max) * 100);
        const label = index % visibleLabels === 0 ? item.label : '';
        const revenue = new Intl.NumberFormat('ru-RU').format(item.revenue);
        return `<div class="workload-chart__item"><button class="workload-chart__bar" type="button" style="height:${height}%" title="${item.label}: ${item.units} питомце-дн. · ${revenue} ₽" aria-label="${item.label}: ${item.units} питомце-дней"></button><span class="workload-chart__label">${label}</span></div>`;
    }).join('');
});
</script>
@endsection
