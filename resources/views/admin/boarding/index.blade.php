@extends('admin.index')

@section('content')
<div class="container-fluid boarding-calendar-page">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Передержка: календарь приёма</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.boarding.animals') }}" class="btn btn-outline-secondary">Животные</a>
            <a href="{{ route('admin.boarding.archive') }}" class="btn btn-outline-primary">Архив</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($serviceOrders->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between"><span>Заказы на дому</span><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.service-orders.index') }}">Все заказы</a></div>
            <div class="card-body py-2">
                @foreach($serviceOrders as $order)
                    <div class="d-flex gap-3 align-items-center justify-content-between py-2 border-bottom flex-wrap">
                        <div><strong>{{ ucfirst($order->service_type) }}:</strong> {{ $order->animals->map(fn($animal) => $animal->quantity.' '.($animal->animal?->name ?: $animal->category?->name ?: $animal->label ?: 'животное'))->join(', ') ?: 'животные не указаны' }}</div>
                        <div class="text-muted">{{ $order->start_date->locale('ru')->translatedFormat('j F') }} — {{ $order->end_date->locale('ru')->translatedFormat('j F') }}</div>
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.service-orders.index') }}">Открыть</a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-header">Запись</div>
        <div class="card-body">
            <form action="{{ route('admin.boarding.store') }}"
                  method="POST"
                  class="row g-3 align-items-end"
                  id="boardingForm">
                @csrf
                <div class="col-md-3">
                    <label class="form-label">Кличка</label>
                    <input type="text" name="name" class="form-control" required list="animalHints" autocomplete="off" placeholder="Выберите или введите">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Хозяин</label>
                    <select name="client_id" class="form-select">
                        <option value="">Без хозяина</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}{{ $client->phone ? ' · '.$client->phone : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Категория</label>
                    <select name="category_id" class="form-select js-category">
                        <option value="">Не указана</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Описание</label>
                    <input type="text" name="description" class="form-control" placeholder="Напр. особенности, контакт" maxlength="255">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Тип услуги</label>
                    <select name="service_type" class="form-select" required>
                        <option value="передержка">передержка</option>
                        <option value="выгул">выгул</option>
                        <option value="уход">уход</option>
                    </select>
                </div>
                <div class="col-md-2 col-lg-2">
                    <label class="form-label">Начало</label>
                    <input type="text" name="start_date" class="form-control js-date" autocomplete="off" inputmode="numeric" maxlength="10" required placeholder="YYYY-MM-DD">
                </div>
                <div class="col-md-2 col-lg-2">
                    <label class="form-label">Окончание</label>
                    <input type="text" name="end_date" class="form-control js-date" autocomplete="off" inputmode="numeric" maxlength="10" required placeholder="YYYY-MM-DD">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Заметка</label>
                    <input type="text" name="note" class="form-control" placeholder="Дополнительная информация">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Раз в день</label>
                    <input type="number" name="units_per_day" class="form-control js-units-per-day" min="1" max="24" value="1" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Цена за услугу, ₽</label>
                    <input type="number" name="unit_price" class="form-control js-unit-price" min="0" max="100000" value="500" required>
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-success">Добавить</button>
                </div>
            </form>
            <datalist id="animalHints">
                @foreach($animals as $animal)
                    <option value="{{ $animal->name }}" data-description="{{ $animal->description }}" data-client-id="{{ $animal->client_id }}" data-category-id="{{ $animal->category_id }}" data-dog-size="{{ $animal->dog_size }}">{{ trim((($animal->category?->name ?: $animal->species) ? ($animal->category?->name ?: $animal->species).' · ' : '').($animal->client?->name ?: '').($animal->description ? ' · '.$animal->description : ''), ' ·') }}</option>
                @endforeach
            </datalist>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Последние заявки</div>
        <div class="card-body">
            @if($latest->count())
                <div class="table-responsive">
                    <table class="table align-middle boarding-latest-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Кличка</th>
                                <th>Хозяин</th>
                                <th>Описание</th>
                                <th>Тип услуги</th>
                                <th>Период</th>
                                <th>Источник</th>
                                <th>Создано</th>
                                <th class="text-end"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($latest as $row)
                                <tr>
                                    <td>{{ $row->id }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            @if($row->animal?->photos->first())
                                                <img src="{{ Storage::url($row->animal->photos->first()->path) }}" alt="{{ $row->animal->name }}" style="width:38px;height:38px;object-fit:cover;border-radius:8px;">
                                            @endif
                                            @if($row->animal)
                                                <a href="{{ route('admin.animals.show', $row->animal) }}">{{ $row->animal->name }}</a>
                                            @else
                                                {{ $row->name }}
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if($row->client ?: $row->animal?->client)
                                            <a href="{{ route('admin.clients.show', $row->client ?: $row->animal->client) }}">{{ ($row->client ?: $row->animal->client)->name }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $row->description ?: '—' }}</td>
                                    <td>{{ $row->service_type }}</td>
                                    <td>{{ $row->start_date->toDateString() }} — {{ $row->end_date->toDateString() }}</td>
                                    <td>{{ $row->source ?? 'admin' }}</td>
                                    <td>{{ $row->created_at->format('d.m.Y H:i') }}</td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end flex-wrap gap-1">
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-primary js-edit-entry"
                                                    data-id="{{ $row->id }}"
                                                    data-name="{{ $row->name }}"
                                                    data-category-id="{{ $row->animal?->category_id }}"
                                                    data-dog-size="{{ $row->animal?->dog_size }}"
                                                    data-client-id="{{ $row->client_id }}"
                                                    data-description="{{ $row->description }}"
                                                    data-service-type="{{ $row->service_type }}"
                                                    data-units-per-day="{{ $row->units_per_day ?: 1 }}"
                                                    data-unit-price="{{ $row->unit_price }}"
                                                    data-start="{{ $row->start_date->toDateString() }}"
                                                    data-end="{{ $row->end_date->toDateString() }}"
                                                    data-note="{{ $row->note }}">
                                                Редактировать
                                            </button>
                                            <a href="{{ route('admin.boarding.tasks.index', $row) }}" class="btn btn-sm btn-outline-success">Действия</a>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-secondary js-archive-entry"
                                                    data-id="{{ $row->id }}"
                                                    data-name="{{ $row->name }}"
                                                    data-url="{{ route('admin.boarding.archive.store', $row) }}">
                                                В архив
                                            </button>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger js-delete-entry"
                                                    data-id="{{ $row->id }}"
                                                    data-name="{{ $row->name }}"
                                                    data-url="{{ route('admin.boarding.destroy', $row) }}">
                                                Удалить
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-muted">Пока нет заявок.</div>
            @endif
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Календарь передержки</span>
            <div class="d-flex align-items-center gap-2">
                <label class="mb-0">Год</label>
                @php($yearOpts = range($minYear, $maxYear))
                <select id="yearSelect" class="form-select form-select-sm" style="width:auto;">
                    <option value="all" {{ $year === 'all' ? 'selected' : '' }}>Все активные месяцы</option>
                    @foreach($yearOpts as $y)
                        <option value="{{ $y }}" {{ $y==$year?'selected':'' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="card-body">
            <div id="calendarGrid" class="calendar-grid"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="editBoardingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST"
              class="modal-content"
              id="editBoardingForm"
              data-update-template="{{ route('admin.boarding.update', ['boarding' => '__ID__']) }}">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Редактировать запись</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Кличка</label>
                        <input type="text" name="name" class="form-control" required list="animalHints" autocomplete="off" placeholder="Выберите или введите">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Хозяин</label>
                        <select name="client_id" class="form-select">
                            <option value="">Без хозяина</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->name }}{{ $client->phone ? ' · '.$client->phone : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Категория</label>
                        <select name="category_id" class="form-select js-category">
                            <option value="">Не указана</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Описание</label>
                        <input type="text" name="description" class="form-control" placeholder="Напр. особенности, контакт" maxlength="255">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Тип услуги</label>
                        <select name="service_type" class="form-select" required>
                            <option value="передержка">передержка</option>
                            <option value="выгул">выгул</option>
                            <option value="уход">уход</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Начало</label>
                        <input type="text" name="start_date" class="form-control js-date" autocomplete="off" inputmode="numeric" maxlength="10" required placeholder="YYYY-MM-DD">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Окончание</label>
                        <input type="text" name="end_date" class="form-control js-date" autocomplete="off" inputmode="numeric" maxlength="10" required placeholder="YYYY-MM-DD">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Раз в день</label>
                        <input type="number" name="units_per_day" class="form-control js-units-per-day" min="1" max="24" value="1" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Цена за услугу, ₽</label>
                        <input type="number" name="unit_price" class="form-control js-unit-price" min="0" max="100000" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Заметка</label>
                        <textarea name="note" class="form-control" rows="3"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </div>
        </form>
    </div>
</div>

<style>
    .boarding-latest-table.admin-flex-table tr {
        display:grid;
        position:relative;
        grid-template-columns:repeat(4, minmax(0, 1fr));
        align-items:start;
        gap:18px 24px;
        padding:20px;
    }
    .boarding-latest-table.admin-flex-table td {
        display:block;
        min-width:0;
        padding:0;
    }
    .boarding-latest-table.admin-flex-table td::before {
        display:block;
        min-width:0;
        margin-bottom:4px;
        color:#7b8794;
        font-size:.75rem;
        font-weight:700;
        letter-spacing:.04em;
        text-transform:uppercase;
    }
    .boarding-latest-table.admin-flex-table td:first-child {
        position:absolute;
        top:16px;
        right:18px;
        display:inline-flex;
        align-items:center;
        min-height:28px;
        padding:3px 9px;
        border:1px solid #d8dee5;
        border-radius:999px;
        background:#f8fafc;
        font-size:.82rem;
        font-weight:700;
    }
    .boarding-latest-table.admin-flex-table td:first-child::before {
        display:none;
    }
    .boarding-latest-table.admin-flex-table td:nth-child(2) a,
    .boarding-latest-table.admin-flex-table td:nth-child(2) {
        font-weight:700;
        font-size:1.05rem;
    }
    .boarding-latest-table.admin-flex-table td:last-child {
        grid-column:1 / -1;
        margin-top:2px;
        padding-top:14px;
        border-top:1px solid #edf0f2;
    }
    .boarding-latest-table.admin-flex-table td:last-child::before {
        display:none;
    }
    .boarding-latest-table.admin-flex-table td:last-child .d-flex {
        justify-content:flex-start !important;
    }
    .boarding-latest-table .btn {
        white-space:nowrap;
    }
    @media (max-width: 991.98px) {
        .boarding-latest-table.admin-flex-table tr {
            grid-template-columns:repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 575.98px) {
        .boarding-latest-table.admin-flex-table tr {
            grid-template-columns:1fr;
            gap:12px;
            padding:16px;
        }
        .boarding-latest-table.admin-flex-table td:last-child .d-flex {
            justify-content:flex-start !important;
        }
    }
    .boarding-calendar-page { min-width:0; overflow-x:hidden; }
    .calendar-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(min(260px, 100%), 1fr)); gap:16px; min-width:0; }
    .cal-month { min-width:0; border:1px solid #e5e7eb; border-radius:8px; padding:10px; box-shadow:0 4px 10px rgba(0,0,0,0.04); }
    .cal-month h5 { font-size:16px; margin-bottom:8px; }
    .cal-header, .cal-row { display:grid; grid-template-columns: repeat(7, 1fr); text-align:center; font-size:12px; }
    .cal-cell { padding:6px 0; border-radius:6px; position:relative; }
    .cal-cell.day { cursor:pointer; touch-action:manipulation; }
    .cal-cell.day:hover { background:#f3f4f6; }
    .cal-cell.day.busy { background:#e9f8ef; border:1px solid #6cc17b; }
    .cal-cell.day.conflict { background:#fff3e0; border:1px solid #f0a500; }
    .tooltip-box { position:absolute; z-index:20; background:#fff; border:1px solid #ddd; box-shadow:0 10px 30px rgba(0,0,0,0.15); padding:10px; border-radius:8px; font-size:12px; min-width:200px; white-space:pre-line; }
    .dp-popover { position:absolute; background:#fff; border-radius:10px; padding:12px; width:320px; max-width:calc(100vw - 32px); box-shadow:0 10px 40px rgba(0,0,0,0.18); border:1px solid #e5e7eb; z-index:2000; user-select:none; }
    .dp-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; gap:8px; }
    .dp-nav { min-width:40px; font-size:18px; line-height:1; }
    .dp-nav:hover { background:#eef2f7; }
    .dp-grid-wrap { touch-action:none; overscroll-behavior:contain; border-radius:8px; }
    .dp-grid-wrap.is-swiping { cursor:grabbing; }
    .dp-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:4px; font-size:13px; }
    .dp-day { text-align:center; padding:8px 0; border-radius:6px; cursor:pointer; border:1px solid transparent; }
    .dp-day:hover { background:#f3f4f6; }
    .dp-day.is-selected { background:#e9f8ef; border-color:#6cc17b; font-weight:600; }
    .dp-hint { margin-top:8px; font-size:11px; color:#6b7280; text-align:center; }
    body.date-picker-open .admin-to-top {
        opacity:0;
        visibility:hidden;
        pointer-events:none;
    }
    body.boarding-calendar-active .admin-to-top {
        display:none;
    }
    @media (hover:none), (pointer:coarse) {
        .tooltip-box {
            position:fixed;
            right:12px;
            bottom:12px;
            left:12px;
            z-index:2100;
            min-width:0;
            max-width:none;
            max-height:45vh;
            overflow-y:auto;
            font-size:14px;
        }
    }
</style>

<div class="modal fade" id="archiveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content" id="archiveForm">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Отправить в архив</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="archiveText"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="submit" class="btn btn-primary">Архивировать</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content" id="deleteForm">
            @csrf
            @method('DELETE')
            <div class="modal-header">
                <h5 class="modal-title text-danger">Удалить запись</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="deleteText"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="submit" class="btn btn-danger">Удалить</button>
            </div>
        </form>
    </div>
</div>

@php($entriesJson = $entries->toJson())

<script>
document.addEventListener('DOMContentLoaded', function(){
    document.body.classList.add('boarding-calendar-active');
    const entries = JSON.parse(@json($entriesJson));
    const tariffs = @json($tariffs);
    let state = { year: @json($year), entries, minYear: {{ $minYear }}, maxYear: {{ $maxYear }} };
    const MONTHS = ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'];

    const grid = document.getElementById('calendarGrid');
    const yearSelect = document.getElementById('yearSelect');
    const createForm = document.getElementById('boardingForm');
    const editModalEl = document.getElementById('editBoardingModal');
    const editForm = document.getElementById('editBoardingForm');
    const editModal = editModalEl ? new bootstrap.Modal(editModalEl) : null;

    const createFields = {
        name: createForm.querySelector('[name="name"]'),
        client: createForm.querySelector('[name="client_id"]'),
        category: createForm.querySelector('[name="category_id"]'),
        description: createForm.querySelector('[name="description"]'),
        service: createForm.querySelector('[name="service_type"]'),
        start: createForm.querySelector('[name="start_date"]'),
        end: createForm.querySelector('[name="end_date"]'),
        note: createForm.querySelector('[name="note"]'),
        units: createForm.querySelector('[name="units_per_day"]'),
        price: createForm.querySelector('[name="unit_price"]'),
    };

    const editFields = {
        name: editForm.querySelector('[name="name"]'),
        client: editForm.querySelector('[name="client_id"]'),
        category: editForm.querySelector('[name="category_id"]'),
        description: editForm.querySelector('[name="description"]'),
        service: editForm.querySelector('[name="service_type"]'),
        start: editForm.querySelector('[name="start_date"]'),
        end: editForm.querySelector('[name="end_date"]'),
        note: editForm.querySelector('[name="note"]'),
        units: editForm.querySelector('[name="units_per_day"]'),
        price: editForm.querySelector('[name="unit_price"]'),
    };

    function parseIsoDate(value){
        if(!value) return null;
        const [year, month, day] = value.split('-').map(Number);
        if(!year || !month || !day) return null;
        return new Date(Date.UTC(year, month - 1, day));
    }

    function buildUtcDate(year, month, day = 1){
        return new Date(Date.UTC(year, month, day));
    }

    function formatIsoDate(date){
        return [
            date.getUTCFullYear(),
            String(date.getUTCMonth() + 1).padStart(2, '0'),
            String(date.getUTCDate()).padStart(2, '0'),
        ].join('-');
    }

    function changeUtcMonth(year, month, delta){
        const date = buildUtcDate(year, month, 1);
        date.setUTCMonth(date.getUTCMonth() + delta);
        return {
            year: date.getUTCFullYear(),
            month: date.getUTCMonth(),
        };
    }

    function formatDateInputValue(value){
        const digits = String(value || '').replace(/\D/g, '').slice(0, 8);
        if(digits.length <= 4) return digits;
        if(digits.length <= 6) return `${digits.slice(0, 4)}-${digits.slice(4)}`;
        return `${digits.slice(0, 4)}-${digits.slice(4, 6)}-${digits.slice(6)}`;
    }

    function caretFromDigitsCount(value, digitsCount){
        if(digitsCount <= 0) return 0;
        let seen = 0;
        for(let i = 0; i < value.length; i++){
            if(/\d/.test(value[i])) seen += 1;
            if(seen >= digitsCount) return i + 1;
        }
        return value.length;
    }

    function bindDateMask(input){
        input.addEventListener('input', ()=>{
            const selectionStart = input.selectionStart ?? input.value.length;
            const digitsBeforeCaret = input.value.slice(0, selectionStart).replace(/\D/g, '').length;
            const formatted = formatDateInputValue(input.value);
            input.value = formatted;
            const caret = caretFromDigitsCount(formatted, digitsBeforeCaret);
            input.setSelectionRange(caret, caret);
        });

        input.addEventListener('blur', ()=>{
            input.value = formatDateInputValue(input.value);
        });
    }

    function rangeDays(start, end){
        const res = [];
        let cur = parseIsoDate(start);
        const endDate = parseIsoDate(end);
        if(!cur || !endDate) return res;
        while(cur <= endDate){
            res.push(formatIsoDate(cur));
            cur.setUTCDate(cur.getUTCDate() + 1);
        }
        return res;
    }

    function buildMap(){
        const map = {};
        state.entries.forEach(e=>{
            rangeDays(e.start_date, e.end_date).forEach(d=>{
                if(!map[d]) map[d]=[];
                map[d].push(e);
            })
        });
        return map;
    }

    function monthsToRender(list, baseYear){
        const collected = [];
        if(baseYear !== 'all'){
            for(let m=0; m<12; m++){
                collected.push({ year: parseInt(baseYear,10), month: m });
            }
        }
        list.forEach(entry => {
            const start = parseIsoDate(entry.start_date);
            const end = parseIsoDate(entry.end_date);
            if(!start || !end) return;
            let cur = buildUtcDate(start.getUTCFullYear(), start.getUTCMonth(), 1);
            const last = buildUtcDate(end.getUTCFullYear(), end.getUTCMonth(), 1);
            while(cur <= last){
                collected.push({ year: cur.getUTCFullYear(), month: cur.getUTCMonth() });
                cur.setUTCMonth(cur.getUTCMonth() + 1);
            }
        });
        const seen = new Set();
        const uniq = [];
        collected.forEach(item=>{
            const key = `${item.year}-${item.month}`;
            if(seen.has(key)) return;
            seen.add(key);
            uniq.push(item);
        });
        uniq.sort((a,b)=> buildUtcDate(a.year, a.month, 1) - buildUtcDate(b.year, b.month, 1));
        return uniq;
    }

    function render(){
        const map = buildMap();
        const months = monthsToRender(state.entries, state.year);
        grid.innerHTML='';
        months.forEach(({year, month})=>{
            const first = buildUtcDate(year, month, 1);
            const wrap = document.createElement('div');
            wrap.className='cal-month';
            wrap.innerHTML = `<h5>${MONTHS[month]} ${year}</h5>`;

            const header = document.createElement('div'); header.className='cal-header';
            ['Пн','Вт','Ср','Чт','Пт','Сб','Вс'].forEach(d=>{
                const c=document.createElement('div'); c.textContent=d; header.appendChild(c);
            });
            wrap.appendChild(header);

            const body = document.createElement('div'); body.className='cal-row';
            const startIdx = (first.getUTCDay()+6)%7; // Monday start
            for(let i=0;i<startIdx;i++){ body.appendChild(document.createElement('div')); }
            const daysInMonth = buildUtcDate(year, month + 1, 0).getUTCDate();
            for(let d=1; d<=daysInMonth; d++){
                const cell = document.createElement('div');
                cell.className = 'cal-cell day';
                cell.textContent = d;
                const dateStr = formatIsoDate(buildUtcDate(year, month, d));
                const list = map[dateStr] || [];
                if(list.length>0){
                    cell.classList.add(list.length>1 ? 'conflict' : 'busy');
                    cell.dataset.tooltip = list.map(x=>{
                        const client = x.client_name ? ` · ${x.client_name}` : '';
                        const species = x.species ? ` (${x.species})` : '';
                        return `${x.name}${species}${client} • ${x.service_type} (${x.start_date} — ${x.end_date})`;
                    }).join('\n');
                }
                body.appendChild(cell);
            }
            wrap.appendChild(body);
            grid.appendChild(wrap);
        });
        bindTooltips();
    }

    let calendarTooltip = null;
    const usesTouchCalendar = window.matchMedia('(hover:none), (pointer:coarse)').matches;

    function removeCalendarTooltip(){
        if(!calendarTooltip) return;
        calendarTooltip.remove();
        calendarTooltip = null;
    }

    function showCalendarTooltip(cell){
        removeCalendarTooltip();
        calendarTooltip = document.createElement('div');
        calendarTooltip.className='tooltip-box';
        calendarTooltip.textContent = cell.dataset.tooltip;
        document.body.appendChild(calendarTooltip);

        if(usesTouchCalendar) return;

        const rect = cell.getBoundingClientRect();
        calendarTooltip.style.left = (rect.left + window.scrollX) + 'px';
        calendarTooltip.style.top = (rect.bottom + 6 + window.scrollY) + 'px';
        const tRect = calendarTooltip.getBoundingClientRect();
        const maxLeft = window.innerWidth - tRect.width - 12;
        if (tRect.right > window.innerWidth - 12) {
            calendarTooltip.style.left = (Math.max(12, maxLeft) + window.scrollX) + 'px';
        }
        if (tRect.bottom > window.innerHeight - 12) {
            calendarTooltip.style.top = (rect.top + window.scrollY - tRect.height - 8) + 'px';
        }
    }

    function bindTooltips(){
        removeCalendarTooltip();
        grid.querySelectorAll('.cal-cell.day').forEach(cell=>{
            cell.addEventListener('mouseenter', ()=>{
                if(usesTouchCalendar || !cell.dataset.tooltip) return;
                showCalendarTooltip(cell);
            });
            cell.addEventListener('mouseleave', ()=>{
                if(!usesTouchCalendar) removeCalendarTooltip();
            });
            cell.addEventListener('click', (event)=>{
                if(!usesTouchCalendar || !cell.dataset.tooltip) return;
                event.preventDefault();
                event.stopPropagation();
                showCalendarTooltip(cell);
            });
        });
    }

    document.addEventListener('click', ()=>{
        if(usesTouchCalendar) removeCalendarTooltip();
    });

    async function fetchYear(y){
        const res = await fetch(`{{ route('admin.boarding.data') }}?year=${y}`);
        const json = await res.json();
        state.year = y;
        state.entries = json.entries;
        if(json.minYear) state.minYear = json.minYear;
        if(json.maxYear) state.maxYear = json.maxYear;
        render();
    }

    yearSelect.addEventListener('change', (e)=>{
        fetchYear(e.target.value);
    });

    function openEditModal(payload){
        if(!editModal) return;

        editForm.action = editForm.dataset.updateTemplate.replace('__ID__', payload.id);
        editFields.name.value = payload.name || '';
        editFields.client.value = payload.client_id || '';
        editFields.description.value = payload.description || '';
        editFields.service.value = payload.service_type || 'передержка';
        editFields.start.value = payload.start || '';
        editFields.end.value = payload.end || '';
        editFields.note.value = payload.note || '';
        editFields.units.value = payload.units_per_day || 1;
        editFields.category.value = payload.category_id || '';
        editFields.dogSize = payload.dog_size || '';
        if(payload.unit_price){
            editFields.price.value = payload.unit_price;
            editFields.price.dataset.manual = 'true';
        } else {
            delete editFields.price.dataset.manual;
            syncDefaultPrice(editFields, true);
        }
        editModal.show();
    }

    function bindNameHints(fields){
        const options = Array.from(document.querySelectorAll('#animalHints option'));
        const maybeFill = () => {
            const found = options.find(opt => opt.value === fields.name.value);
            if(found && !fields.description.value){
                fields.description.value = found.dataset.description || '';
            }
            if(found && fields.client && !fields.client.value && found.dataset.clientId){
                fields.client.value = found.dataset.clientId;
            }
            if(fields.category) fields.category.value = found?.dataset.categoryId || '';
            fields.dogSize = found?.dataset.dogSize || '';
            syncDefaultPrice(fields);
        };
        fields.name.addEventListener('change', maybeFill);
        fields.name.addEventListener('blur', maybeFill);
    }

    function animalGroup(category){
        const value = String(category || '').trim().toLowerCase();
        if(['кот', 'кошка', 'котёнок', 'котенок', 'кошки'].includes(value)) return 'cat';
        if(['собака', 'пёс', 'пес', 'щенок', 'собаки'].includes(value)) return 'dog_large';
        if(['грызуны', 'птицы', 'рыбки'].includes(value)) return 'small_pet';
        return 'other';
    }

    function syncDefaultPrice(fields, force = false){
        if(!fields.price || (!force && fields.price.dataset.manual === 'true')) return;
        const service = fields.service?.value || 'передержка';
        let group = animalGroup(fields.category?.selectedOptions?.[0]?.textContent);
        if(group === 'dog_large' && fields.dogSize === 'small') group = 'dog_small';
        fields.price.value = tariffs?.[service]?.[group] ?? tariffs?.[service]?.other ?? 500;
        fields.price.dataset.manual = 'false';
    }

    [createFields, editFields].forEach(fields => {
        fields.price?.addEventListener('input', () => { fields.price.dataset.manual = 'true'; });
        fields.service?.addEventListener('change', () => syncDefaultPrice(fields, true));
        fields.category?.addEventListener('change', () => syncDefaultPrice(fields, true));
    });

    document.querySelectorAll('.js-edit-entry').forEach(btn=>{
        btn.addEventListener('click', ()=>{
            openEditModal({
                id: btn.dataset.id,
                name: btn.dataset.name,
                category_id: btn.dataset.categoryId,
                dog_size: btn.dataset.dogSize,
                client_id: btn.dataset.clientId,
                description: btn.dataset.description,
                service_type: btn.dataset.serviceType,
                units_per_day: btn.dataset.unitsPerDay,
                unit_price: btn.dataset.unitPrice,
                start: btn.dataset.start,
                end: btn.dataset.end,
                note: btn.dataset.note,
            });
        });
    });

    editModalEl?.addEventListener('hidden.bs.modal', ()=>{
        editForm.reset();
        editForm.action = '';
    });

    const archiveModalEl = document.getElementById('archiveModal');
    const archiveForm = document.getElementById('archiveForm');
    const archiveText = document.getElementById('archiveText');
    const archiveModal = archiveModalEl ? new bootstrap.Modal(archiveModalEl) : null;
    document.querySelectorAll('.js-archive-entry').forEach(btn=>{
        btn.addEventListener('click', ()=>{
            if(!archiveModal) return;
            archiveForm.action = btn.dataset.url;
            archiveText.textContent = `Отправить запись #${btn.dataset.id} (${btn.dataset.name}) в архив?`;
            archiveModal.show();
        });
    });

    const deleteModalEl = document.getElementById('deleteModal');
    const deleteForm = document.getElementById('deleteForm');
    const deleteText = document.getElementById('deleteText');
    const deleteModal = deleteModalEl ? new bootstrap.Modal(deleteModalEl) : null;
    document.querySelectorAll('.js-delete-entry').forEach(btn=>{
        btn.addEventListener('click', ()=>{
            if(!deleteModal) return;
            deleteForm.action = btn.dataset.url;
            deleteText.textContent = `Удалить запись #${btn.dataset.id} (${btn.dataset.name}) без возможности восстановления?`;
            deleteModal.show();
        });
    });

    bindNameHints(createFields);
    bindNameHints(editFields);
    syncDefaultPrice(createFields, true);

    // Кастомный datepicker около поля
    const dateInputs = Array.from(document.querySelectorAll('.js-date'));
    dateInputs.forEach(bindDateMask);
    let activePicker = null;
    const outsideHandler = (e) => {
        if (!activePicker) return;
        if (activePicker.pop.contains(e.target) || activePicker.input === e.target) return;
        closePicker();
    };
    const escHandler = (e) => { if (e.key === 'Escape') closePicker(); };
    const scrollHandler = () => closePicker();

    dateInputs.forEach(input=>{
        input.addEventListener('focus', (e)=>{ e.stopPropagation(); openPicker(input); });
        input.addEventListener('click', (e)=>{ e.stopPropagation(); openPicker(input); });
    });

    [createForm, editForm].forEach(currentForm => currentForm.addEventListener('submit', ()=>{
        dateInputs.forEach(input => {
            input.value = formatDateInputValue(input.value);
        });
    }));

    function openPicker(input){
        closePicker();
        const selectedDate = parseIsoDate(input.value);
        const now = new Date();
        let curYear = selectedDate ? selectedDate.getUTCFullYear() : now.getFullYear();
        let curMonth = selectedDate ? selectedDate.getUTCMonth() : now.getMonth();

        const pop = document.createElement('div');
        pop.className='dp-popover';
        pop.setAttribute('role','dialog');
        pop.addEventListener('click', (e)=>e.stopPropagation());

        const header = document.createElement('div'); header.className='dp-header';
        const prev = document.createElement('button'); prev.type='button'; prev.className='btn btn-light btn-sm dp-nav'; prev.textContent='‹'; prev.setAttribute('aria-label', 'Предыдущий месяц');
        const next = document.createElement('button'); next.type='button'; next.className='btn btn-light btn-sm dp-nav'; next.textContent='›'; next.setAttribute('aria-label', 'Следующий месяц');
        const titleWrap = document.createElement('div'); titleWrap.className='d-flex align-items-center gap-2 flex-grow-1';
        const titleText = document.createElement('div'); titleText.className='fw-semibold';
        const yearSelectDp = document.createElement('select'); yearSelectDp.className='form-select form-select-sm'; yearSelectDp.style.width='auto';
        titleWrap.appendChild(titleText); titleWrap.appendChild(yearSelectDp);
        header.appendChild(prev); header.appendChild(titleWrap); header.appendChild(next);
        pop.appendChild(header);

        const gridWrap = document.createElement('div');
        gridWrap.className = 'dp-grid-wrap';
        const gridDp = document.createElement('div'); gridDp.className='dp-grid';
        gridWrap.appendChild(gridDp);
        pop.appendChild(gridWrap);

        const hint = document.createElement('div');
        hint.className = 'dp-hint';
        hint.textContent = 'Свайп влево/вправо переключает месяц';
        pop.appendChild(hint);

        let suppressClicksUntil = 0;
        let swipeState = null;

        function changeMonth(delta){
            const nextMonth = changeUtcMonth(curYear, curMonth, delta);
            curYear = nextMonth.year;
            curMonth = nextMonth.month;
            renderDp();
        }

        function resetSwipeState(){
            swipeState = null;
            gridWrap.classList.remove('is-swiping');
        }

        function renderDp(){
            gridDp.innerHTML='';
            yearSelectDp.innerHTML='';
            const selectedValue = formatDateInputValue(input.value);
            for(let y=curYear-2; y<=curYear+5; y++){
                const opt=document.createElement('option'); opt.value=y; opt.textContent=y;
                if(y===curYear) opt.selected=true; yearSelectDp.appendChild(opt);
            }
            const first = buildUtcDate(curYear, curMonth, 1);
            const startIdx = (first.getUTCDay()+6)%7;
            ['Пн','Вт','Ср','Чт','Пт','Сб','Вс'].forEach(d=>{
                const c=document.createElement('div'); c.textContent=d; c.style.fontWeight='600'; c.style.fontSize='12px'; gridDp.appendChild(c);
            });
            for(let i=0;i<startIdx;i++){ const e=document.createElement('div'); gridDp.appendChild(e); }
            const dim = buildUtcDate(curYear, curMonth + 1, 0).getUTCDate();
            for(let d=1; d<=dim; d++){
                const c=document.createElement('div'); c.className='dp-day'; c.textContent=d;
                const dateValue = formatIsoDate(buildUtcDate(curYear, curMonth, d));
                if(dateValue === selectedValue){
                    c.classList.add('is-selected');
                }
                c.addEventListener('click', ()=>{
                    if(Date.now() < suppressClicksUntil) return;
                    const mm = String(curMonth+1).padStart(2,'0');
                    const dd = String(d).padStart(2,'0');
                    input.value = `${curYear}-${mm}-${dd}`;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    closePicker();
                });
                gridDp.appendChild(c);
            }
            titleText.textContent = `${MONTHS[curMonth]} ${curYear}`;
            yearSelectDp.value = curYear;
        }
        renderDp();

        [prev, next].forEach(button => {
            button.addEventListener('pointerdown', (e)=>e.stopPropagation());
            button.addEventListener('click', (e)=>{
                e.preventDefault();
                e.stopPropagation();
                changeMonth(button === prev ? -1 : 1);
            });
        });
        yearSelectDp.addEventListener('change', (e)=>{ curYear=parseInt(e.target.value,10); renderDp(); });

        gridWrap.addEventListener('click', (e)=>{
            if(Date.now() < suppressClicksUntil){
                e.preventDefault();
                e.stopPropagation();
            }
        }, true);

        gridWrap.addEventListener('pointerdown', (e)=>{
            if(e.pointerType === 'mouse' && e.button !== 0) return;
            swipeState = {
                pointerId: e.pointerId,
                startX: e.clientX,
                startY: e.clientY,
            };
        });

        gridWrap.addEventListener('pointermove', (e)=>{
            if(!swipeState || e.pointerId !== swipeState.pointerId) return;
            const dx = e.clientX - swipeState.startX;
            const dy = e.clientY - swipeState.startY;
            if(Math.abs(dx) > 12 && Math.abs(dx) > Math.abs(dy)){
                gridWrap.classList.add('is-swiping');
            }
        });

        const handleSwipeEnd = (e)=>{
            if(!swipeState || e.pointerId !== swipeState.pointerId) return;
            const dx = e.clientX - swipeState.startX;
            const dy = e.clientY - swipeState.startY;
            const shouldNavigate = Math.abs(dx) >= 48 && Math.abs(dx) > Math.abs(dy);
            resetSwipeState();
            if(!shouldNavigate) return;
            suppressClicksUntil = Date.now() + 250;
            changeMonth(dx < 0 ? 1 : -1);
        };

        gridWrap.addEventListener('pointerup', handleSwipeEnd);
        gridWrap.addEventListener('pointercancel', resetSwipeState);

        document.body.appendChild(pop);
        positionPicker(pop, input);

        activePicker = { pop, input };
        document.body.classList.add('date-picker-open');
        document.addEventListener('click', outsideHandler);
        document.addEventListener('keydown', escHandler);
        window.addEventListener('resize', scrollHandler, true);
        window.addEventListener('scroll', scrollHandler, true);
    }

    function positionPicker(pop, input){
        const rect = input.getBoundingClientRect();
        let top = rect.bottom + window.scrollY + 8;
        let left = rect.left + window.scrollX;
        pop.style.top = `${top}px`;
        pop.style.left = `${left}px`;
        const popRect = pop.getBoundingClientRect();
        if (popRect.right > window.innerWidth - 12) {
            left = window.innerWidth - popRect.width - 12 + window.scrollX;
            pop.style.left = `${Math.max(left, 12 + window.scrollX)}px`;
        }
        if (popRect.bottom > window.innerHeight - 12) {
            const newTop = rect.top + window.scrollY - popRect.height - 8;
            pop.style.top = `${Math.max(newTop, window.scrollY + 12)}px`;
        }
    }

    function closePicker(){
        document.body.classList.remove('date-picker-open');
        if(!activePicker) return;
        activePicker.pop.remove();
        activePicker = null;
        document.removeEventListener('click', outsideHandler);
        document.removeEventListener('keydown', escHandler);
        window.removeEventListener('resize', scrollHandler, true);
        window.removeEventListener('scroll', scrollHandler, true);
    }

    render();
});
</script>
@endsection
