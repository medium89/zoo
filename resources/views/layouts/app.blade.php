<!DOCTYPE html>
<html lang="ru">
<head>
    @php($settings = \App\Models\SiteSetting::first())
    <meta charset="{{ $settings->charset ?? 'UTF-8' }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $settings->description ?? '' }}">
    <meta name="robots" content="{{ $settings->robots ?? '' }}">
    <meta property="og:title" content="{{ $settings->og_title ?? 'Заголовок страницы' }}">
    <meta property="og:description" content="{{ $settings->og_description ?? 'Описание' }}">
    <meta property="og:image" content="{{ $settings->og_image ?? 'https://example.com/image.jpg' }}">
    <meta property="og:url" content="{{ $settings->og_url ?? 'https://example.com/page' }}">
    <title>Зооняня</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
    @yield('content')
</body>
</html> 
