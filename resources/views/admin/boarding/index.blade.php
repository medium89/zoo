@extends('admin.index')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Передержка: календарь приёма</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.boarding.index', ['year' => $year]) }}" class="btn btn-outline-secondary">Обновить</a>
            <a href="{{ route('admin.boarding.export', ['year' => $year]) }}" class="btn btn-outline-primary">Экспорт CSV</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Запись</span>
            <span class="badge bg-secondary" id="boardingMode">добавление</span>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.boarding.store') }}"
                  method="POST"
                  class="row g-3 align-items-end"
                  id="boardingForm"
                  data-store-action="{{ route('admin.boarding.store') }}"
                  data-update-template="{{ route('admin.boarding.update', ['boarding' => '__ID__']) }}">
                @csrf
                <input type="hidden" name="_method" value="PUT" id="boardingMethod" disabled>
                <div class="col-md-4">
                    <label class="form-label">Кличка</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Описание</label>
                    <input type="text" name="description" class="form-control" placeholder="Напр. особенности, контакт" maxlength="255">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Тип услуги</label>
                    <select name="service_type" class="form-select" required>
                        <option value="передержка">передержка</option>
                        <option value="выгул">выгул</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">Начало</label>
                    <input type="text" name="start_date" class="form-control js-date" autocomplete="off" required placeholder="ГГГГ-ММ-ДД">
                </div>
                <div class="col-md-1">
                    <label class="form-label">Окончание</label>
                    <input type="text" name="end_date" class="form-control js-date" autocomplete="off" required placeholder="ГГГГ-ММ-ДД">
                </div>
                <div class="col-12 text-end">
                    <div class="d-flex justify-content-end gap-2 flex-wrap">
                        <button type="button" class="btn btn-outline-secondary d-none" id="boardingCancel">Отмена</button>
                        <button type="submit" class="btn btn-success" id="boardingSubmit">Добавить</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Последние заявки</div>
        <div class="card-body">
            @if($latest->count())
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Кличка</th>
                                <th>Описание</th>
                                <th>Тип услуги</th>
                                <th>Период</th>
                                <th>Создано</th>
                                <th class="text-end">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($latest as $row)
                                <tr>
                                    <td>{{ $row->id }}</td>
                                    <td>{{ $row->name }}</td>
                                    <td>{{ $row->description }}</td>
                                    <td>{{ $row->service_type }}</td>
                                    <td>{{ $row->start_date->toDateString() }} — {{ $row->end_date->toDateString() }}</td>
                                    <td>{{ $row->created_at->format('d.m.Y H:i') }}</td>
                                    <td class="text-end">
                                        <button type="button"
                                                class="btn btn-sm btn-outline-primary js-edit-entry"
                                                data-id="{{ $row->id }}"
                                                data-name="{{ $row->name }}"
                                                data-description="{{ $row->description }}"
                                                data-service-type="{{ $row->service_type }}"
                                                data-start="{{ $row->start_date->toDateString() }}"
                                                data-end="{{ $row->end_date->toDateString() }}">
                                            Редактировать
                                        </button>
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
                <select id="yearSelect" class="form-select form-select-sm" style="width:auto;">
                    @for($y = $year-2; $y <= $year+3; $y++)
                        <option value="{{ $y }}" {{ $y==$year?'selected':'' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
        </div>
        <div class="card-body">
            <div id="calendarGrid" class="calendar-grid"></div>
        </div>
    </div>
</div>

<style>
    .calendar-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; }
    .cal-month { border:1px solid #e5e7eb; border-radius:8px; padding:10px; box-shadow:0 4px 10px rgba(0,0,0,0.04); }
    .cal-month h5 { font-size:16px; margin-bottom:8px; }
    .cal-header, .cal-row { display:grid; grid-template-columns: repeat(7, 1fr); text-align:center; font-size:12px; }
    .cal-cell { padding:6px 0; border-radius:6px; position:relative; }
    .cal-cell.day { cursor:pointer; }
    .cal-cell.day:hover { background:#f3f4f6; }
    .cal-cell.day.busy { background:#e9f8ef; border:1px solid #6cc17b; }
    .cal-cell.day.conflict { background:#fff3e0; border:1px solid #f0a500; }
    .tooltip-box { position:absolute; z-index:20; background:#fff; border:1px solid #ddd; box-shadow:0 10px 30px rgba(0,0,0,0.15); padding:10px; border-radius:8px; font-size:12px; min-width:200px; white-space:pre-line; }
    .dp-popover { position:absolute; background:#fff; border-radius:10px; padding:12px; width:320px; max-width:calc(100vw - 32px); box-shadow:0 10px 40px rgba(0,0,0,0.18); border:1px solid #e5e7eb; z-index:2000; }
    .dp-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; gap:8px; }
    .dp-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:4px; font-size:13px; }
    .dp-day { text-align:center; padding:8px 0; border-radius:6px; cursor:pointer; }
    .dp-day:hover { background:#f3f4f6; }
</style>

@php($entriesJson = $entries->toJson())

<script>
document.addEventListener('DOMContentLoaded', function(){
    const entries = JSON.parse(@json($entriesJson));
    let state = { year: {{ $year }}, entries };
    const MONTHS = ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'];

    const grid = document.getElementById('calendarGrid');
    const yearSelect = document.getElementById('yearSelect');
    const form = document.getElementById('boardingForm');
    const methodInput = document.getElementById('boardingMethod');
    const submitBtn = document.getElementById('boardingSubmit');
    const cancelBtn = document.getElementById('boardingCancel');
    const modeBadge = document.getElementById('boardingMode');

    const formFields = {
        name: form.querySelector('[name="name"]'),
        description: form.querySelector('[name="description"]'),
        service: form.querySelector('[name="service_type"]'),
        start: form.querySelector('[name="start_date"]'),
        end: form.querySelector('[name="end_date"]'),
    };

    function rangeDays(start, end){
        const res = [];
        let cur = new Date(start);
        const endDate = new Date(end);
        while(cur <= endDate){
            res.push(cur.toISOString().slice(0,10));
            cur.setDate(cur.getDate()+1);
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

    function render(){
        const map = buildMap();
        grid.innerHTML='';
        for(let m=0;m<12;m++){
            const first = new Date(state.year, m, 1);
            const wrap = document.createElement('div');
            wrap.className='cal-month';
            wrap.innerHTML = `<h5>${MONTHS[m]} ${state.year}</h5>`;

            const header = document.createElement('div'); header.className='cal-header';
            ['Пн','Вт','Ср','Чт','Пт','Сб','Вс'].forEach(d=>{
                const c=document.createElement('div'); c.textContent=d; header.appendChild(c);
            });
            wrap.appendChild(header);

            const body = document.createElement('div'); body.className='cal-row';
            const startIdx = (first.getDay()+6)%7; // Monday start
            for(let i=0;i<startIdx;i++){ body.appendChild(document.createElement('div')); }
            const daysInMonth = new Date(state.year, m+1, 0).getDate();
            for(let d=1; d<=daysInMonth; d++){
                const cell = document.createElement('div');
                cell.className = 'cal-cell day';
                cell.textContent = d;
                const dateStr = new Date(state.year, m, d).toISOString().slice(0,10);
                const list = map[dateStr] || [];
                if(list.length>0){
                    cell.classList.add(list.length>1 ? 'conflict' : 'busy');
                    cell.dataset.tooltip = list.map(x=>`${x.name} • ${x.service_type} (${x.start_date} — ${x.end_date})`).join('\n');
                }
                body.appendChild(cell);
            }
            wrap.appendChild(body);
            grid.appendChild(wrap);
        }
        bindTooltips();
    }

    function bindTooltips(){
        let tooltip;
        grid.querySelectorAll('.cal-cell.day').forEach(cell=>{
            cell.addEventListener('mouseenter', ()=>{
                if(!cell.dataset.tooltip) return;
                tooltip = document.createElement('div');
                tooltip.className='tooltip-box';
                tooltip.textContent = cell.dataset.tooltip;
                document.body.appendChild(tooltip);
                const rect = cell.getBoundingClientRect();
                tooltip.style.left = (rect.left + window.scrollX) + 'px';
                tooltip.style.top = (rect.bottom + 6 + window.scrollY) + 'px';
                // keep tooltip within viewport
                const tRect = tooltip.getBoundingClientRect();
                const maxLeft = window.innerWidth - tRect.width - 12;
                if (tRect.right > window.innerWidth - 12) {
                    tooltip.style.left = Math.max(12, maxLeft) + window.scrollX + 'px';
                }
                if (tRect.bottom > window.innerHeight - 12) {
                    tooltip.style.top = (rect.top + window.scrollY - tRect.height - 8) + 'px';
                }
            });
            cell.addEventListener('mouseleave', ()=>{
                if(tooltip){ tooltip.remove(); tooltip=null; }
            });
        });
    }

    async function fetchYear(y){
        const res = await fetch(`{{ route('admin.boarding.data') }}?year=${y}`);
        const json = await res.json();
        state.year = y; state.entries = json.entries; render();
    }

    yearSelect.addEventListener('change', (e)=>{
        fetchYear(parseInt(e.target.value,10));
    });

    // Добавление/редактирование записей
    function setCreateMode(){
        methodInput.disabled = true;
        form.action = form.dataset.storeAction;
        submitBtn.textContent = 'Добавить';
        modeBadge.textContent = 'добавление';
        modeBadge.classList.remove('bg-warning');
        modeBadge.classList.add('bg-secondary');
        cancelBtn.classList.add('d-none');
        form.reset();
    }

    function setEditMode(payload){
        methodInput.disabled = false;
        form.action = form.dataset.updateTemplate.replace('__ID__', payload.id);
        formFields.name.value = payload.name || '';
        formFields.description.value = payload.description || '';
        formFields.service.value = payload.service_type || 'передержка';
        formFields.start.value = payload.start || '';
        formFields.end.value = payload.end || '';
        submitBtn.textContent = 'Сохранить';
        modeBadge.textContent = `редактирование #${payload.id}`;
        modeBadge.classList.remove('bg-secondary');
        modeBadge.classList.add('bg-warning');
        cancelBtn.classList.remove('d-none');
        form.scrollIntoView({ behavior:'smooth', block:'start' });
    }

    cancelBtn.addEventListener('click', (e)=>{ e.preventDefault(); setCreateMode(); });
    document.querySelectorAll('.js-edit-entry').forEach(btn=>{
        btn.addEventListener('click', ()=>{
            setEditMode({
                id: btn.dataset.id,
                name: btn.dataset.name,
                description: btn.dataset.description,
                service_type: btn.dataset.serviceType,
                start: btn.dataset.start,
                end: btn.dataset.end,
            });
        });
    });

    setCreateMode();

    // Кастомный datepicker около поля
    const dateInputs = Array.from(document.querySelectorAll('.js-date'));
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

    function openPicker(input){
        closePicker();
        const now = input.value ? new Date(input.value) : new Date();
        let curYear = now.getFullYear();
        let curMonth = now.getMonth();

        const pop = document.createElement('div');
        pop.className='dp-popover';
        pop.setAttribute('role','dialog');
        pop.addEventListener('click', (e)=>e.stopPropagation());

        const header = document.createElement('div'); header.className='dp-header';
        const prev = document.createElement('button'); prev.type='button'; prev.className='btn btn-light btn-sm'; prev.textContent='‹';
        const next = document.createElement('button'); next.type='button'; next.className='btn btn-light btn-sm'; next.textContent='›';
        const titleWrap = document.createElement('div'); titleWrap.className='d-flex align-items-center gap-2 flex-grow-1';
        const titleText = document.createElement('div'); titleText.className='fw-semibold';
        const yearSelectDp = document.createElement('select'); yearSelectDp.className='form-select form-select-sm'; yearSelectDp.style.width='auto';
        titleWrap.appendChild(titleText); titleWrap.appendChild(yearSelectDp);
        header.appendChild(prev); header.appendChild(titleWrap); header.appendChild(next);
        pop.appendChild(header);

        const gridDp = document.createElement('div'); gridDp.className='dp-grid';
        pop.appendChild(gridDp);

        function renderDp(){
            gridDp.innerHTML='';
            yearSelectDp.innerHTML='';
            for(let y=curYear-2; y<=curYear+5; y++){
                const opt=document.createElement('option'); opt.value=y; opt.textContent=y;
                if(y===curYear) opt.selected=true; yearSelectDp.appendChild(opt);
            }
            const first = new Date(curYear, curMonth, 1);
            const startIdx = (first.getDay()+6)%7;
            ['Пн','Вт','Ср','Чт','Пт','Сб','Вс'].forEach(d=>{
                const c=document.createElement('div'); c.textContent=d; c.style.fontWeight='600'; c.style.fontSize='12px'; gridDp.appendChild(c);
            });
            for(let i=0;i<startIdx;i++){ const e=document.createElement('div'); gridDp.appendChild(e); }
            const dim = new Date(curYear, curMonth+1,0).getDate();
            for(let d=1; d<=dim; d++){
                const c=document.createElement('div'); c.className='dp-day'; c.textContent=d;
                c.addEventListener('click', ()=>{
                    const mm = String(curMonth+1).padStart(2,'0');
                    const dd = String(d).padStart(2,'0');
                    input.value = `${curYear}-${mm}-${dd}`;
                    closePicker();
                });
                gridDp.appendChild(c);
            }
            titleText.textContent = `${MONTHS[curMonth]} ${curYear}`;
            yearSelectDp.value = curYear;
        }
        renderDp();

        prev.addEventListener('click', ()=>{ curMonth--; if(curMonth<0){ curMonth=11; curYear--; } renderDp(); });
        next.addEventListener('click', ()=>{ curMonth++; if(curMonth>11){ curMonth=0; curYear++; } renderDp(); });
        yearSelectDp.addEventListener('change', (e)=>{ curYear=parseInt(e.target.value,10); renderDp(); });

        document.body.appendChild(pop);
        positionPicker(pop, input);

        activePicker = { pop, input };
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
