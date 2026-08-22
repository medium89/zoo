<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админпанель</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --sidebar-width: 240px; }

        body {
            min-height: 100vh;
            background: #f7f8fa;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        #sidebarToggle {
            position: fixed;
            top: 14px;
            left: 14px;
            z-index: 1100;
            box-shadow: 0 8px 22px rgba(0,0,0,0.12);
        }

        .admin-layout {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        .sidebar {
            width: var(--sidebar-width);
            flex: 0 0 var(--sidebar-width);
            background: #1f232a;
            color: #fff;
            min-height: 100vh;
            transition: transform 0.3s ease, width 0.3s ease, opacity 0.2s ease, visibility 0.2s ease;
            position: sticky;
            top: 0;
            left: 0;
            overflow-y: auto;
            padding: 16px 0 24px;
            border-right: 1px solid #2d323a;
            box-shadow: 6px 0 16px rgba(0,0,0,0.08);
        }

        body.sidebar-collapsed .sidebar {
            width: 0;
            flex-basis: 0;
            transform: translateX(-104%);
            opacity: 0;
            visibility: hidden;
            box-shadow: none;
        }

        .sidebar h4 a {
            color: #fff;
            text-decoration: none;
        }

        .sidebar-content {
            display: flex;
            flex-direction: column;
            gap: 4px;
            min-height: calc(100vh - 72px);
            padding: 4px 0 0;
        }

        .sidebar-content__item {
            display: flex;
            flex-direction: column;
            width: 100%;
            border-bottom: 1px solid #2d323a;
        }

        .sidebar-content__item:last-child {
            border-bottom: 0;
        }

        .sidebar a {
            color: #e9ecef;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            transition: background 0.2s ease, color 0.2s ease, padding-left 0.2s ease;
        }

        .sidebar .text-muted {
            color: #fff !important;
        }

        .sidebar a.active { background: #2b3038; color: #fff; padding-left: 26px; }
        .sidebar a.active:hover { background: #2b3038; color: #fff; padding-left: 26px; }
        .sidebar a:hover { background: #2b3038; color: #fff; padding-left: 20px; }

        .content {
            padding: 32px;
            flex: 1 1 auto;
            width: 100%;
        }

        .admin-breadcrumbs {
            margin: 0 0 22px;
        }

        .admin-breadcrumbs ol {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 7px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .admin-breadcrumbs li {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: #8190a0;
            font-size: .82rem;
            font-weight: 700;
        }

        .admin-breadcrumbs li + li::before {
            color: #b6c0ca;
            content: '/';
            font-weight: 600;
        }

        .admin-breadcrumbs a {
            color: #64778a;
            text-decoration: none;
            transition: color .15s ease;
        }

        .admin-breadcrumbs a:hover { color: #1f3345; }
        .admin-breadcrumbs li[aria-current="page"] { color: #263846; }

        .admin-to-top {
            position: fixed;
            right: 18px;
            bottom: 18px;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #1f232a;
            color: #fff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 24px rgba(0,0,0,0.22);
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s ease;
            z-index: 1200;
        }

        .admin-to-top.is-visible {
            opacity: 1;
            visibility: visible;
        }

        .admin-to-top:hover {
            color: #fff;
            transform: translateY(-2px);
        }

        .sidebar-backdrop {
            display: none;
        }

        /* Нормальные размеры и выравнивание иконок пагинации */
        .pagination svg {
            width: 16px !important;
            height: 16px !important;
            flex-shrink: 0;
        }

        .pagination .page-link {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 0.35rem 0.65rem;
        }

        .admin-pagination {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px 18px;
        }

        .admin-pagination .pagination {
            margin: 0;
        }

        .admin-editor-modal .modal-content {
            border: 0;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 22px 60px rgba(31, 35, 42, .24);
        }

        .admin-editor-modal .modal-header {
            padding: 18px 22px;
            border-bottom: 1px solid #edf0f2;
        }

        .admin-editor-modal .modal-body {
            padding: 22px;
            background: #f7f8fa;
        }

        .admin-editor-modal .container-fluid {
            padding: 0;
        }

        .admin-editor-modal .card {
            box-shadow: none;
        }

        .admin-editor-loading {
            display: grid;
            min-height: 180px;
            place-items: center;
            color: #718096;
        }

        .entity-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 7px;
        }

        .entity-tag {
            display: inline-flex;
            align-items: center;
            min-height: 24px;
            padding: 3px 8px;
            border: 1px solid transparent;
            border-radius: 999px;
            font-size: .74rem;
            font-weight: 700;
            line-height: 1.15;
        }

        .entity-tag--positive { color: #237348; background: #e4f6eb; border-color: #bfe8ce; }
        .entity-tag--negative { color: #ae3e3e; background: #fff0f0; border-color: #f2c5c5; }

        .tag-editor__controls {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .tag-editor__controls .form-control { min-width: 0; }
        .tag-editor__list { display: flex; flex-wrap: wrap; gap: 7px; margin-top: 10px; }
        .tag-editor__item { position: relative; gap: 0; padding: 0; overflow: visible; }
        .tag-editor__label { min-height: 24px; padding: 3px 8px; border: 0; border-radius: inherit; background: transparent; color: inherit; font: inherit; text-align: left; }
        .tag-editor__label:hover { background: rgba(0,0,0,.06); }
        .tag-editor__item.is-classifying { color: #5c6470; background: #f1f3f5; border-color: #d8dde3; }
        .tag-editor__item.is-classifying .tag-editor__label::after { content: ' · ИИ…'; font-weight: 600; }
        .tag-editor__actions { position: absolute; z-index: 5; top: calc(100% + 5px); left: 0; display: flex; gap: 3px; min-width: max-content; padding: 4px; border: 1px solid #dce1e7; border-radius: 9px; background: #fff; box-shadow: 0 8px 20px rgba(31,41,55,.14); }
        .tag-editor__action { border: 0; border-radius: 6px; padding: 4px 7px; background: #f4f6f8; color: #384252; font-size: .72rem; font-weight: 700; }
        .tag-editor__action:hover { background: #e8edf2; }
        .tag-editor__action--remove { color: #ae3e3e; }

        @media (max-width: 575.98px) {
            .admin-editor-modal .modal-body { padding: 16px; }
            .admin-editor-modal .modal-header { padding: 15px 16px; }
            .tag-editor__controls { align-items: stretch; flex-direction: column; }
        }

        /* Шапки страниц и таблицы */
        .content .d-flex.justify-content-between.align-items-center {
            flex-wrap: wrap;
            gap: 12px;
        }

        .content .table img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        /* Псевдо-табличный формат → плитки */
        .content .admin-flex-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 14px;
        }
        .content .admin-flex-table thead {
            display: none;
        }
        .content .admin-flex-table tbody {
            display: block;
        }
        .content .admin-flex-table tr {
            display: flex;
            flex-direction: row;
            align-items: center;
            flex-wrap: wrap;
            gap: 14px 18px;
            padding: 14px 16px;
            border-radius: 14px;
            background: #fff;
            border: 1px solid #e9ecef;
            box-shadow: 0 10px 24px rgba(31,35,42,0.08);
            margin-bottom: 10px;
        }
        .content .admin-flex-table td {
            display: flex;
            align-items: center;
            gap: 10px;
            border: 0;
            padding: 2px 0;
            flex: 0 1 auto;
        }
        .content .admin-flex-table td::before {
            content: attr(data-label);
            min-width: 140px;
            font-weight: 600;
            color: #6c757d;
            font-size: 0.95rem;
        }
        .content .admin-flex-table td:last-child {
            padding-bottom: 0;
        }
        .content .admin-flex-table td.no-label::before {
            display: none;
        }

        /* История записей: такие же читаемые карточки, как в передержке */
        .content .boarding-history-table.admin-flex-table {
            border-spacing: 0;
        }
        .content .boarding-history-table.admin-flex-table tr {
            display: grid;
            position: relative;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            align-items: start;
            gap: 18px 24px;
            padding: 20px;
            margin-bottom: 14px;
        }
        .content .boarding-history-table.admin-flex-table td {
            display: block;
            min-width: 0;
            padding: 0;
        }
        .content .boarding-history-table.admin-flex-table td::before {
            display: block;
            min-width: 0;
            margin-bottom: 4px;
            color: #7b8794;
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .content .boarding-history-table.admin-flex-table td:first-child {
            position: absolute;
            top: 16px;
            right: 18px;
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            padding: 3px 9px;
            border: 1px solid #d8dee5;
            border-radius: 999px;
            background: #f8fafc;
            font-size: .82rem;
            font-weight: 700;
        }
        .content .boarding-history-table.admin-flex-table td:first-child::before {
            display: none;
        }
        .content .boarding-history-table.admin-flex-table td:nth-child(2) {
            padding-right: 52px;
            font-size: 1.05rem;
            font-weight: 700;
        }
        .content .boarding-history-table.admin-flex-table td:nth-child(2) a {
            font-weight: 700;
        }
        @media (max-width: 575.98px) {
            .content .boarding-history-table.admin-flex-table tr {
                grid-template-columns: 1fr;
                gap: 12px;
                padding: 16px;
            }
        }
        .content .admin-flex-table td .js-order-label,
        .js-order-label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            vertical-align: middle;
            min-width: 24px;
        }
        /* Грид-списки (общие) */
        .admin-grid {
            display: grid;
            gap: 12px;
        }
        .admin-grid-header {
            display: grid;
            grid-template-columns: var(--grid-cols, repeat(auto-fit, minmax(120px,1fr)));
            gap: 12px;
            font-weight: 600;
            color: #6c757d;
            font-size: 0.95rem;
            padding-left: 2px;
        }
        .admin-grid-body {
            display: grid;
            gap: 12px;
        }
        .admin-grid-row {
            display: grid;
            grid-template-columns: var(--grid-cols, repeat(auto-fit, minmax(120px,1fr)));
            gap: 12px;
            padding: 12px;
            border-radius: 14px;
            background: #fff;
            border: 1px solid #e9ecef;
            box-shadow: 0 8px 20px rgba(31,35,42,0.08);
            align-items: center;
        }
        .admin-grid-row .actions {
            display: flex;
            justify-content: flex-end;
        }
        .text-clip {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        @media (max-width: 767.98px) {
            .admin-grid {
                display: flex;
                flex-direction: column;
                gap: 14px;
            }
            .admin-grid-header {
                display: none;
            }
            .admin-grid-body {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }
            .admin-grid-row {
                display: flex;
                flex-direction: column;
                gap: 10px;
                padding: 14px;
            }
            .admin-grid-row > * {
                display: flex;
                flex-direction: row;
                align-items: flex-start;
                gap: 8px;
                padding: 2px 0;
                width: 100%;
                flex-wrap: wrap;
            }
            .admin-grid-row > *::before {
                content: attr(data-label);
                min-width: 120px;
                font-weight: 600;
                color: #6c757d;
                font-size: 0.95rem;
                line-height: 1.3;
                display: inline-block;
                margin: 0;
                padding-top: 3px;
                flex: 0 0 120px;
            }
            .admin-grid-row > .actions {
                justify-content: flex-start;
            }
            .admin-grid-row .actions > .d-flex {
                justify-content: flex-start;
            }
            .admin-grid-row .text-end {
                text-align: left !important;
            }
            .admin-grid-row img {
                max-width: 160px;
                height: auto;
            }
            .admin-grid-row .js-order-label{
                display: inline-flex;
                align-items: center;
                gap: 6px;
                line-height: 1.2;
            }
            .text-clip {
                -webkit-line-clamp: unset;
                white-space: normal;
            }
        }

        /* Грид-списки */
        .admin-grid-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .admin-grid-table thead {
            display: grid;
            grid-template-columns: var(--grid-cols, repeat(auto-fit, minmax(120px, 1fr)));
            gap: 12px;
            padding: 0 0 12px 0;
        }
        .admin-grid-table thead tr {
            display: contents;
        }
        .admin-grid-table thead th {
            font-weight: 600;
            color: #6c757d;
            font-size: 0.95rem;
        }
        .admin-grid-table tbody {
            display: grid;
            gap: 12px;
        }
        .admin-grid-table tbody tr {
            display: grid;
            grid-template-columns: var(--grid-cols, repeat(auto-fit, minmax(120px, 1fr)));
            gap: 12px;
            padding: 12px;
            border-radius: 14px;
            background: #fff;
            border: 1px solid #e9ecef;
            box-shadow: 0 8px 20px rgba(31,35,42,0.08);
            align-items: center;
        }
        .admin-grid-table td {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 0;
            border: none;
        }
        .admin-grid-table td.actions {
            justify-content: flex-end;
        }
        .text-clip {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .content .btn {
            white-space: nowrap;
        }

        @media (max-width: 991.98px) {
            :root { --sidebar-width: 280px; }

            .admin-layout {
                display: block;
            }

            .sidebar {
                position: fixed;
                inset: 0 auto 0 0;
                height: 100vh;
                transform: translateX(-104%);
                opacity: 0;
                visibility: hidden;
                width: var(--sidebar-width);
                flex-basis: var(--sidebar-width);
                box-shadow: 12px 0 26px rgba(0,0,0,0.28);
                z-index: 1040;
            }

            body.sidebar-open .sidebar {
                transform: translateX(0);
                opacity: 1;
                visibility: visible;
            }

            .sidebar-backdrop {
                display: block;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.45);
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.3s ease;
                z-index: 1030;
            }

            body.sidebar-open .sidebar-backdrop {
                opacity: 1;
                visibility: visible;
            }

            .content {
                padding: 72px 16px 32px;
            }
        }

        @media (max-width: 575.98px) {
            .content {
                padding: 72px 14px 28px;
            }

            .content .btn {
                width: 100%;
            }
        }

        @media (min-width: 992px) {
            #sidebarToggle {
                top: 18px;
                left: 18px;
            }
        }

        /* WYSIWYG базовые размеры */
        textarea.wysiwyg,
        textarea.js-wysiwyg,
        textarea.wysiwyg-excerpt { min-height: 220px; resize: vertical; }
        .tox-tinymce { width: 100%; border-radius: 8px; }

        /* Ползунки масштаба/качества */
        .form-range::-webkit-slider-thumb { background: #0d6efd; }
        .form-range::-moz-range-thumb { background: #0d6efd; }
        .form-range::-webkit-slider-runnable-track { background: #dfe3e8; }
        .form-range::-moz-range-track { background: #dfe3e8; }
    </style>
    @vite(['resources/js/app.js'])
    <style>
        /* Общий reset сайта задаёт Inter всем <i>; возвращаем иконкам их шрифт. */
        i.fa, i.fa-classic, i.fa-sharp, i.fas, i.fa-solid, i.far, i.fa-regular {
            font-family: "Font Awesome 6 Free" !important;
            font-style: normal !important;
            font-weight: 900 !important;
        }

        i.far, i.fa-regular {
            font-weight: 400 !important;
        }

        i.fab, i.fa-brands {
            font-family: "Font Awesome 6 Brands" !important;
            font-style: normal !important;
            font-weight: 400 !important;
        }
    </style>
    @stack('styles')
</head>
<body>
<button id="sidebarToggle" class="btn btn-dark" aria-label="Переключить меню"><i class="fa fa-bars"></i></button>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<div class="d-flex admin-layout">
    <nav id="sidebar" class="sidebar d-flex flex-column p-0">
        <h4 class="text-center py-3 border-bottom mb-0">
            <a href="{{ route('admin.dashboard') }}">Админпанель</a></h4>
        <div class="sidebar-content">
            <div class="sidebar-content__item px-3 text-uppercase text-muted small pt-2 pb-2 mt-2">Контент</div>
            <div class="sidebar-content__item">
                <a href="/zooadmin/sliders" class="{{ request()->is('zooadmin/sliders*') ? 'active' : '' }}"><i class="fa fa-photo-film me-2"></i>Слайдер</a>
                <a href="{{ route('admin.about.edit') }}" class="{{ request()->is('zooadmin/about*') ? 'active' : '' }}"><i class="fa fa-user me-2"></i>Обо мне</a>
                <a href="/zooadmin/advantages" class="{{ request()->is('zooadmin/advantages*') ? 'active' : '' }}"><i class="fa fa-star me-2"></i>Преимущества</a>
                <a href="/zooadmin/services" class="{{ request()->is('zooadmin/services*') ? 'active' : '' }}"><i class="fa fa-briefcase me-2"></i>Услуги</a>
                <a href="/zooadmin/galleries" class="{{ request()->is('zooadmin/galleries*') ? 'active' : '' }}"><i class="fa fa-image me-2"></i>Фотоальбом</a>
                <a href="/zooadmin/images" class="{{ request()->is('zooadmin/images*') ? 'active' : '' }}"><i class="fa fa-file-image me-2"></i>Изображения</a>
                <a href="/zooadmin/socials" class="{{ request()->is('zooadmin/socials*') ? 'active' : '' }}"><i class="fa fa-share-alt me-2"></i>Социальные контакты</a>
            </div>
            <div class="sidebar-content__item px-3 text-uppercase text-muted small border-top pt-2 mt-2">Работа</div>
            <div class="sidebar-content__item">
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->is('zooadmin/dashboard*') ? 'active' : '' }}"><i class="fa fa-chart-line me-2"></i>Дашборд</a>
                <a href="{{ route('admin.clients.index') }}" class="{{ request()->is('zooadmin/clients*') ? 'active' : '' }}"><i class="fa fa-address-book me-2"></i>Клиенты</a>
                <a href="{{ route('admin.animals.index') }}" class="{{ request()->is('zooadmin/animals*') ? 'active' : '' }}"><i class="fa fa-paw me-2"></i>Питомцы</a>
                <a href="{{ route('admin.categories.index') }}" class="{{ request()->is('zooadmin/categories*') ? 'active' : '' }}"><i class="fa fa-tags me-2"></i>Категории животных</a>
                <a href="{{ route('admin.feedbacks.index') }}" class="{{ request()->is('zooadmin/feedbacks*') ? 'active' : '' }}"><i class="fa fa-envelope me-2"></i>Обратная связь</a>
                <a href="{{ route('admin.avito-reviews.index') }}" class="{{ request()->is('zooadmin/avito-reviews*') ? 'active' : '' }}"><i class="fa fa-star-half-stroke me-2"></i>Отзывы Avito</a>
                <a href="{{ route('admin.boarding.index') }}" class="{{ request()->is('zooadmin/boarding*') ? 'active' : '' }}"><i class="fa fa-calendar-check me-2"></i>Календарь</a>
                <a href="{{ route('admin.service-orders.index') }}" class="{{ request()->is('zooadmin/service-orders') ? 'active' : '' }}"><i class="fa fa-briefcase me-2"></i>Заказы и работа</a>
                <a href="{{ route('admin.service-orders.archive.index') }}" class="{{ request()->is('zooadmin/service-orders/archive') ? 'active' : '' }}"><i class="fa fa-box-archive me-2"></i>Архив заказов</a>
            </div>
            <div class="sidebar-content__item px-3 text-uppercase text-muted small border-top pt-2 mt-2">Статьи</div>
            <div class="sidebar-content__item">
                <a href="{{ route('admin.article-comments.index') }}" class="{{ request()->is('zooadmin/article-comments*') ? 'active' : '' }}"><i class="fa fa-comments me-2"></i>Комментарии</a>
                <a href="{{ route('admin.articles.index') }}" class="{{ request()->is('zooadmin/articles*') ? 'active' : '' }}"><i class="fa fa-newspaper me-2"></i>Статьи</a>
            </div>
            <div class="sidebar-content__item px-3 text-uppercase text-muted small">Настройки</div>
            <div class="sidebar-content__item">
                <a href="{{ route('admin.settings') }}" class="{{ request()->is('zooadmin/settings') ? 'active' : '' }}"><i class="fa fa-gear me-2"></i>Настройки</a>
                <a href="{{ route('admin.telegram-bot-settings.edit') }}" class="{{ request()->is('zooadmin/settings/telegram-bot*') ? 'active' : '' }}"><i class="fa fa-robot me-2"></i>Telegram-бот</a>
                <a href="{{ route('admin.personal-data-consent.edit') }}" class="{{ request()->is('zooadmin/personal-data-consent*') ? 'active' : '' }}"><i class="fa fa-file-signature me-2"></i>Согласие ПДн</a>
                <a href="{{ route('admin.nav-links.index') }}" class="{{ request()->is('zooadmin/nav-links*') ? 'active' : '' }}"><i class="fa fa-list me-2"></i>Меню сайта</a>
                <a href="{{ route('admin.users.index') }}" class="{{ request()->is('zooadmin/users*') ? 'active' : '' }}"><i class="fa fa-users me-2"></i>Пользователи</a>
            </div>
            <div class="sidebar-content__item border-top mt-auto pt-3">
                <a href="{{ route('logout') }}"
                onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                class="mt-auto border-top">Выйти</a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </div>
        </div>
    </nav>
    <div class="content flex-grow-1">
        @php
            $routeName = request()->route()?->getName() ?? '';
            $currentPath = trim(request()->path(), '/');
            $breadcrumbSections = [
                'dashboard' => ['Сводка', route('admin.dashboard')],
                'settings' => ['Настройки', route('admin.settings')],
                'telegram-bot-settings' => ['Telegram-бот', route('admin.telegram-bot-settings.edit')],
                'personal-data-consent' => ['Согласие ПДн', route('admin.personal-data-consent.edit')],
                'clients' => ['Клиенты', route('admin.clients.index')],
                'animals' => ['Питомцы', route('admin.animals.index')],
                'categories' => ['Категории животных', route('admin.categories.index')],
                'boarding' => ['Передержка', route('admin.boarding.index')],
                'service-orders' => ['Заказы и работа', route('admin.service-orders.index')],
                'feedbacks' => ['Обратная связь', route('admin.feedbacks.index')],
                'avito-reviews' => ['Отзывы Avito', route('admin.avito-reviews.index')],
                'sliders' => ['Слайдер', route('admin.sliders.index')],
                'about' => ['Обо мне', route('admin.about.edit')],
                'advantages' => ['Преимущества', route('admin.advantages.index')],
                'services' => ['Услуги', route('admin.services.index')],
                'galleries' => ['Фотоальбом', route('admin.galleries.index')],
                'images' => ['Изображения', route('admin.images.index')],
                'socials' => ['Социальные контакты', route('admin.socials.index')],
                'articles' => ['Статьи', route('admin.articles.index')],
                'article-comments' => ['Комментарии', route('admin.article-comments.index')],
                'nav-links' => ['Меню сайта', route('admin.nav-links.index')],
                'users' => ['Пользователи', route('admin.users.index')],
            ];
            $sectionKey = collect(array_keys($breadcrumbSections))
                ->first(fn ($key) => str_starts_with($currentPath, 'zooadmin/'.$key));
            $breadcrumbs = [];
            if ($sectionKey && $sectionKey !== 'dashboard') {
                $breadcrumbs[] = ['Сводка', route('admin.dashboard'), false];
                $section = $breadcrumbSections[$sectionKey];
                $isSectionRoot = in_array($routeName, ["admin.{$sectionKey}.index", 'admin.settings', 'admin.about.edit', 'admin.telegram-bot-settings.edit', 'admin.personal-data-consent.edit'], true);
                $breadcrumbs[] = [$section[0], $isSectionRoot ? null : $section[1], $isSectionRoot];
                if (!$isSectionRoot) {
                    $pageLabel = match (true) {
                        $routeName === 'admin.boarding.animals' => 'Питомцы',
                        str_contains($routeName, '.create') => 'Новая запись',
                        str_contains($routeName, '.edit') => 'Редактирование',
                        str_contains($routeName, '.show') => 'Карточка',
                        str_contains($routeName, '.archive') => 'Архив',
                        str_contains($routeName, '.tasks') => 'Действия',
                        default => 'Раздел',
                    };
                    $breadcrumbs[] = [$pageLabel, null, true];
                }
            }
        @endphp
        @if($breadcrumbs)
            <nav class="admin-breadcrumbs" aria-label="Навигационная цепочка">
                <ol>
                    @foreach($breadcrumbs as [$label, $url, $isCurrent])
                        <li @if($isCurrent) aria-current="page" @endif>
                            @if($url)<a href="{{ $url }}">{{ $label }}</a>@else{{ $label }}@endif
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif
        <div id="admin-content">
            @yield('content')
        </div>
    </div>
</div>
<div class="modal fade admin-editor-modal" id="adminEditorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="adminEditorModalTitle">Редактирование</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body" id="adminEditorModalBody">
                <div class="admin-editor-loading">Загрузка формы…</div>
            </div>
        </div>
    </div>
</div>
<a href="#" class="admin-to-top" id="adminToTop" aria-label="Наверх">
    <i class="fas fa-arrow-up"></i>
</a>
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">Удаление</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="confirmDeleteText">Удалить запись?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Удалить</button>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.getElementById('sidebar');
        const toggleButton = document.getElementById('sidebarToggle');
        const backdrop = document.getElementById('sidebarBackdrop');
        const editorModalEl = document.getElementById('adminEditorModal');
        const editorModalBody = document.getElementById('adminEditorModalBody');
        const editorModalTitle = document.getElementById('adminEditorModalTitle');
        const editorModal = editorModalEl ? new bootstrap.Modal(editorModalEl) : null;
        const editorScrollKey = `admin-editor-scroll:${window.location.pathname}${window.location.search}`;
        const tagClassificationUrl = @json(route('admin.tags.classify'));

        const restoreEditorScroll = () => {
            const saved = sessionStorage.getItem(editorScrollKey);
            if (!saved) return;
            sessionStorage.removeItem(editorScrollKey);
            requestAnimationFrame(() => window.scrollTo(0, Number(saved) || 0));
        };

        restoreEditorScroll();

        const setTagType = (item, type, reason = '') => {
            item.classList.remove('entity-tag--positive', 'entity-tag--negative', 'is-classifying');
            item.classList.add(`entity-tag--${type === 'positive' ? 'positive' : 'negative'}`);
            const typeField = item.querySelector('input[name$="[type]"]');
            if (typeField) typeField.value = type === 'positive' ? 'positive' : 'negative';
            item.title = reason ? `ИИ: ${reason}` : 'Нажмите, чтобы изменить тип или удалить';
        };

        const classifyTag = async (item, name) => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                || item.closest('form')?.querySelector('input[name="_token"]')?.value;

            try {
                const response = await fetch(tagClassificationUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                    },
                    body: JSON.stringify({ tag: name }),
                });
                if (!response.ok) throw new Error('Tag classification failed');

                const result = await response.json();
                setTagType(item, result.type, result.reason || 'Тип определён автоматически.');
            } catch (_) {
                setTagType(item, 'negative', 'Не удалось определить автоматически — проверьте тег вручную.');
            }
        };

        const appendTag = (editor, name) => {
            const cleanName = name.trim().replace(/\s+/g, ' ');
            if (!cleanName) return;

            const list = editor.querySelector('[data-tag-list]');
            const input = editor.querySelector('[data-tag-input]');
            const exists = Array.from(list.querySelectorAll('input[name$="[name]"]'))
                .some((field) => field.value.trim().toLocaleLowerCase('ru') === cleanName.toLocaleLowerCase('ru'));
            if (exists) {
                input.value = '';
                return;
            }

            const index = Number(editor.dataset.tagIndex || list.children.length);
            editor.dataset.tagIndex = String(index + 1);

            const item = document.createElement('span');
            item.className = 'tag-editor__item entity-tag entity-tag--negative is-classifying';
            item.dataset.tagItem = '';
            item.title = 'ИИ определяет тип тега…';
            const label = document.createElement('button');
            label.type = 'button';
            label.className = 'tag-editor__label';
            label.dataset.tagToggle = '';
            label.textContent = cleanName;
            const actions = document.createElement('span');
            actions.className = 'tag-editor__actions';
            actions.dataset.tagActions = '';
            actions.hidden = true;
            [['positive', 'Хороший'], ['negative', 'Проблемный']].forEach(([type, text]) => {
                const action = document.createElement('button');
                action.type = 'button';
                action.className = 'tag-editor__action';
                action.dataset.setTagType = type;
                action.textContent = text;
                actions.appendChild(action);
            });
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'tag-editor__action tag-editor__action--remove';
            remove.dataset.removeTag = '';
            remove.textContent = 'Удалить';
            actions.appendChild(remove);

            const nameField = document.createElement('input');
            nameField.type = 'hidden';
            nameField.name = `tags[${index}][name]`;
            nameField.value = cleanName;
            const typeField = document.createElement('input');
            typeField.type = 'hidden';
            typeField.name = `tags[${index}][type]`;
            typeField.value = 'negative';

            item.append(label, actions, nameField, typeField);
            list.appendChild(item);
            input.value = '';
            classifyTag(item, cleanName);
        };

        document.addEventListener('keydown', (event) => {
            const input = event.target.closest('[data-tag-input]');
            if (!input || event.key !== 'Enter' || event.isComposing) return;
            event.preventDefault();
            appendTag(input.closest('[data-tag-editor]'), input.value);
        });

        document.addEventListener('click', (event) => {
            const toggle = event.target.closest('[data-tag-toggle]');
            if (toggle) {
                const item = toggle.closest('[data-tag-item]');
                const actions = item?.querySelector('[data-tag-actions]');
                if (actions) actions.hidden = !actions.hidden;
                return;
            }

            const setTypeButton = event.target.closest('[data-set-tag-type]');
            if (setTypeButton) {
                const item = setTypeButton.closest('[data-tag-item]');
                setTagType(item, setTypeButton.dataset.setTagType, 'Тип выбран вручную.');
                const actions = item?.querySelector('[data-tag-actions]');
                if (actions) actions.hidden = true;
                return;
            }

            const removeButton = event.target.closest('[data-remove-tag]');
            if (removeButton) removeButton.closest('.tag-editor__item')?.remove();

            if (!event.target.closest('[data-tag-item]')) {
                document.querySelectorAll('[data-tag-actions]').forEach((actions) => { actions.hidden = true; });
            }
        });

        const isEditorUrl = (url) => {
            if (url.origin !== window.location.origin || !url.pathname.startsWith('/zooadmin/')) return false;

            const path = url.pathname.replace(/\/$/, '');
            const standaloneEditors = [
                '/zooadmin/settings',
                '/zooadmin/about',
                '/zooadmin/settings/telegram-bot',
                '/zooadmin/personal-data-consent',
            ];
            const usesFullPageEditor = /^\/zooadmin\/articles(?:\/create|\/[^/]+\/edit)$/.test(path)
                || path === '/zooadmin/personal-data-consent';

            if (usesFullPageEditor) return false;

            const isProfile = /^\/zooadmin\/(animals|clients)\/\d+$/.test(path);

            return standaloneEditors.includes(path) || isProfile || /\/create$/.test(path) || /\/edit$/.test(path);
        };

        const setEditorContent = (source) => {
            const heading = source.querySelector('h1, h2, h3');
            editorModalTitle.textContent = heading?.textContent.trim() || 'Редактирование';
            heading?.remove();
            editorModalBody.innerHTML = source.innerHTML;
        };

        const showEditorError = () => {
            editorModalBody.innerHTML = '<div class="alert alert-danger mb-0">Не удалось загрузить форму. Попробуйте ещё раз.</div>';
        };

        const openEditor = async (url) => {
            if (!editorModal || !editorModalBody) {
                window.location.href = url;
                return;
            }

            editorModalTitle.textContent = 'Загрузка…';
            editorModalBody.innerHTML = '<div class="admin-editor-loading">Загрузка формы…</div>';
            editorModal.show();

            try {
                const response = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const html = await response.text();
                const documentFromResponse = new DOMParser().parseFromString(html, 'text/html');
                const source = documentFromResponse.getElementById('admin-content');

                if (!response.ok || !source) {
                    window.location.href = url;
                    return;
                }

                setEditorContent(source);
            } catch (error) {
                showEditorError();
            }
        };

        document.addEventListener('click', (event) => {
            const link = event.target.closest('a[href]');
            if (!link || event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || link.target === '_blank') return;
            if (!isEditorUrl(new URL(link.href, window.location.origin))) return;

            event.preventDefault();
            openEditor(link.href);
        });

        editorModalBody?.addEventListener('submit', async (event) => {
            const form = event.target.closest('form');
            if (!form) return;

            event.preventDefault();
            const submitButton = form.querySelector('[type="submit"]');
            const originalLabel = submitButton?.innerHTML;
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Сохраняем…';
            }

            try {
                const response = await fetch(form.action, {
                    method: (form.method || 'POST').toUpperCase(),
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const html = await response.text();
                const documentFromResponse = new DOMParser().parseFromString(html, 'text/html');
                const source = documentFromResponse.getElementById('admin-content');
                const hasValidationErrors = source?.querySelector('.is-invalid, .alert-danger');

                if (!response.ok || !source) {
                    throw new Error('Form request failed');
                }

                if (hasValidationErrors) {
                    setEditorContent(source);
                    return;
                }

                sessionStorage.setItem(editorScrollKey, String(window.scrollY));
                editorModal.hide();
                window.location.reload();
            } catch (error) {
                const alert = document.createElement('div');
                alert.className = 'alert alert-danger mt-3 mb-0';
                alert.textContent = 'Не удалось сохранить изменения. Проверьте соединение и попробуйте ещё раз.';
                form.appendChild(alert);
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalLabel;
                }
            }
        });

        const isDesktop = () => window.innerWidth >= 992;
        let desktopOpen = true;
        let mobileOpen = false;
        let lastIsDesktop = isDesktop();

        const applySidebarState = () => {
            if (isDesktop()) {
                document.body.classList.toggle('sidebar-collapsed', !desktopOpen);
                document.body.classList.remove('sidebar-open');
                toggleButton?.setAttribute('aria-expanded', desktopOpen);
            } else {
                document.body.classList.remove('sidebar-collapsed');
                document.body.classList.toggle('sidebar-open', mobileOpen);
                toggleButton?.setAttribute('aria-expanded', mobileOpen);
            }
        };

        applySidebarState();

        toggleButton?.addEventListener('click', () => {
            if (isDesktop()) {
                desktopOpen = !desktopOpen;
            } else {
                mobileOpen = !mobileOpen;
            }
            applySidebarState();
        });

        backdrop?.addEventListener('click', () => {
            mobileOpen = false;
            applySidebarState();
        });

        window.addEventListener('resize', () => {
            const nowDesktop = isDesktop();
            if (nowDesktop !== lastIsDesktop) {
                lastIsDesktop = nowDesktop;
                applySidebarState();
            }
        });

        document.querySelectorAll('.content table').forEach((table) => {
            if (!table.closest('.table-responsive')) {
                const wrapper = document.createElement('div');
                wrapper.classList.add('table-responsive', 'admin-table-responsive');
                table.parentNode.insertBefore(wrapper, table);
                wrapper.appendChild(table);
            }

            table.classList.add('admin-flex-table');
            const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
            if (headers.length) {
                table.querySelectorAll('tbody tr').forEach((row) => {
                    Array.from(row.children).forEach((cell, idx) => {
                        if (!cell.dataset.label && headers[idx]) {
                            cell.dataset.label = headers[idx];
                        }
                    });
                });
            }
        });

        document.querySelectorAll('.admin-grid').forEach((grid)=>{
            const headerCells = grid.querySelectorAll(':scope > .admin-grid-header > *');
            if (!headerCells.length) return;
            const labels = Array.from(headerCells).map(cell => cell.textContent.trim());
            grid.querySelectorAll(':scope > .admin-grid-body > .admin-grid-row').forEach((row)=>{
                Array.from(row.children).forEach((cell, idx)=>{
                    if (!cell.dataset.label && labels[idx]) {
                        cell.dataset.label = labels[idx];
                    }
                });
            });
        });

        // Drag & drop сортировка
        if (window.Sortable) {
            document.querySelectorAll('.js-sortable').forEach((el)=>{
                if (el.dataset.customSort === '1') return;
                const sortable = Sortable.create(el, {
                    animation: 150,
                    handle: '.js-order-label',
                    onEnd: updateOrders
                });
                function updateOrders(){
                    const items = el.querySelectorAll('[data-id]')?.length ? el.querySelectorAll('[data-id]') : el.querySelectorAll('tr');
                    items.forEach((row, idx)=>{
                        const orderField = row.querySelector('.js-order-input');
                        const label = row.querySelector('.js-order-label');
                        if (orderField) orderField.value = idx + 1;
                        if (label) label.innerHTML = `<i class="fa fa-grip-vertical me-1"></i>${idx+1}`;
                    });
                }
                updateOrders();
            });
        }

        // Глобальная замена текста кнопок на иконки
        document.querySelectorAll('button, a').forEach((btn) => {
            if (btn.closest('.modal')) return;
            const txt = (btn.textContent || '').trim();
            if (txt === 'Редактировать') {
                btn.innerHTML = '<i class="fa fa-pen"></i>';
                btn.title = 'Редактировать';
                btn.classList.add('btn-icon');
            }
            if (txt === 'Удалить') {
                if (btn.hasAttribute('onclick')) btn.removeAttribute('onclick');
                btn.innerHTML = '<i class="fa fa-trash"></i>';
                btn.title = 'Удалить';
                btn.classList.add('btn-icon', 'js-delete-trigger');
                if (!btn.getAttribute('type')) {
                    btn.setAttribute('type','button');
                }
            }
        });

        // Глобальное подтверждение удаления через модалку
        const deleteModalEl = document.getElementById('confirmDeleteModal');
        const deleteTextEl = document.getElementById('confirmDeleteText');
        const deleteBtn = document.getElementById('confirmDeleteBtn');
        const deleteModal = deleteModalEl ? new bootstrap.Modal(deleteModalEl) : null;
        let deleteForm = null;
        document.addEventListener('click', (e)=>{
            const btn = e.target.closest('.js-delete-trigger, form.js-delete-form button[type="submit"]');
            if (!btn || !deleteModal) return;
            if (btn.closest('.modal')) return;
            const form = btn.closest('form');
            if (!form) return;
            e.preventDefault();
            deleteForm = form;
            deleteTextEl.textContent = btn.dataset.confirm || form.dataset.confirm || 'Удалить запись?';
            deleteModal.show();
        });
        deleteBtn?.addEventListener('click', ()=>{
            if (deleteForm) {
                deleteForm.submit();
                deleteForm = null;
            }
        });

        const toTop = document.getElementById('adminToTop');
        const updateToTopState = () => {
            if (!toTop) return;
            const shouldShow = (window.pageYOffset || document.documentElement.scrollTop) > 320;
            toTop.classList.toggle('is-visible', shouldShow);
        };

        toTop?.addEventListener('click', (e) => {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        window.addEventListener('scroll', updateToTopState);
        updateToTopState();
    });
</script>
@yield('scripts')
@stack('scripts')
</body>
</html>
