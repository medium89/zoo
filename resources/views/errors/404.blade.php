<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, follow">
    <title>Страница не найдена — Zooland</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --error-accent: #8c4dc7; --error-text: #383838; }
        * { box-sizing: border-box; }
        body { display: grid; min-height: 100svh; margin: 0; place-items: center; overflow-x: hidden; background: linear-gradient(145deg, #fff 0%, #fff8fc 54%, #f6efff 100%); color: var(--error-text); font-family: Inter, Arial, sans-serif; }
        .error-page { display: grid; width: min(1160px, 100%); min-height: 100svh; grid-template-columns: minmax(280px, .82fr) minmax(0, 1.45fr); align-items: center; gap: clamp(18px, 4vw, 64px); padding: clamp(28px, 5vw, 76px) clamp(20px, 5vw, 60px); }
        .error-page__content { position: relative; z-index: 1; min-width: 0; }
        .error-page__code { margin: 0; color: var(--error-accent); font-size: clamp(5.4rem, 13vw, 10rem); font-weight: 800; letter-spacing: -.09em; line-height: .82; }
        .error-page__title { max-width: 420px; margin: 28px 0 10px; font-size: clamp(1.65rem, 3.2vw, 2.6rem); font-weight: 800; line-height: 1.13; }
        .error-page__text { max-width: 400px; margin: 0; color: #515151; font-size: clamp(.98rem, 1.7vw, 1.12rem); line-height: 1.6; }
        .error-page__home { display: inline-flex; align-items: center; justify-content: center; min-height: 48px; margin-top: 28px; padding: 12px 21px; border: 2px solid var(--error-accent); border-radius: 10px; background: var(--error-accent); box-shadow: 0 10px 22px rgba(140, 77, 199, .24); color: #fff; font-size: .95rem; font-weight: 700; text-decoration: none; transition: transform .18s ease, background .18s ease, color .18s ease; }
        .error-page__home:hover, .error-page__home:focus-visible { background: #fff; color: var(--error-accent); transform: translateY(-2px); }
        .error-page__image-wrap { min-width: 0; }
        .error-page__image { display: block; width: min(100%, 760px); height: auto; margin-left: auto; filter: drop-shadow(0 18px 20px rgba(96, 45, 102, .12)); }
        @media (max-width: 760px) { .error-page { grid-template-columns: minmax(0, 1fr); justify-items: center; align-content: center; gap: 18px; padding: 40px 20px 26px; text-align: center; } .error-page__content { order: 1; width: 100%; max-width: 100%; } .error-page__image-wrap { order: 2; width: min(100%, 620px); } .error-page__code { letter-spacing: -.075em; } .error-page__title { width: 100%; max-width: 100%; margin: 18px auto 9px; overflow-wrap: anywhere; } .error-page__text { width: 100%; max-width: 100%; margin-inline: auto; overflow-wrap: anywhere; } .error-page__home { margin-top: 21px; } .error-page__image { width: 100%; } }
    </style>
</head>
<body>
    <main class="error-page">
        <section class="error-page__content" aria-labelledby="error-title">
            <p class="error-page__code" aria-hidden="true">404</p>
            <h1 class="error-page__title" id="error-title">Страница не найдена</h1>
            <p class="error-page__text">Возможно, ссылка устарела или страница переехала. Давайте вернёмся туда, где всё на месте.</p>
            <a class="error-page__home" href="{{ url('/') }}">На главную</a>
        </section>
        <div class="error-page__image-wrap">
            <img class="error-page__image" src="{{ asset('images/404-pets.png') }}" alt="Питомцы ищут игрушки">
        </div>
    </main>
</body>
</html>