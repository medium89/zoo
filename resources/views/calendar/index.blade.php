@extends('layouts.app')

@section('content')
<div class="public-calendar-page">
    <div class="calendar-shell">
        <header class="calendar-hero">
            <span class="calendar-eyebrow">Календарь занятости</span>
            <div class="calendar-hero-row">
                <div class="calendar-hero-copy">
                    <h1 class="calendar-title">Занятые даты на {{ $year }} год</h1>
                    <p class="calendar-subtitle mb-0">
                        Показаны только текущий месяц и будущие месяцы этого года. Все записи вынесены в левую колонку, а по клику на занятую дату откроется информация по этому дню.
                    </p>
                </div>
                <div class="calendar-legend" aria-label="Легенда календаря">
                    <span class="legend-item">
                        <span class="legend-dot legend-dot--busy"></span>
                        занято
                    </span>
                    <span class="legend-item">
                        <span class="legend-dot legend-dot--conflict"></span>
                        несколько записей
                    </span>
                </div>
            </div>
        </header>

        <div class="calendar-layout">
            <aside class="calendar-sidebar" aria-label="Список активных записей">
                <div class="calendar-sidebar__inner">
                    <div class="calendar-sidebar__head">
                        <span class="calendar-sidebar__eyebrow">Активные записи</span>
                        <h2 class="calendar-sidebar__title">Записи с текущего месяца</h2>
                        <p class="calendar-sidebar__meta mb-0">{{ $entries->count() }} активных записей до конца {{ $year }} года</p>
                    </div>

                    <div class="calendar-sidebar__list">
                        @forelse($entries as $entry)
                            <article class="sidebar-entry">
                                <div class="sidebar-entry__head">
                                    <h3 class="sidebar-entry__name">{{ $entry['name'] }}</h3>
                                    <span class="sidebar-entry__type">{{ $entry['service_type'] }}</span>
                                </div>
                                <p class="sidebar-entry__dates mb-0">{{ $entry['start_date'] }} — {{ $entry['end_date'] }}</p>
                                @if(!empty($entry['description']))
                                    <p class="sidebar-entry__description mb-0">{{ $entry['description'] }}</p>
                                @endif
                            </article>
                        @empty
                            <div class="calendar-sidebar__empty">
                                В текущем году активных записей пока нет.
                            </div>
                        @endforelse
                    </div>
                </div>
            </aside>

            <main class="calendar-content">
                <section class="calendar-panel">
                    <div id="publicCalendarGrid" class="calendar-grid"></div>
                </section>
            </main>
        </div>
    </div>

    <div id="calendarTooltip" class="calendar-tooltip" hidden></div>
</div>

<style>
    .public-calendar-page {
        min-height: 100vh;
        background:
            radial-gradient(circle at top right, rgba(255, 214, 177, 0.42), transparent 28%),
            radial-gradient(circle at bottom left, rgba(255, 240, 216, 0.8), transparent 30%),
            linear-gradient(180deg, #faf4ec 0%, #f6efe7 100%);
    }

    .calendar-shell {
        width: min(1600px, calc(100vw - 32px));
        margin: 0 auto;
        padding: 30px 0 40px;
    }

    .calendar-hero,
    .calendar-sidebar__inner,
    .calendar-panel,
    .calendar-tooltip {
        border: 1px solid rgba(70, 44, 23, 0.08);
        background: rgba(255, 255, 255, 0.88);
        box-shadow: 0 20px 60px rgba(92, 63, 40, 0.08);
        backdrop-filter: blur(14px);
    }

    .calendar-hero {
        padding: 22px 26px;
        margin-bottom: 22px;
        border-radius: 28px;
    }

    .calendar-eyebrow,
    .calendar-sidebar__eyebrow {
        display: inline-flex;
        align-items: center;
        padding: 7px 12px;
        border-radius: 999px;
        background: #fff0de;
        color: #8d5122;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .calendar-hero-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 20px;
        align-items: end;
    }

    .calendar-hero-copy {
        min-width: 0;
    }

    .calendar-title {
        margin: 16px 0 10px;
        color: #28170b;
        font-size: clamp(18px, 2.2vw, 24px);
        line-height: 1.12;
        font-weight: 800;
    }

    .calendar-subtitle {
        max-width: 760px;
        color: #715d50;
        font-size: 14px;
        line-height: 1.6;
    }

    .calendar-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: flex-end;
        align-items: center;
    }

    .legend-item {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 14px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.9);
        color: #554236;
        font-size: 13px;
        font-weight: 600;
        box-shadow: inset 0 0 0 1px rgba(85, 66, 54, 0.08);
    }

    .legend-dot {
        width: 12px;
        height: 12px;
        border-radius: 999px;
    }

    .legend-dot--busy {
        background: #6fd99c;
    }

    .legend-dot--conflict {
        background: #ffbb5d;
    }

    .calendar-layout {
        display: grid;
        grid-template-columns: minmax(280px, 340px) minmax(0, 1fr);
        gap: 20px;
        align-items: start;
    }

    .calendar-sidebar,
    .calendar-content {
        min-width: 0;
    }

    .calendar-sidebar__inner {
        border-radius: 24px;
        padding: 18px;
    }

    .calendar-sidebar__head {
        padding-bottom: 16px;
        margin-bottom: 16px;
        border-bottom: 1px solid rgba(85, 66, 54, 0.1);
    }

    .calendar-sidebar__title {
        margin: 14px 0 8px;
        color: #28170b;
        font-size: 20px;
        font-weight: 800;
        line-height: 1.15;
    }

    .calendar-sidebar__meta {
        color: #776356;
        font-size: 14px;
        line-height: 1.6;
    }

    .calendar-sidebar__list {
        display: grid;
        gap: 12px;
    }

    .sidebar-entry {
        padding: 14px;
        border-radius: 18px;
        background: linear-gradient(180deg, #fffdfa 0%, #f9f2e9 100%);
        border: 1px solid rgba(85, 66, 54, 0.08);
    }

    .sidebar-entry__head {
        display: flex;
        justify-content: space-between;
        align-items: start;
        gap: 10px;
        margin-bottom: 8px;
    }

    .sidebar-entry__name {
        margin: 0;
        color: #28170b;
        font-size: 16px;
        font-weight: 700;
        line-height: 1.25;
    }

    .sidebar-entry__type,
    .calendar-tooltip__type {
        display: inline-flex;
        align-items: center;
        padding: 6px 10px;
        border-radius: 999px;
        background: #eef6ff;
        color: #2d638f;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        white-space: nowrap;
    }

    .sidebar-entry__dates,
    .sidebar-entry__description {
        color: #715d50;
        font-size: 13px;
        line-height: 1.55;
    }

    .sidebar-entry__description {
        margin-top: 6px;
    }

    .calendar-sidebar__empty {
        padding: 18px;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.6);
        color: #7a6659;
        font-size: 14px;
        line-height: 1.6;
        border: 1px dashed rgba(122, 102, 89, 0.22);
    }

    .calendar-panel {
        border-radius: 24px;
        padding: 26px;
    }

    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
        align-items: stretch;
    }

    .cal-month {
        width: 100%;
        min-width: 0;
        height: 100%;
        padding: 14px;
        border-radius: 20px;
        background: linear-gradient(180deg, #fffdf9 0%, #f6efe7 100%);
        border: 1px solid rgba(96, 68, 45, 0.08);
    }

    .cal-month-title {
        margin: 0 0 12px;
        color: #322015;
        font-size: 17px;
        font-weight: 700;
    }

    .cal-header,
    .cal-body {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 4px;
    }

    .cal-weekday {
        text-align: center;
        color: #907a6c;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .cal-spacer {
        min-height: 38px;
    }

    .cal-cell {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 38px;
        padding: 0;
        border: 0;
        border-radius: 12px;
        background: transparent;
        color: #7b6659;
        font-size: 13px;
        font-weight: 600;
        transition: transform 0.16s ease, box-shadow 0.16s ease, background 0.16s ease;
    }

    button.cal-cell {
        cursor: pointer;
    }

    .cal-cell--busy {
        background: #e9faf1;
        color: #21583c;
        box-shadow: inset 0 0 0 1px rgba(58, 151, 95, 0.24);
    }

    .cal-cell--busy:hover {
        transform: translateY(-1px);
        box-shadow: inset 0 0 0 1px rgba(58, 151, 95, 0.28), 0 10px 22px rgba(58, 151, 95, 0.12);
    }

    .cal-cell--conflict {
        background: #fff3e0;
        color: #90520d;
        box-shadow: inset 0 0 0 1px rgba(255, 171, 64, 0.26);
    }

    .cal-cell--selected {
        background: linear-gradient(135deg, #34231a 0%, #4f3a2b 100%);
        color: #fff;
        box-shadow: 0 12px 28px rgba(52, 35, 26, 0.2);
    }

    .cal-cell__count {
        position: absolute;
        top: 4px;
        right: 4px;
        min-width: 16px;
        height: 16px;
        padding: 0 4px;
        border-radius: 999px;
        background: rgba(40, 23, 11, 0.12);
        font-size: 10px;
        line-height: 16px;
    }

    .cal-cell--selected .cal-cell__count {
        background: rgba(255, 255, 255, 0.18);
    }

    .calendar-tooltip {
        position: fixed;
        z-index: 1000;
        width: min(320px, calc(100vw - 24px));
        padding: 16px;
        border-radius: 20px;
    }

    .calendar-tooltip[hidden] {
        display: none !important;
    }

    .calendar-tooltip__eyebrow {
        display: inline-flex;
        margin-bottom: 10px;
        color: #966130;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .calendar-tooltip__title {
        margin: 0 0 8px;
        color: #28170b;
        font-size: 20px;
        font-weight: 800;
        line-height: 1.15;
    }

    .calendar-tooltip__meta {
        margin: 0 0 14px;
        color: #715d50;
        font-size: 13px;
        line-height: 1.55;
    }

    .calendar-tooltip__list {
        display: grid;
        gap: 10px;
    }

    .calendar-tooltip__entry {
        padding: 12px;
        border-radius: 16px;
        background: linear-gradient(180deg, #fffdfa 0%, #f8f1e9 100%);
        border: 1px solid rgba(85, 66, 54, 0.08);
    }

    .calendar-tooltip__entry-head {
        display: flex;
        justify-content: space-between;
        align-items: start;
        gap: 10px;
        margin-bottom: 8px;
    }

    .calendar-tooltip__name {
        margin: 0;
        color: #28170b;
        font-size: 15px;
        font-weight: 700;
        line-height: 1.25;
    }

    .calendar-tooltip__dates,
    .calendar-tooltip__description {
        margin: 0;
        color: #715d50;
        font-size: 12px;
        line-height: 1.55;
    }

    .calendar-tooltip__description {
        margin-top: 6px;
    }

    @media (min-width: 1200px) {
        .calendar-sidebar__inner {
            position: sticky;
            top: 24px;
            max-height: calc(100vh - 48px);
            overflow: auto;
        }
    }

    @media (max-width: 1199px) {
        .calendar-layout {
            grid-template-columns: minmax(260px, 300px) minmax(0, 1fr);
        }

        .calendar-sidebar__inner {
            position: static;
            max-height: none;
        }

        .calendar-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .calendar-panel {
            padding: 22px;
        }
    }

    @media (max-width: 767px) {
        .calendar-shell {
            width: calc(100vw - 20px);
            padding: 18px 0 28px;
        }

        .calendar-layout {
            grid-template-columns: 1fr;
        }

        .calendar-hero,
        .calendar-sidebar__inner,
        .calendar-panel {
            border-radius: 22px;
            padding: 16px;
        }

        .calendar-hero-row {
            grid-template-columns: 1fr;
            align-items: start;
        }

        .calendar-grid {
            grid-template-columns: 1fr;
        }

        .sidebar-entry__head,
        .calendar-tooltip__entry-head {
            flex-direction: column;
        }

        .calendar-tooltip {
            width: min(320px, calc(100vw - 24px));
            padding: 14px;
            border-radius: 18px;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const entries = @json($entries->values());
    const year = {{ $year }};
    const firstVisibleMonth = {{ $currentMonth }};
    const months = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];
    const weekdays = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];

    const grid = document.getElementById('publicCalendarGrid');
    const tooltip = document.getElementById('calendarTooltip');

    let activeDate = null;
    let activeButton = null;

    function parseIsoDate(value) {
        if (!value) {
            return null;
        }

        const [yearPart, monthPart, dayPart] = value.split('-').map(Number);

        if (!yearPart || !monthPart || !dayPart) {
            return null;
        }

        return new Date(Date.UTC(yearPart, monthPart - 1, dayPart));
    }

    function buildUtcDate(yearPart, monthPart, dayPart = 1) {
        return new Date(Date.UTC(yearPart, monthPart, dayPart));
    }

    function formatIsoDate(date) {
        return [
            date.getUTCFullYear(),
            String(date.getUTCMonth() + 1).padStart(2, '0'),
            String(date.getUTCDate()).padStart(2, '0'),
        ].join('-');
    }

    function formatHumanDate(value) {
        const date = parseIsoDate(value);

        if (!date) {
            return value;
        }

        return new Intl.DateTimeFormat('ru-RU', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            timeZone: 'UTC',
        }).format(date);
    }

    function rangeDays(start, end) {
        const days = [];
        let current = parseIsoDate(start);
        const last = parseIsoDate(end);

        if (!current || !last) {
            return days;
        }

        while (current <= last) {
            days.push(formatIsoDate(current));
            current.setUTCDate(current.getUTCDate() + 1);
        }

        return days;
    }

    function buildMap() {
        const map = {};

        entries.forEach((entry) => {
            rangeDays(entry.start_date, entry.end_date).forEach((dateValue) => {
                if (!map[dateValue]) {
                    map[dateValue] = [];
                }

                map[dateValue].push(entry);
            });
        });

        return map;
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function clearSelectedCells() {
        grid.querySelectorAll('.cal-cell--selected').forEach((cell) => {
            cell.classList.remove('cal-cell--selected');
            cell.setAttribute('aria-expanded', 'false');
        });
    }

    function setSelectedCell(button) {
        clearSelectedCells();

        if (!button) {
            return;
        }

        button.classList.add('cal-cell--selected');
        button.setAttribute('aria-expanded', 'true');
    }

    function closeTooltip() {
        activeDate = null;
        activeButton = null;
        tooltip.hidden = true;
        tooltip.innerHTML = '';
        clearSelectedCells();
    }

    function positionTooltip(button) {
        const rect = button.getBoundingClientRect();
        const margin = 12;

        tooltip.style.left = `${margin}px`;
        tooltip.style.top = `${margin}px`;

        const tooltipRect = tooltip.getBoundingClientRect();

        let left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);
        left = Math.max(margin, Math.min(left, window.innerWidth - tooltipRect.width - margin));

        let top = rect.bottom + 10;
        if (top + tooltipRect.height > window.innerHeight - margin) {
            top = rect.top - tooltipRect.height - 10;
        }

        top = Math.max(margin, Math.min(top, window.innerHeight - tooltipRect.height - margin));

        tooltip.style.left = `${left}px`;
        tooltip.style.top = `${top}px`;
    }

    function buildTooltipContent(dateValue, items) {
        const countText = `Записей на дату: ${items.length}`;
        const entriesHtml = items.map((item) => {
            const description = item.description ? `<p class="calendar-tooltip__description">${escapeHtml(item.description)}</p>` : '';

            return `
                <article class="calendar-tooltip__entry">
                    <div class="calendar-tooltip__entry-head">
                        <h3 class="calendar-tooltip__name">${escapeHtml(item.name)}</h3>
                        <span class="calendar-tooltip__type">${escapeHtml(item.service_type)}</span>
                    </div>
                    <p class="calendar-tooltip__dates">${escapeHtml(item.start_date)} — ${escapeHtml(item.end_date)}</p>
                    ${description}
                </article>
            `;
        }).join('');

        return `
            <span class="calendar-tooltip__eyebrow">Занятость дня</span>
            <h2 class="calendar-tooltip__title">${escapeHtml(formatHumanDate(dateValue))}</h2>
            <p class="calendar-tooltip__meta">${escapeHtml(countText)}</p>
            <div class="calendar-tooltip__list">${entriesHtml}</div>
        `;
    }

    function openTooltip(button, dateValue, items) {
        activeDate = dateValue;
        activeButton = button;
        tooltip.innerHTML = buildTooltipContent(dateValue, items);
        tooltip.hidden = false;
        setSelectedCell(button);
        positionTooltip(button);
    }

    function toggleTooltip(button, dateValue, items) {
        if (activeDate === dateValue && activeButton === button && !tooltip.hidden) {
            closeTooltip();
            return;
        }

        openTooltip(button, dateValue, items);
    }

    function render() {
        const map = buildMap();
        grid.innerHTML = '';

        for (let month = firstVisibleMonth; month < 12; month += 1) {
            const monthWrap = document.createElement('section');
            monthWrap.className = 'cal-month';

            const title = document.createElement('h2');
            title.className = 'cal-month-title';
            title.textContent = `${months[month]} ${year}`;
            monthWrap.appendChild(title);

            const header = document.createElement('div');
            header.className = 'cal-header';

            weekdays.forEach((weekday) => {
                const weekdayCell = document.createElement('div');
                weekdayCell.className = 'cal-weekday';
                weekdayCell.textContent = weekday;
                header.appendChild(weekdayCell);
            });

            monthWrap.appendChild(header);

            const body = document.createElement('div');
            body.className = 'cal-body';

            const first = buildUtcDate(year, month, 1);
            const offset = (first.getUTCDay() + 6) % 7;
            const daysInMonth = buildUtcDate(year, month + 1, 0).getUTCDate();

            for (let spacer = 0; spacer < offset; spacer += 1) {
                const spacerCell = document.createElement('div');
                spacerCell.className = 'cal-spacer';
                body.appendChild(spacerCell);
            }

            for (let day = 1; day <= daysInMonth; day += 1) {
                const dateValue = formatIsoDate(buildUtcDate(year, month, day));
                const items = map[dateValue] || [];

                if (!items.length) {
                    const emptyCell = document.createElement('div');
                    emptyCell.className = 'cal-cell';
                    emptyCell.textContent = day;
                    body.appendChild(emptyCell);
                    continue;
                }

                const button = document.createElement('button');
                button.type = 'button';
                button.className = `cal-cell cal-cell--busy${items.length > 1 ? ' cal-cell--conflict' : ''}`;
                button.dataset.date = dateValue;
                button.textContent = day;
                button.setAttribute('aria-haspopup', 'dialog');
                button.setAttribute('aria-expanded', 'false');

                if (items.length > 1) {
                    const counter = document.createElement('span');
                    counter.className = 'cal-cell__count';
                    counter.textContent = items.length;
                    button.appendChild(counter);
                }

                button.addEventListener('click', (event) => {
                    event.preventDefault();
                    toggleTooltip(button, dateValue, items);
                });

                body.appendChild(button);
            }

            monthWrap.appendChild(body);
            grid.appendChild(monthWrap);
        }
    }

    document.addEventListener('click', (event) => {
        if (tooltip.hidden) {
            return;
        }

        if (tooltip.contains(event.target)) {
            return;
        }

        if (event.target.closest('.cal-cell--busy')) {
            return;
        }

        closeTooltip();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeTooltip();
        }
    });

    window.addEventListener('resize', closeTooltip);
    window.addEventListener('scroll', closeTooltip, true);

    render();
});
</script>
@endsection
