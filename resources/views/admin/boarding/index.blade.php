@extends('admin.index')

@section('content')
<div class="container-fluid boarding-calendar-page">
    <header class="calendar-page__head">
        <h1>Календарь</h1>
    </header>
    <div id="calendarGrid" class="calendar-grid"></div>
</div>

@push('styles')<style>
.boarding-calendar-page{max-width:1180px;min-width:0}.calendar-page__head{margin-bottom:22px}.calendar-page__head h1{margin:0 0 4px;font-size:2rem}.calendar-page__head p{margin:0;color:#697586}.calendar-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}.cal-month{min-width:0;padding:14px;border:1px solid #e1e7ee;border-radius:14px;background:#fff;box-shadow:0 4px 14px rgba(27,39,57,.04)}.cal-month h2{margin:0 0 12px;font-size:1rem;color:#314155}.cal-header,.cal-row{display:grid;grid-template-columns:repeat(7,1fr);text-align:center;font-size:.78rem}.cal-header{color:#8793a2;margin-bottom:4px}.cal-cell{padding:7px 0;border-radius:6px;position:relative}.cal-cell.day{cursor:pointer;touch-action:manipulation}.cal-cell.day:hover{background:#f3f6f9}.cal-cell.day.busy{background:#e9f8ef;border:1px solid #6cc17b}.cal-cell.day.conflict{background:#fff3e0;border:1px solid #f0a500}.tooltip-box{position:absolute;z-index:20;min-width:200px;max-width:300px;padding:10px;border:1px solid #ddd;border-radius:8px;background:#fff;box-shadow:0 10px 30px rgba(0,0,0,.15);font-size:.78rem;white-space:pre-line}@media(max-width:900px){.calendar-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:560px){.calendar-grid{grid-template-columns:1fr}.tooltip-box{position:fixed;right:12px;bottom:12px;left:12px;min-width:0;max-width:none;font-size:.9rem}}
</style>@endpush

@push('scripts')<script>
document.addEventListener('DOMContentLoaded',()=>{
    const entries=@json($entries),grid=document.getElementById('calendarGrid');
    const months=['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'];
    const iso=date=>[date.getUTCFullYear(),String(date.getUTCMonth()+1).padStart(2,'0'),String(date.getUTCDate()).padStart(2,'0')].join('-');
    const utc=(year,month,day=1)=>new Date(Date.UTC(year,month,day));
    const parse=value=>{const [year,month,day]=String(value).split('-').map(Number);return year&&month&&day?utc(year,month-1,day):null};
    const dates=(start,end)=>{const result=[];let cursor=parse(start),last=parse(end);while(cursor&&last&&cursor<=last){result.push(iso(cursor));cursor.setUTCDate(cursor.getUTCDate()+1)}return result};
    const byDate={};entries.forEach(entry=>dates(entry.start_date,entry.end_date).forEach(date=>(byDate[date]??=[]).push(entry)));
    let tip;const touch=matchMedia('(hover:none), (pointer:coarse)').matches;const hideTip=()=>{tip?.remove();tip=null};
    const showTip=cell=>{hideTip();tip=document.createElement('div');tip.className='tooltip-box';tip.textContent=cell.dataset.tooltip;document.body.append(tip);if(touch)return;const box=cell.getBoundingClientRect();tip.style.left=`${Math.max(12,box.left+scrollX)}px`;tip.style.top=`${box.bottom+scrollY+6}px`;if(tip.getBoundingClientRect().right>innerWidth-12)tip.style.left=`${Math.max(12,innerWidth-tip.offsetWidth-12)+scrollX}px`};
    const now=new Date();
    for(let offset=0;offset<3;offset++){
        const date=new Date(Date.UTC(now.getFullYear(),now.getMonth()+offset,1));
        const year=date.getUTCFullYear(),month=date.getUTCMonth(),first=utc(year,month),last=utc(year,month+1,0);
        const card=document.createElement('section');card.className='cal-month';card.innerHTML=`<h2>${months[month]} ${year}</h2>`;
        const header=document.createElement('div');header.className='cal-header';['Пн','Вт','Ср','Чт','Пт','Сб','Вс'].forEach(day=>{const cell=document.createElement('div');cell.textContent=day;header.append(cell)});card.append(header);
        const body=document.createElement('div');body.className='cal-row';for(let i=0;i<(first.getUTCDay()+6)%7;i++)body.append(document.createElement('div'));
        for(let day=1;day<=last.getUTCDate();day++){const cell=document.createElement('div');cell.className='cal-cell day';cell.textContent=day;const list=byDate[iso(utc(year,month,day))]??[];if(list.length){cell.classList.add(list.length>1?'conflict':'busy');cell.dataset.tooltip=list.map(item=>`${item.name}${item.client_name?' · '+item.client_name:''} • ${item.service_type} (${item.start_date} — ${item.end_date})`).join('\n');cell.addEventListener('mouseenter',()=>{if(!touch)showTip(cell)});cell.addEventListener('mouseleave',hideTip);cell.addEventListener('click',event=>{if(touch){event.stopPropagation();showTip(cell)}})}body.append(cell)}
        card.append(body);grid.append(card);
    }
    document.addEventListener('click',hideTip);
});
</script>@endpush
@endsection
