<!DOCTYPE html>
<html lang="ru">
<head>
    @php($settings = \App\Models\SiteSetting::first())
    <meta charset="{{ $settings->charset ?? 'UTF-8' }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @if(auth()->user()?->is_admin)<meta name="csrf-token" content="{{ csrf_token() }}">@endif
    <meta name="description" content="{{ $settings->description ?? '' }}">
    <meta name="robots" content="{{ request()->is('v1') ? 'noindex, follow' : ($settings->robots ?? '') }}">
    <link rel="icon" href="{{ asset("favicon-32x32.webp") }}" type="image/webp">

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
    @if(config('services.recaptcha.site_key'))<link rel="stylesheet" href="{{ asset('css/captcha-inline.css') }}?v=20261029-2">@endif
    @if(!request()->is('v1') && !auth()->user()?->is_admin) @include('partials.site-preloader-head') @endif
    @stack('styles')
    
</head>
<body class="{{ (request()->is('/') || request()->is('v1')) ? 'landing' : '' }}">
    @if(!request()->is('v1') && !auth()->user()?->is_admin) @include('partials.site-preloader') @endif
    @yield('content')
    <a href="#" class="to-top" aria-label="Наверх">
        <i class="fas fa-arrow-up"></i>
    </a>
    <script src="/js/main.js?v=20261029-2"></script>
    @if(config('services.recaptcha.site_key'))
        <script src="{{ asset('js/captcha-inline.js') }}?v=20261029-2"></script>
        <script src="https://www.google.com/recaptcha/api.js?onload=captchaInlineReady&render=explicit" async defer onerror="window.captchaInlineLoadError?.()"></script>
    @endif
    @stack('scripts')
</body>
</html> 
