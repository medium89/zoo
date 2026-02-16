<!DOCTYPE html>
<html lang="ru">
<head>
    @php($settings = \App\Models\SiteSetting::first())
    <meta charset="{{ $settings->charset ?? 'UTF-8' }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $settings->description ?? '' }}">
    <meta name="robots" content="{{ $settings->robots ?? '' }}">
    <link rel="icon" href="https://zooland22.ru/favicon.ico" type="image/x-icon">

    <meta property="og:title" content="{{ $settings->og_title ?? 'Заголовок страницы' }}">
    <meta property="og:description" content="{{ $settings->og_description ?? 'Описание' }}">
    <meta property="og:image" content="{{ $settings->og_image ?? 'https://example.com/image.jpg' }}">
    <meta property="og:url" content="{{ $settings->og_url ?? 'https://example.com/page' }}">

    <title>{{ $settings->title ?? 'Зооняня' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="/css/main.css">
    
</head>
<body class="{{ request()->is('/') ? 'landing' : '' }}">
    @yield('content')
    <a href="#" class="to-top" aria-label="Наверх">
        <i class="fas fa-arrow-up"></i>
    </a>
    <script src="/js/main.js"></script>
</body>
</html> 
