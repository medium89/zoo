<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админпанель</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --sidebar-width: 240px; }

        body {
            min-height: 100vh;
            background: #f7f8fa;
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

        .sidebar a.active, .sidebar a:hover {
            background: #2b3038;
            color: #fff;
            padding-left: 26px;
        }

        .content {
            padding: 32px;
            flex: 1 1 auto;
            width: 100%;
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
            align-items: flex-start;
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
        .content .admin-flex-table td .js-order-label,
        .js-order-label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            vertical-align: middle;
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

        /* CKEditor высота и читаемость */
        .ck-editor__editable_inline {
            min-height: 280px;
            border: 1px solid #ced4da !important;
            border-radius: 8px !important;
            padding: 12px 14px !important;
            background: #fff;
            resize: vertical;
        }
        .ck.ck-editor { width: 100%; }
        textarea.wysiwyg, textarea.js-wysiwyg { min-height: 220px; resize: vertical; }
        .ck-toolbar { border-radius: 8px 8px 0 0 !important; }

        /* Ползунки масштаба/качества */
        .form-range::-webkit-slider-thumb { background: #0d6efd; }
        .form-range::-moz-range-thumb { background: #0d6efd; }
        .form-range::-webkit-slider-runnable-track { background: #dfe3e8; }
        .form-range::-moz-range-track { background: #dfe3e8; }
    </style>
    @vite(['resources/js/app.js'])
</head>
<body>
<button id="sidebarToggle" class="btn btn-dark" aria-label="Переключить меню"><i class="fa fa-bars"></i></button>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<div class="d-flex admin-layout">
    <nav id="sidebar" class="sidebar d-flex flex-column p-0">
        <h4 class="text-center py-3 border-bottom mb-0">
            <a href="{{ route('admin.settings') }}">Админпанель</a></h4>
        <div class="sidebar-content">
            <div class="sidebar-content__item">
                <a href="{{ route('admin.settings') }}" class="{{ request()->is('zooadmin/settings*') ? 'active' : '' }}"><i class="fa fa-gear me-2"></i>Настройки</a>
                <a href="/zooadmin/sliders" class="{{ request()->is('zooadmin/sliders*') ? 'active' : '' }}"><i class="fa fa-photo-film me-2"></i>Слайдер</a>
                <a href="/zooadmin/about" class="{{ request()->is('zooadmin/about*') ? 'active' : '' }}"><i class="fa fa-id-card me-2"></i>Обо мне</a>
                <a href="/zooadmin/advantages" class="{{ request()->is('zooadmin/advantages*') ? 'active' : '' }}"><i class="fa fa-star me-2"></i>Преимущества</a>
                <a href="/zooadmin/services" class="{{ request()->is('zooadmin/services*') ? 'active' : '' }}"><i class="fa fa-briefcase me-2"></i>Услуги</a>
                <a href="/zooadmin/galleries" class="{{ request()->is('zooadmin/galleries*') ? 'active' : '' }}"><i class="fa fa-image me-2"></i>Фотоальбом</a>
                <a href="/zooadmin/socials" class="{{ request()->is('zooadmin/socials*') ? 'active' : '' }}"><i class="fa fa-share-alt me-2"></i>Социальные контакты</a>
                <a href="{{ route('admin.animals.index') }}" class="{{ request()->is('zooadmin/animals*') ? 'active' : '' }}"><i class="fa fa-paw me-2"></i>Питомцы</a>
                <a href="{{ route('admin.feedbacks.index') }}" class="{{ request()->is('zooadmin/feedbacks*') ? 'active' : '' }}"><i class="fa fa-envelope me-2"></i>Обратная связь</a>
                <a href="{{ route('admin.boarding.index') }}" class="{{ request()->is('zooadmin/boarding*') ? 'active' : '' }}"><i class="fa fa-calendar-check me-2"></i>Передержка</a>
                <a href="{{ route('admin.articles.index') }}" class="{{ request()->is('zooadmin/articles*') ? 'active' : '' }}"><i class="fa fa-newspaper me-2"></i>Статьи</a>
                <a href="{{ route('admin.article-comments.index') }}" class="{{ request()->is('zooadmin/article-comments*') ? 'active' : '' }}"><i class="fa fa-comments me-2"></i>Комментарии</a>
            </div>
            <div class="sidebar-content__item">
                <a href="{{ route('admin.users.index') }}" class="{{ request()->is('zooadmin/users*') ? 'active' : '' }}"><i class="fa fa-users me-2"></i>Пользователи</a>
            </div>
            <div class="sidebar-content__item">
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
        @yield('content')
    </div>
</div>
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

        // Drag & drop сортировка
        if (window.Sortable) {
            document.querySelectorAll('.js-sortable').forEach((el)=>{
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
    });
</script>
@yield('scripts')
@stack('scripts')
</body>
</html>
