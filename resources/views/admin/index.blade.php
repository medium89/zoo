<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админпанель</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/font-awesome/css/all.min.css') }}">
    <style>
        body { min-height: 100vh; }
        .sidebar {
            width: 220px;
            background: #343a40;
            color: #fff;
            min-height: 100vh;
            transition: width 0.3s;
        }
        .sidebar.collapsed {
            width: 0;
        }
        #sidebarToggle {
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1050;
        }
        .sidebar a {
            color: #fff;
            text-decoration: none;
            display: block;
            padding: 12px 20px;
        }
        .sidebar a.active, .sidebar a:hover {
            background: #495057;
        }
        .content {
            padding: 32px;
        }

        .sidebar-content {
            display: flex;
            flex-direction: column;
            height: 92vh;
        }



        .sidebar-content__item {
            display: flex;
            flex-direction: column;
            border-bottom: 2px solid #495057;
            border-top: 2px solid #495057;
            width: 100%;
        }

        .sidebar-content__item:nth-child(1) {
            justify-content: flex-start;
            align-self: flex-start;
        }

        .sidebar-content__item:nth-child(2) {
            justify-content: center;
            align-self: center;
        }

        .sidebar-content__item:nth-child(3) {
            justify-content: flex-end;
            align-self: flex-end;
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

    </style>
    @vite(['resources/js/app.js'])
</head>
<body>
<button id="sidebarToggle" class="btn btn-dark"><i class="fa fa-bars"></i></button>
<div class="d-flex">
    <nav id="sidebar" class="sidebar d-flex flex-column p-0">
        <h4 class="text-center py-3 border-bottom mb-0">
            <a href="{{ route('admin.settings') }}">Админпанель</a></h4>
        <div class="sidebar-content">
            <div class="sidebar-content__item">
                <a href="{{ route('admin.settings') }}" class="{{ request()->is('zooadmin/settings*') ? 'active' : '' }}">Настройки</a>
                <a href="/zooadmin/sliders" class="{{ request()->is('zooadmin/sliders*') ? 'active' : '' }}">Слайдер</a>
                <a href="/zooadmin/about" class="{{ request()->is('zooadmin/about*') ? 'active' : '' }}"><i class="fa fa-id-card me-2"></i>Обо мне</a>
                <a href="/zooadmin/advantages" class="{{ request()->is('zooadmin/advantages*') ? 'active' : '' }}"><i class="fa fa-star me-2"></i>Преимущества</a>
                <a href="/zooadmin/services" class="{{ request()->is('zooadmin/services*') ? 'active' : '' }}"><i class="fa fa-briefcase me-2"></i>Услуги</a>
                <a href="/zooadmin/galleries" class="{{ request()->is('zooadmin/galleries*') ? 'active' : '' }}"><i class="fa fa-image me-2"></i>Фотоальбом</a>
                <a href="/zooadmin/socials" class="{{ request()->is('zooadmin/socials*') ? 'active' : '' }}"><i class="fa fa-share-alt me-2"></i>Социальные контакты</a>
                <a href="{{ route('admin.feedbacks.index') }}" class="{{ request()->is('zooadmin/feedbacks*') ? 'active' : '' }}"><i class="fa fa-envelope me-2"></i>Обратная связь</a>
                <a href="{{ route('admin.boarding.index') }}" class="{{ request()->is('zooadmin/boarding*') ? 'active' : '' }}"><i class="fa fa-calendar-check me-2"></i>Передержка</a>
                <a href="{{ route('admin.articles.index') }}" class="{{ request()->is('zooadmin/articles*') ? 'active' : '' }}"><i class="fa fa-newspaper me-2"></i>Статьи</a>
                <a href="{{ route('admin.article-comments.index') }}" class="{{ request()->is('zooadmin/article-comments*') ? 'active' : '' }}"><i class="fa fa-comments me-2"></i>Комментарии</a>
            </div>
            <div class="sidebar-content__item">
                <a href="{{ route('admin.users.index') }}" class="{{ request()->is('zooadmin/users*') ? 'active' : '' }}">Пользователи</a>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('sidebarToggle').addEventListener('click', function () {
        document.getElementById('sidebar').classList.toggle('collapsed');
    });
</script>
@yield('scripts')
</body>
</html>
