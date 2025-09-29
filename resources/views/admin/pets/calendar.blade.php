@extends('admin.index')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Календарь</h1>
    <div>
        <a href="{{ route('admin.pets.index') }}" class="btn btn-secondary">К списку питомцев</a>
    </div>
</div>

<ul class="nav nav-tabs mb-3">
  <li class="nav-item">
    <a class="nav-link" href="{{ route('admin.pets.index') }}">Питомцы</a>
  </li>
  <li class="nav-item">
    <a class="nav-link active" aria-current="page" href="#">Календарь</a>
  </li>
</ul>

<div class="card mb-3">
  <div class="card-body">
    <button class="btn btn-outline-primary mb-2" id="togglePetsPanel">Показать/скрыть список питомцев</button>
    <div id="petsPanel" class="border rounded p-2" style="display:none;">
      <div class="row g-2 align-items-end mb-2">
        <div class="col-md-4">
          <label class="form-label">Поиск по имени</label>
          <input type="text" class="form-control" id="petSearch" placeholder="Начните вводить имя...">
        </div>
        <div class="col-md-4">
          <label class="form-label">Фильтр по типу</label>
          <select class="form-select" id="petTypeFilter">
            <option value="">Все типы</option>
            <option value="кошка">Кошка</option>
            <option value="собака">Собака</option>
            <option value="грызун">Грызун</option>
            <option value="птица">Птица</option>
            <option value="прочее">Прочее</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label d-block">Времена суток</label>
          <div class="btn-group" role="group" aria-label="slots">
            <button type="button" class="btn btn-outline-secondary js-slot" data-slot="утро">Утро</button>
            <button type="button" class="btn btn-outline-secondary js-slot" data-slot="день">День</button>
            <button type="button" class="btn btn-outline-secondary js-slot" data-slot="вечер">Вечер</button>
          </div>
        </div>
      </div>
      <div id="petCards" class="d-flex flex-wrap gap-2">
        @foreach($pets as $p)
          @php($photo = $p->photos->first())
          <div class="card js-pet-card" data-name="{{ mb_strtolower($p->name) }}" data-type="{{ $p->animal_type }}" data-pet-id="{{ $p->id }}" style="width: 200px; cursor:pointer;">
            <div class="d-flex align-items-center p-2">
              <img src="{{ $photo ? asset('storage/'.$photo->path) : 'https://via.placeholder.com/48x48?text=' }}" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:6px;">
              <div class="ms-2">
                <div class="fw-bold">{{ $p->name }}</div>
                <div class="text-muted small">{{ $p->animal_type ?: '—' }}</div>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>
</div>

<div class="d-flex align-items-center justify-content-between mb-2">
  <div class="d-flex align-items-center gap-2">
    <button class="btn btn-light" id="prevMonth">←</button>
    <div id="monthTitle" class="fw-bold fs-5"></div>
    <button class="btn btn-light" id="nextMonth">→</button>
  </div>
  <input type="month" id="monthPicker" class="form-control" style="max-width:220px;">
</div>

<div class="mb-2">
  <strong>Легенда:</strong>
  <span class="badge" style="background:#28a745">Передержка</span>
  <span class="badge" style="background:#0d6efd">Выгул</span>
  <span class="badge" style="background:#fd7e14">Кормление</span>
</div>

<div id="calendar" class="border rounded p-2 position-relative" style="min-height:340px;"></div>
<div id="dayTooltip" class="calendar-tooltip d-none"></div>

<style>
  .calendar-tooltip{position:fixed;z-index:3000;background:#fff;border:1px solid #dee2e6;box-shadow:0 6px 20px rgba(0,0,0,.15);border-radius:8px;padding:8px 10px;max-width:320px;pointer-events:none}
  .calendar-tooltip .item{display:flex;align-items:center;gap:8px;margin:4px 0}
  .calendar-tooltip img{width:32px;height:32px;border-radius:6px;object-fit:cover}
  .calendar-tooltip .badge{font-size:11px}
  .calendar-day:hover{outline:2px solid #0d6efd33}
  .btn-slot-active{color:#fff !important}
</style>

<div id="loader" class="position-fixed top-0 start-0 w-100 h-100 d-none" style="background:rgba(255,255,255,0.6);z-index:2000;">
  <div class="position-absolute top-50 start-50 translate-middle text-center">
    <div class="spinner-border text-primary" role="status"></div>
    <div class="mt-2">Сохранение...</div>
  </div>
  
</div>
@endsection

@section('scripts')
<script>
// Localization
const MONTHS = ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'];
const DOW = ['Пн','Вт','Ср','Чт','Пт','Сб','Вс'];
const SERVICE_COLORS = { 'передержка':'#28a745', 'выгул':'#0d6efd', 'кормление':'#fd7e14' };

let state = {
  month: new Date(),
  entries: [],
  selectedPetId: null,
  selectedService: null,
  selectedSlots: new Set(),
  pets: @json($petsData)
};

function yyyymm(d){ return d.getFullYear()+ '-' + String(d.getMonth()+1).padStart(2,'0'); }
function startOfMonth(d){ return new Date(d.getFullYear(), d.getMonth(), 1); }
function endOfMonth(d){ return new Date(d.getFullYear(), d.getMonth()+1, 0); }
function isoDate(d){ return d.toISOString().slice(0,10); }

function renderHeader(){
  document.getElementById('monthTitle').textContent = MONTHS[state.month.getMonth()] + ' ' + state.month.getFullYear();
  document.getElementById('monthPicker').value = yyyymm(state.month);
}

function renderCalendar(){
  const cal = document.getElementById('calendar');
  cal.innerHTML = '';
  const wrap = document.createElement('div');
  wrap.className = 'calendar-grid';
  wrap.style.display = 'grid';
  wrap.style.gridTemplateColumns = 'repeat(7, 1fr)';
  wrap.style.gap = '4px';
  // Header row
  for(const d of DOW){
    const h = document.createElement('div'); h.className='text-center fw-bold'; h.textContent=d; h.style.cursor='pointer'; wrap.appendChild(h);
  }
  const first = startOfMonth(state.month);
  let startIdx = (first.getDay() + 6) % 7; // Monday first
  const daysInMonth = endOfMonth(state.month).getDate();
  for(let i=0;i<startIdx;i++){ const d=document.createElement('div'); d.className='text-muted'; d.textContent=''; wrap.appendChild(d);}    
  for(let day=1; day<=daysInMonth; day++){
    const cell = document.createElement('div');
    cell.className = 'p-2 border rounded position-relative calendar-day';
    cell.style.cursor = 'pointer';
    cell.style.minHeight = '64px';
    const date = new Date(state.month.getFullYear(), state.month.getMonth(), day);
    const dateStr = isoDate(date);
    cell.dataset.date = dateStr;
    const num = document.createElement('div'); num.className='small fw-bold'; num.textContent = String(day); cell.appendChild(num);

    // Paint backgrounds by entries
    const dayEntries = state.entries.filter(e=> e.date === dateStr);
    if(dayEntries.length){
      const colors = Array.from(new Set(dayEntries.map(e=> SERVICE_COLORS[e.service] || '#999')));
      if(colors.length === 1){ cell.style.background = colors[0] + '66'; }
      else if(colors.length === 2){ cell.style.background = `linear-gradient(135deg, ${colors[0]}66 50%, ${colors[1]}66 50%)`; }
      else {
        // stripes for 3+
        cell.style.background = `repeating-linear-gradient(45deg, ${colors.map(c=>`${c}55 0 8px, transparent 8px 16px`).join(',')})`;
      }
    }

    // Popup tooltip events
    cell.addEventListener('mouseenter', (ev)=> showTooltipForDate(dateStr, ev));
    cell.addEventListener('mousemove', positionTooltip);
    cell.addEventListener('mouseleave', hideTooltip);
    cell.addEventListener('click', onDayClick);
    wrap.appendChild(cell);
  }
  cal.appendChild(wrap);
}

async function loadEntries(){
  const month = yyyymm(state.month);
  const res = await fetch(`{{ route('admin.pets.calendar.data') }}?month=${month}`, {headers:{'X-Requested-With':'XMLHttpRequest'}});
  const data = await res.json();
  state.entries = data.entries || [];
}

function showLoader(v){ document.getElementById('loader').classList.toggle('d-none', !v); }

// Tooltip helpers
function showTooltipForDate(dateStr, ev){
  const entries = state.entries.filter(e=> e.date === dateStr);
  if(!entries.length){ hideTooltip(); return; }
  // group by pet+service to merge slots
  const map = new Map();
  entries.forEach(e=>{
    const key = `${e.pet_id}|${e.service}`;
    if(!map.has(key)) map.set(key, {pet_id:e.pet_id, pet_name:e.pet_name, pet_photo:e.pet_photo, service:e.service, slots:new Set()});
    map.get(key).slots.add(e.slot);
  });
  const tooltip = document.getElementById('dayTooltip');
  const html = Array.from(map.values()).map(item=>{
    const color = SERVICE_COLORS[item.service] || '#6c757d';
    const slots = Array.from(item.slots).join(', ');
    const img = item.pet_photo ? `<img src="${item.pet_photo}" alt="">` : `<div style="width:32px;height:32px;border-radius:6px;background:#e9ecef;"></div>`;
    return `<div class="item">${img}<div><div class="fw-bold">${item.pet_name}</div><div><span class="badge" style="background:${color};">${item.service}</span> <span class="text-muted">${slots}</span></div></div></div>`;
  }).join('');
  tooltip.innerHTML = html;
  tooltip.classList.remove('d-none');
  positionTooltip(ev);
}
function positionTooltip(ev){
  const tooltip = document.getElementById('dayTooltip');
  if(tooltip.classList.contains('d-none')) return;
  const pad = 12;
  let x = ev.clientX + pad;
  let y = ev.clientY + pad;
  const rect = tooltip.getBoundingClientRect();
  if (x + rect.width > window.innerWidth - 8) x = ev.clientX - rect.width - pad;
  if (y + rect.height > window.innerHeight - 8) y = ev.clientY - rect.height - pad;
  tooltip.style.left = x + 'px';
  tooltip.style.top = y + 'px';
}
function hideTooltip(){
  const tooltip = document.getElementById('dayTooltip');
  tooltip.classList.add('d-none');
}

async function onDayClick(e){
  if(!state.selectedPetId){ alert('Сначала выберите питомца сверху.'); return; }
  if(!state.selectedService){ alert('У выбранного питомца нет отмеченных услуг.'); return; }
  if(state.selectedSlots.size === 0){ alert('Выберите: утро, день или вечер.'); return; }
  const date = e.currentTarget.dataset.date;
  showLoader(true);
  try{
    const res = await fetch(`{{ route('admin.pets.calendar.toggle') }}`, {
      method: 'POST',
      headers: {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':'{{ csrf_token() }}'},
      body: JSON.stringify({ date, pet_id: state.selectedPetId, service: state.selectedService, slots: Array.from(state.selectedSlots) })
    });
    await loadEntries();
    renderCalendar();
  } finally { showLoader(false); }
}

function bindPetsPanel(){
  const panel = document.getElementById('petsPanel');
  document.getElementById('togglePetsPanel').addEventListener('click', ()=>{
    panel.style.display = panel.style.display==='none' ? '' : 'none';
  });
  const search = document.getElementById('petSearch');
  const typeSel = document.getElementById('petTypeFilter');
  function applyFilter(){
    const q = (search.value||'').trim().toLowerCase();
    const t = typeSel.value;
    document.querySelectorAll('.js-pet-card').forEach(card=>{
      const okName = card.dataset.name.includes(q);
      const okType = !t || card.dataset.type === t;
      card.style.display = (okName && okType) ? '' : 'none';
    });
  }
  search.addEventListener('input', applyFilter);
  typeSel.addEventListener('change', applyFilter);

  document.querySelectorAll('.js-slot').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const slot = btn.dataset.slot;
      if(state.selectedSlots.has(slot)){
        state.selectedSlots.delete(slot);
        btn.classList.remove('btn-secondary','btn-slot-active');
        btn.classList.add('btn-outline-secondary');
      } else {
        state.selectedSlots.add(slot);
        btn.classList.remove('btn-outline-secondary');
        btn.classList.add('btn-secondary','btn-slot-active');
      }
    });
  });

  document.querySelectorAll('.js-pet-card').forEach(card=>{
    card.addEventListener('click', ()=>{
      const id = parseInt(card.dataset.petId, 10);
      if(state.selectedPetId === id){
        state.selectedPetId = null; state.selectedService = null; card.classList.remove('border-primary'); card.style.background='';
        return;
      }
      document.querySelectorAll('.js-pet-card').forEach(c=>{ c.classList.remove('border-primary'); c.style.background=''; });
      state.selectedPetId = id;
      const pet = state.pets.find(p=>p.id===id);
      // pick first available service by default
      state.selectedService = (pet.services && pet.services.length) ? pet.services[0] : null;
      card.classList.add('border-primary'); card.style.background = '#e7f1ff';
    });
  });
}

document.getElementById('prevMonth').addEventListener('click', async ()=>{ state.month = new Date(state.month.getFullYear(), state.month.getMonth()-1, 1); renderHeader(); await loadEntries(); renderCalendar(); });
document.getElementById('nextMonth').addEventListener('click', async ()=>{ state.month = new Date(state.month.getFullYear(), state.month.getMonth()+1, 1); renderHeader(); await loadEntries(); renderCalendar(); });
document.getElementById('monthPicker').addEventListener('change', async (e)=>{ const [y,m]=e.target.value.split('-'); state.month = new Date(parseInt(y), parseInt(m)-1, 1); renderHeader(); await loadEntries(); renderCalendar(); });

(async function init(){
  renderHeader();
  await loadEntries();
  renderCalendar();
  bindPetsPanel();
})();
</script>
@endsection
