@extends('layouts.app')

@section('content')
<div class="public-calendar-page">
    <div class="container py-5">
        <div class="calendar-hero mb-4">
            <span class="calendar-eyebrow">Календарь занятости</span>
            <div class="calendar-hero-row">
                <div>
                    <h1 class="calendar-title">Занятые даты на {{ $year }} год</h1>
                    <p class="calendar-subtitle mb-0">
                        Показаны только активные записи текущего года. Кликните по занятому дню, чтобы увидеть животных и тип услуги.
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
        </div>

        <div class="calendar-layout">
            <section class="calendar-panel">
                <div id="publicCalendarGrid" class="calendar-grid"></div>
            </section>

            <aside class="calendar-panel calendar-panel--details">
                <div class="details-head">
                    <span class="details-label">Выбранная дата</span>
                    <h2 id="detailsDate" class="details-date">Выберите занятый день</h2>
                    <p id="detailsMeta" class="details-meta mb-0">В правой панели появится список животных и тип занятости.</p>
                </div>

                <div id="detailsEmpty" class="details-empty">
                    На эту дату записи не выбраны.
                </div>

                <div id="detailsList" class="details-list" hidden></div>
            </aside>
        </div>
    </div>
</div>

<style>
    .public-calendar-page {
        min-height: 100vh;
        background:
            radial-gradient(circle at top right, rgba(255, 214, 177, 0.45), transparent 28%),
            linear-gradient(180deg, #f9f3ea 0%, #f4efe8 100%);
    }

    .calendar-hero {
        padding: 28px;
        border: 1px solid rgba(63, 38, 20, 0.08);
        border-radius: 28px;
        background: rgba(255, 255, 255, 0.88);
        box-shadow: 0 22px 60px rgba(64, 41, 20, 0.08);
        backdrop-filter: blur(16px);
    }

    .calendar-eyebrow {
        display: inline-flex;
        align-items: center;
        padding: 8px 12px;
        margin-bottom: 18px;
        border-radius: 999px;
        background: #fff1e2;
        color: #8b4a17;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .calendar-hero-row {
        display: flex;
        justify-content: space-between;
        gap: 24px;
        align-items: end;
    }

    .calendar-title {
        margin: 0 0 10px;
        font-size: clamp(32px, 4vw, 48px);
        line-height: 1.02;
        font-weight: 800;
        color: #24150b;
    }

    .calendar-subtitle {
        max-width: 760px;
        color: #6e5b4f;
        font-size: 16px;
        line-height: 1.6;
    }

    .calendar-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: flex-end;
    }

    .legend-item {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        border-radius: 999px;
        background: #fff;
        color: #4f3d31;
        font-size: 13px;
        font-weight: 600;
        box-shadow: inset 0 0 0 1px rgba(79, 61, 49, 0.08);
    }

    .legend-dot {
        width: 12px;
        height: 12px;
        border-radius: 999px;
    }

    .legend-dot--busy {
        background: #70d49c;
    }

    .legend-dot--conflict {
        background: #ffbf69;
    }

    .calendar-layout {
        display: grid;
        grid-template-columns: minmax(0, 2.1fr) minmax(320px, 0.9fr);
        gap: 22px;
        align-items: start;
    }

    .calendar-panel {
        padding: 22px;
        border: 1px solid rgba(63, 38, 20, 0.08);
        border-radius: 28px;
        background: rgba(255, 255, 255, 0.9);
        box-shadow: 0 22px 60px rgba(64, 41, 20, 0.06);
        backdrop-filter: blur(16px);
    }

    .calendar-panel--details {
        position: sticky;
        top: 24px;
    }

    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
    }

    .cal-month {
        padding: 16px;
        border-radius: 22px;
        background: linear-gradient(180deg, #fffdf9 0%, #f7f1eb 100%);
        border: 1px solid rgba(99, 68, 42, 0.08);
    }

    .cal-month-title {
        margin: 0 0 12px;
        color: #2f1f13;
        font-size: 18px;
        font-weight: 700;
    }

    .cal-header,
    .cal-body {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 6px;
    }

    .cal-weekday {
        text-align: center;
        color: #8e786b;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .cal-spacer {
        min-height: 40px;
    }

    .cal-cell {
        position: relative;
        min-height: 44px;
        border: 0;
        border-radius: 14px;
        background: transparent;
        color: #7b675b;
        font-size: 14px;
        font-weight: 600;
        transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
    }

    .cal-cell--busy {
        cursor: pointer;
        background: #eafaf1;
        color: #21583c;
        box-shadow: inset 0 0 0 1px rgba(61, 161, 101, 0.28);
    }

    .cal-cell--busy:hover {
        transform: translateY(-1px);
        box-shadow: inset 0 0 0 1px rgba(61, 161, 101, 0.3), 0 12px 24px rgba(61, 161, 101, 0.12);
    }

    .cal-cell--conflict {
        background: #fff3e3;
        color: #8d4d07;
        box-shadow: inset 0 0 0 1px rgba(242, 156, 42, 0.28);
    }

    .cal-cell--selected {
        background: linear-gradient(135deg, #29211a 0%, #473528 100%);
        color: #fff;
        box-shadow: 0 14px 30px rgba(41, 33, 26, 0.2);
    }

    .cal-cell__count {
        position: absolute;
        top: 6px;
        right: 6px;
        min-width: 18px;
        height: 18px;
        padding: 0 5px;
        border-radius: 999px;
        background: rgba(36, 21, 11, 0.1);
        font-size: 11px;
        line-height: 18px;
    }

    .cal-cell--selected .cal-cell__count {
        background: rgba(255, 255, 255, 0.18);
    }

    .details-head {
        padding-bottom: 18px;
        margin-bottom: 18px;
        border-bottom: 1px solid rgba(79, 61, 49, 0.1);
    }

    .details-label {
        display: inline-block;
        margin-bottom: 8px;
        color: #a06f3d;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .details-date {
        margin: 0 0 8px;
        color: #24150b;
        font-size: 28px;
        line-height: 1.1;
        font-weight: 800;
    }

    .details-meta {
        color: #6e5b4f;
        font-size: 15px;
        line-height: 1.6;
    }

    .details-empty {
        color: #7f6a5d;
        font-size: 15px;
        line-height: 1.6;
    }

    .details-list {
        display: grid;
        gap: 12px;
    }

    .details-item {
        padding: 16px;
        border-radius: 20px;
        background: #fffaf5;
        border: 1px solid rgba(79, 61, 49, 0.08);
    }

    .details-item-head {
        display: flex;
        justify-content: space-between;
        align-items: start;
        gap: 12px;
        margin-bottom: 10px;
    }

    .details-animal {
        margin: 0;
        color: #24150b;
        font-size: 18px;
        font-weight: 700;
    }

    .details-type {
        display: inline-flex;
        align-items: center;
        padding: 7px 12px;
        border-radius: 999px;
        background: #eef7ff;
        color: #295e8a;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        white-space: nowrap;
    }

    .details-period,
    .details-description {
        margin: 0;
        color: #6e5b4f;
        font-size: 14px;
        line-height: 1.6;
    }

    .details-description {
        margin-top: 6px;
    }

    @media (max-width: 1199px) {
        .calendar-layout {
            grid-template-columns: 1fr;
        }

        .calendar-panel--details {
            position: static;
        }

        .calendar-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767px) {
        .public-calendar-page .container {
            padding-top: 24px !important;
            padding-bottom: 32px !important;
        }

        .calendar-hero,
        .calendar-panel {
            padding: 18px;
            border-radius: 22px;
        }

        .calendar-hero-row {
            flex-direction: column;
            align-items: start;
        }

        .calendar-grid {
            grid-template-columns: 1fr;
        }

        .cal-cell,
        .cal-spacer {
            min-height: 38px;
        }

        .details-item-head {
            flex-direction: column;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const entries = @json($entries);
    const year = {{ $year }};
    const months = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];
    const weekdays = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];

    const grid = document.getElementById('publicCalendarGrid');
    const detailsDate = document.getElementById('detailsDate');
    const detailsMeta = document.getElementById('detailsMeta');
    const detailsEmpty = document.getElementById('detailsEmpty');
    const detailsList = document.getElementById('detailsList');

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

    function setSelectedCell(dateValue) {
        grid.querySelectorAll('.cal-cell--selected').forEach((cell) => {
            cell.classList.remove('cal-cell--selected');
        });

        const target = grid.querySelector(`[data-date="${dateValue}"]`);

        if (target) {
            target.classList.add('cal-cell--selected');
        }
    }

    function renderDetails(dateValue, items) {
        if (!items || !items.length) {
            detailsDate.textContent = 'Выберите занятый день';
            detailsMeta.textContent = 'В правой панели появится список животных и тип занятости.';
            detailsEmpty.hidden = false;
            detailsList.hidden = true;
            detailsList.innerHTML = '';
            setSelectedCell('');
            return;
        }

        detailsDate.textContent = formatHumanDate(dateValue);
        detailsMeta.textContent = `Записей на дату: ${items.length}`;
        detailsEmpty.hidden = true;
        detailsList.hidden = false;
        detailsList.innerHTML = items.map((item) => {
            const description = item.description ? `<p class="details-description">${escapeHtml(item.description)}</p>` : '';

            return `
                <article class="details-item">
                    <div class="details-item-head">
                        <h3 class="details-animal">${escapeHtml(item.name)}</h3>
                        <span class="details-type">${escapeHtml(item.service_type)}</span>
                    </div>
                    <p class="details-period">${escapeHtml(item.start_date)} — ${escapeHtml(item.end_date)}</p>
                    ${description}
                </article>
            `;
        }).join('');

        setSelectedCell(dateValue);
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function render() {
        const map = buildMap();
        const busyDates = Object.keys(map).sort();
        grid.innerHTML = '';

        for (let month = 0; month < 12; month += 1) {
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

                if (items.length > 1) {
                    const counter = document.createElement('span');
                    counter.className = 'cal-cell__count';
                    counter.textContent = items.length;
                    button.appendChild(counter);
                }

                button.addEventListener('click', () => {
                    renderDetails(dateValue, items);
                });

                body.appendChild(button);
            }

            monthWrap.appendChild(body);
            grid.appendChild(monthWrap);
        }

        if (busyDates.length) {
            renderDetails(busyDates[0], map[busyDates[0]]);
        } else {
            renderDetails('', []);
            detailsDate.textContent = `${year} год`;
            detailsMeta.textContent = 'На текущий год активных записей нет.';
            detailsEmpty.hidden = false;
            detailsEmpty.textContent = 'Свободные даты появятся здесь, когда в системе будут активные записи.';
        }
    }

    render();
});
</script>
@endsection
