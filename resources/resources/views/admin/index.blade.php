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
    </style>
</head>
<body>
<div class="d-flex">
    <nav class="sidebar d-flex flex-column p-0">
        <h4 class="text-center py-3 border-bottom mb-0">Админпанель</h4>
        <a href="#" class="active">Слайдер</a>
        <a href="#">Обо мне</a>
        <a href="#">Преимущества</a>
        <a href="#">Услуги</a>
        <a href="#">Фотоальбом</a>
        <a href="{{ route('logout') }}"
           onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
           class="mt-auto border-top">Выйти</a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
    </nav>
    <div class="content flex-grow-1">
        <h2>Добро пожаловать в админпанель!</h2>
        <p>Выберите раздел для редактирования.</p>
    </div>
</div>
</body>
</html> 