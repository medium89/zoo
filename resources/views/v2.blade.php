@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/v2.css') }}">
@endpush

@section('content')
@php
    $heroSlide = $sliders->first();
    $heroImage = $heroSlide?->image ? asset('storage/' . $heroSlide->image) : null;
    $heroText = trim(strip_tags((string) ($heroSlide?->text ?? '')));
    $primarySocial = $socials->first();
    $galleryItems = $galleries->take(9)->values();
    $featuredServices = $services->take(6);
    $recaptchaKey = config('services.recaptcha.site_key');
@endphp

<main class="v2-page">
    <header class="v2-header">
        <div class="v2-shell v2-header__inner">
            <a class="v2-brand" href="{{ url('/v2') }}" aria-label="Зооленд 22 — главная">
                <img src="{{ asset('assets/img/logo.png') }}" alt="Зооленд 22">
            </a>
            <nav class="v2-nav" aria-label="Основная навигация">
                <a href="#v2-top">Главная</a>
                <a href="#v2-about">Обо мне</a>
                <a href="#v2-services">Услуги</a>
                <a href="#v2-gallery">Фото</a>
                <a href="#v2-reviews">Отзывы</a>
                <a href="#v2-contacts">Контакты</a>
            </nav>
            <a class="v2-button v2-button--small" href="#v2-contacts">Оставить заявку</a>
        </div>
    </header>

    <section class="v2-hero" id="v2-top">
        <div class="v2-hero__orb v2-hero__orb--violet"></div>
        <div class="v2-hero__orb v2-hero__orb--coral"></div>
        <div class="v2-shell v2-hero__inner">
            <div class="v2-hero__copy">
                <p class="v2-eyebrow"><i class="fa-solid fa-heart"></i> Забота о питомцах в Барнауле</p>
                <h1>{{ $heroText ?: 'Заботливый уход за питомцем, пока вас нет дома' }}</h1>
                <p class="v2-hero__lead">Профессиональный уход, внимание и любовь вашего друга в привычной и безопасной обстановке.</p>
                <div class="v2-hero__actions">
                    <a class="v2-button" href="#v2-services">Выбрать услугу</a>
                    @if($primarySocial)
                        <a class="v2-button v2-button--outline" href="{{ $primarySocial->link }}" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> Написать</a>
                    @endif
                </div>
                <div class="v2-hero__chips">
                    <span><i class="fa-solid fa-shield-heart"></i> Бережный уход</span>
                    <span><i class="fa-solid fa-camera"></i> Фотоотчёты</span>
                    <span><i class="fa-solid fa-location-dot"></i> Барнаул и пригороды</span>
                </div>
            </div>
            <div class="v2-hero__visual {{ $heroImage ? '' : 'v2-hero__visual--empty' }}">
                @if($heroImage)
                    <img src="{{ $heroImage }}" alt="Забота о питомцах">
                @else
                    <i class="fa-solid fa-paw"></i>
                @endif
            </div>
        </div>
        <div class="v2-shell v2-stats" aria-label="Преимущества сервиса">
            <span><i class="fa-regular fa-circle-check"></i> Индивидуальный подход</span>
            <b></b>
            <span><i class="fa-solid fa-paw"></i> Любовь к каждому питомцу</span>
            <b></b>
            <span><i class="fa-solid fa-camera"></i> Ежедневные фото и видео</span>
        </div>
    </section>

    <section class="v2-section" id="v2-services">
        <div class="v2-shell">
            <div class="v2-section-heading">
                <p class="v2-eyebrow">Что я предлагаю</p>
                <h2>Выберите заботу для питомца</h2>
                <span class="v2-heading-mark"><i class="fa-solid fa-heart"></i></span>
            </div>
            @if($featuredServices->isNotEmpty())
                <div class="v2-services-grid">
                    @foreach($featuredServices as $service)
                        <article class="v2-service-card">
                            <div class="v2-service-card__image">
                                @if($service->image)
                                    <img src="{{ asset('storage/' . $service->image) }}" alt="{{ $service->title }}">
                                @else
                                    <i class="fa-solid fa-paw"></i>
                                @endif
                            </div>
                            <div class="v2-service-card__content">
                                <h3>{{ $service->title }}</h3>
                                <div>{!! $service->text !!}</div>
                            </div>
                            <a class="v2-round-link" href="#v2-contacts" aria-label="Оставить заявку на услугу {{ $service->title }}"><i class="fa-solid fa-arrow-right"></i></a>
                        </article>
                    @endforeach
                </div>
            @else
                <p class="v2-empty">Услуги скоро появятся.</p>
            @endif
        </div>
    </section>

    <section class="v2-section v2-section--tint" id="v2-advantages">
        <div class="v2-shell">
            <div class="v2-section-heading">
                <p class="v2-eyebrow">Надёжная помощь</p>
                <h2>Почему мне доверяют</h2>
                <span class="v2-heading-mark"><i class="fa-solid fa-heart"></i></span>
            </div>
            @if($advantages->isNotEmpty())
                <div class="v2-advantages-grid">
                    @foreach($advantages->take(3) as $advantage)
                        <article class="v2-advantage-card">
                            <div class="v2-advantage-card__icon">
                                @if($advantage->image)<img src="{{ asset('storage/' . $advantage->image) }}" alt="">@else<i class="fa-solid fa-heart"></i>@endif
                            </div>
                            <div><h3>{{ $advantage->title }}</h3><div>{!! $advantage->text !!}</div></div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="v2-section v2-process" id="v2-process">
        <div class="v2-shell">
            <div class="v2-section-heading">
                <p class="v2-eyebrow">Просто и понятно</p>
                <h2>Как всё происходит</h2>
                <span class="v2-heading-mark"><i class="fa-solid fa-heart"></i></span>
            </div>
            <div class="v2-process__grid">
                <article><span class="v2-process__number">1</span><div class="v2-process__icon"><i class="fa-regular fa-pen-to-square"></i></div><h3>Оставляете заявку</h3><p>Выбираете услугу и оставляете заявку на сайте или в мессенджере.</p></article>
                <article><span class="v2-process__number">2</span><div class="v2-process__icon"><i class="fa-regular fa-comments"></i></div><h3>Обсуждаем питомца</h3><p>Уточняем детали, привычки и пожелания по уходу.</p></article>
                <article><span class="v2-process__number">3</span><div class="v2-process__icon"><i class="fa-solid fa-camera"></i></div><h3>Получаете фотоотчёты</h3><p>Присылаю новости о вашем питомце, чтобы вы были спокойны.</p></article>
            </div>
        </div>
    </section>

    <section class="v2-section v2-showcase" id="v2-gallery">
        <div class="v2-shell v2-showcase__grid">
            <div class="v2-reviews" id="v2-reviews">
                <div class="v2-section-heading v2-section-heading--left">
                    <p class="v2-eyebrow">Мнения хозяев</p>
                    <h2>Отзывы клиентов</h2>
                    <span class="v2-heading-mark"><i class="fa-solid fa-heart"></i></span>
                </div>
                @forelse($avitoReviews->take(3) as $review)
                    <article class="v2-review-card">
                        <div class="v2-review-card__top">
                            <div class="v2-avatar">{{ mb_substr($review->name ?: 'Гость', 0, 1) }}</div>
                            <div><h3>{{ $review->name ?: 'Гость' }}</h3><small>{{ $review->review_date?->format('d.m.Y') }}</small></div>
                            <span class="v2-stars">★★★★★</span>
                        </div>
                        <p>{{ \Illuminate\Support\Str::limit($review->text, 220) }}</p>
                    </article>
                @empty
                    <p class="v2-empty">Отзывы появятся здесь совсем скоро.</p>
                @endforelse
                <a class="v2-button v2-button--outline" href="#v2-contacts">Оставить заявку <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="v2-gallery-grid" aria-label="Фото питомцев">
                @forelse($galleryItems as $gallery)
                    <a href="{{ asset('storage/' . $gallery->image) }}" target="_blank" class="v2-gallery-grid__item">
                        <img src="{{ asset('storage/' . $gallery->image) }}" alt="{{ $gallery->title ?: 'Фото питомца' }}">
                    </a>
                @empty
                    <div class="v2-gallery-grid__empty"><i class="fa-solid fa-images"></i></div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="v2-section v2-about" id="v2-about">
        <div class="v2-shell v2-about__grid">
            <div class="v2-about__image">
                @if($about?->image)<img src="{{ asset('storage/' . $about->image) }}" alt="Обо мне">@else<i class="fa-solid fa-heart"></i>@endif
            </div>
            <div>
                <p class="v2-eyebrow">Личное знакомство</p>
                <h2>Обо мне</h2>
                <div class="v2-about__text">{!! $about?->text ?: '<p>Я с любовью и ответственностью забочусь о питомцах.</p>' !!}</div>
                <a class="v2-button" href="#v2-contacts">Написать мне</a>
            </div>
        </div>
    </section>

    <section class="v2-contact" id="v2-contacts">
        <div class="v2-shell v2-contact__grid">
            <div class="v2-contact__intro">
                <p class="v2-eyebrow">Всегда на связи</p>
                <h2>Нужна забота<br>уже сегодня?</h2>
                <p>Оставьте заявку — отвечу быстро и подберём лучший вариант ухода.</p>
                <div class="v2-contact__socials">
                    @foreach($socials->take(2) as $social)
                        <a href="{{ $social->link }}" target="_blank" rel="noopener"><i class="{{ $social->icon }}"></i>{{ $social->link_text ?: $social->title }}</a>
                    @endforeach
                </div>
            </div>
            <div class="v2-contact__form-wrap">
                <h3>Оставить заявку</h3>
                <form class="v2-contact__form" action="{{ route('feedback.store') }}" method="POST">
                    @csrf
                    <label><span>Ваше имя</span><input type="text" name="name" required></label>
                    <label><span>Телефон</span><input type="tel" name="phone" required></label>
                    <label class="v2-contact__message"><span>Расскажите о питомце и ваших пожеланиях</span><textarea name="message" rows="3" required></textarea></label>
                    @if($recaptchaKey)<div class="g-recaptcha" data-sitekey="{{ $recaptchaKey }}"></div>@endif
                    <label class="v2-consent"><input type="checkbox" name="personal_data_consent" value="1" required><span>Я согласен(на) на обработку персональных данных</span></label>
                    <button class="v2-button v2-button--coral" type="submit">Отправить заявку</button>
                </form>
            </div>
        </div>
    </section>

    <section class="v2-map">
        <div class="v2-shell v2-map__inner">
            <div class="v2-map__card"><i class="fa-solid fa-location-dot"></i><div><strong>Работаю в Барнауле<br>и ближайших районах</strong><span>Уточните адрес — подскажу возможность выезда.</span></div></div>
        </div>
        <script type="text/javascript" charset="utf-8" async src="https://api-maps.yandex.ru/services/constructor/1.0/js/?um=constructor%3Af65e8307b40f10df603f7a788a3c051606fd92405aef27868560308c0349b593&amp;width=100%25&amp;height=330&amp;lang=ru_RU&amp;scroll=false&amp;drag=false"></script>
    </section>

    <footer class="v2-footer">
        <div class="v2-shell v2-footer__grid">
            <div><a class="v2-brand" href="#v2-top"><img src="{{ asset('assets/img/logo.png') }}" alt="Зооленд 22"></a><p>С любовью и ответственностью к каждому питомцу <i class="fa-solid fa-heart"></i></p></div>
            <div><h3>Навигация</h3><a href="#v2-about">Обо мне</a><a href="#v2-services">Услуги</a><a href="#v2-gallery">Фото</a><a href="#v2-reviews">Отзывы</a></div>
            <div><h3>Контакты</h3>@foreach($socials as $social)<a href="{{ $social->link }}" target="_blank" rel="noopener"><i class="{{ $social->icon }}"></i> {{ $social->link_text ?: $social->title }}</a>@endforeach</div>
        </div>
        <div class="v2-footer__bottom"><div class="v2-shell">© {{ date('Y') }} Зооленд 22. Все права защищены.</div></div>
    </footer>
</main>

@if($recaptchaKey)
    @push('scripts')<script src="https://www.google.com/recaptcha/api.js" async defer></script>@endpush
@endif
@endsection
