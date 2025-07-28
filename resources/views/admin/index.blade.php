<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админпанель</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; }
        .sidebar {
            min-width: 220px;
            max-width: 220px;
            background: #343a40;
            color: #fff;
            min-height: 100vh;
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

        .sidebar-content__item {
            display: flex;
            flex-direction: column;
            border-bottom: 2px solid #495057;
        }
    </style>
</head>
<body>
<div class="d-flex">
    <nav class="sidebar d-flex flex-column p-0">
        <h4 class="text-center py-3 border-bottom mb-0">Админпанель</h4>
        <div class="sidebar-content">
            <div class="sidebar-content__item">
                <a href="/zooadmin/sliders" class="{{ request()->is('zooadmin/sliders*') ? 'active' : '' }}">Слайдер</a>
                <a href="/zooadmin/about" class="{{ request()->is('zooadmin/about*') ? 'active' : '' }}">Обо мне</a>
                <a href="/zooadmin/advantages" class="{{ request()->is('zooadmin/advantages*') ? 'active' : '' }}">Преимущества</a>
                <a href="/zooadmin/services" class="{{ request()->is('zooadmin/services*') ? 'active' : '' }}">Услуги</a>
                <a href="/zooadmin/galleries" class="{{ request()->is('zooadmin/galleries*') ? 'active' : '' }}">Фотоальбом</a>
                <a href="/zooadmin/socials" class="{{ request()->is('zooadmin/socials*') ? 'active' : '' }}">Социальные контакты</a> 
            </div>
            <div class="sidebar-content__item">
                <a href="{{ route('users.index') }}" class="{{ request()->is('zooadmin/users*') ? 'active' : '' }}">Пользователи</a>
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
</body>
</html> 