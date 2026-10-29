@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/v2.css') }}?v=20261029-9">
@if(auth()->user()?->is_admin)
<link rel="stylesheet" href="{{ asset('css/admin-editor.css') }}?v=20261029-1">
<link rel="stylesheet" href="{{ asset('css/visual-editor.css') }}?v=20261029-3">
@endif
@endpush

@section('content')
@php
    $heroSlide = $sliders->first(fn ($slide) => filled($slide->image));
    $heroText = $siteSettings?->hero_text ?: trim(strip_tags((string) ($heroSlide?->text ?? '')));
    $heroMode = $siteSettings?->hero_mode === 'slider' && $sliders->whereNotNull('image')->isNotEmpty() ? 'slider' : 'image';
    $heroImage = $siteSettings?->hero_image ? asset('storage/' . $siteSettings->hero_image) : asset('images/v2/hero.webp');
    $recaptchaKey = config('services.recaptcha.site_key');
    $initialGalleryCount = $galleries->count();
    $hasMoreGallery = \App\Models\Gallery::where('active', true)->count() > $initialGalleryCount;
@endphp
<main class="v2-page" id="v2-top">
@if(auth()->user()?->is_admin) @include('partials.visual-editor') @endif
    <header id="index">
        <div class="header-content container">
            <a href="#v2-top" class="logo" aria-label="Зоолэнд22 — главная">
                <img src="{{ asset('assets/img/logo.webp') }}" alt="Логотип">
            </a>
            <nav class="nav" aria-label="Основная навигация">
                <div class="nav-toggle" id="navToggle">
                    <span></span><span></span><span></span>
                </div>
                <div class="nav-menu" id="navMenu" data-ve-group="nav_links">
                    @foreach($navLinks as $link)
                        @php
                            $legacyAnchors = ['index' => '#index', 'about' => '#about', 'advantages' => '#advantages', 'services' => '#services', 'gallery' => '#gallery', 'reviews' => '#reviews', 'contacts' => '#contacts'];
                            $v2Anchors = ['index' => '#v2-top', 'about' => '#v2-about', 'advantages' => '#v2-advantages', 'services' => '#v2-services', 'gallery' => '#v2-gallery', 'reviews' => '#v2-reviews', 'contacts' => '#v2-contacts'];
                            $href = ($legacyAnchors[$link->key] ?? null) === $link->href ? ($v2Anchors[$link->key] ?? $link->href) : $link->href;
                        @endphp
                        @if(auth()->user()?->is_admin)
                            <div class="ve-nav-item" data-ve-item="nav_links" data-ve-id="{{ $link->id }}" data-ve-type="nav_link">
                                <a href="{{ $href }}" class="{{ $link->key === 'articles' ? 'nav-articles' : '' }}"><span data-ve-field="label">{{ $link->label }}</span></a>
                                <button type="button" class="ve-nav-link-edit" data-ve-field="href" aria-label="Изменить ссылку пункта {{ $link->label }}" title="Изменить ссылку">↗</button>
                            </div>
                        @else
                            <a href="{{ $href }}" class="{{ $link->key === 'articles' ? 'nav-articles' : '' }}"><span>{{ $link->label }}</span></a>
                        @endif
                    @endforeach
                    @if(auth()->user()?->is_admin)<button type="button" class="ve-add ve-add--nav" data-ve-add-nav aria-label="Добавить пункт меню" title="Добавить пункт меню">+</button>@endif
                </div>
            </nav>
        </div>
    </header>

    <section class="v2-hero" aria-label="Зоолэнд22" data-hero-mode="{{ $heroMode }}" @if($heroMode === 'image') data-ve-image-type="settings" data-ve-image-field="hero_image" @endif @if($heroMode === 'image') style="background-image: url('{{ $heroImage }}')" @endif>
        @if($heroMode === 'slider')
            <div class="v2-hero__slides" aria-live="off">
                @foreach($sliders->filter(fn ($slide) => filled($slide->image)) as $slide)
                    <div class="v2-hero__slide {{ $loop->first ? 'is-active' : '' }}" style="background-image: url('{{ asset('storage/' . $slide->image) }}')" data-hero-text="{{ trim(strip_tags((string) $slide->text)) }}"></div>
                @endforeach
            </div>
        @endif
        <div class="v2-hero__shade"></div>
        <div class="v2-shell v2-hero__inner">
            <div class="v2-hero__copy">
                <h1 data-ve-type="{{ $heroMode === 'slider' ? 'slider' : 'settings' }}" data-ve-id="{{ $heroMode === 'slider' ? $heroSlide?->id : $siteSettings?->id }}" data-ve-field="{{ $heroMode === 'slider' ? 'text' : 'hero_text' }}">{{ $heroMode === 'slider' ? (trim(strip_tags((string) $heroSlide?->text)) ?: 'Оставить заявку можно в разделе «Контакты»') : ($heroText ?: 'Оставить заявку можно в разделе «Контакты»') }}</h1>
                <a class="v2-button v2-button--pink" href="#v2-contacts">Оставить заявку <i class="fa-solid fa-paw" aria-hidden="true"></i></a>
            </div>
        </div>
        @if($heroMode === 'slider' && $sliders->filter(fn ($slide) => filled($slide->image))->count() > 1)
            <div class="v2-hero__controls" aria-label="Управление слайдером">
                <button type="button" data-hero-prev aria-label="Предыдущий слайд">‹</button>
                <button type="button" data-hero-next aria-label="Следующий слайд">›</button>
            </div>
        @endif
    </section>

    <section class="v2-section v2-about" id="v2-about">
        <div class="v2-shell">
            <h2 class="v2-heading"><span data-ve-type="label" data-ve-key="about" data-ve-field="home_label">{{ $siteSettings?->home_labels['about'] ?? 'Обо мне' }}</span> <span class="v2-paw" aria-hidden="true"></span></h2>
            <div class="v2-about__grid">
                <div class="v2-about__photo" data-ve-image-type="about" data-ve-image-id="{{ $about?->id }}" data-ve-image-field="image">
                    @if($about?->image)
                        <img src="{{ asset('storage/' . $about->image) }}" alt="Екатерина с питомцем" loading="lazy">
                    @endif
                </div>
                <div class="v2-about__text" data-ve-type="about" data-ve-id="{{ $about?->id }}" data-ve-field="text">{!! $about?->text !!}</div>
            </div>
        </div>
    </section>

    <section class="v2-section v2-advantages" id="v2-advantages" data-ve-type="settings" @if($siteSettings?->advantages_bg_color) style="--advantages-bg:{{ $siteSettings->advantages_bg_color }}" @endif>
        <div class="v2-shell">
            <h2 class="v2-heading v2-heading--light"><span data-ve-type="label" data-ve-key="advantages" data-ve-field="home_label">{{ $siteSettings?->home_labels['advantages'] ?? 'Преимущества' }}</span> <span class="v2-paw v2-paw--light" aria-hidden="true"></span> @if(auth()->user()?->is_admin)<a class="ve-add" href="{{ route('admin.advantages.create') }}" aria-label="Добавить преимущество" title="Добавить преимущество">+</a>@endif @if(auth()->user()?->is_admin)<button type="button" class="ve-color-button" data-ve-type="settings" data-ve-field="advantages_bg_color" aria-label="Цвет фона преимуществ" title="Цвет фона преимуществ">🎨</button>@endif</h2>
            @if($advantages->isNotEmpty())
                <div class="v2-advantages__grid" data-ve-group="advantages">
                    @foreach($advantages as $advantage)
                        <article class="v2-advantage" data-ve-item="advantages" data-ve-id="{{ $advantage->id }}" data-ve-type="advantage">
                            <div class="v2-advantage__icon" data-ve-image-type="advantage" data-ve-image-id="{{ $advantage->id }}" data-ve-image-field="image"><img src="{{ $advantage->image ? asset('storage/' . $advantage->image) : asset('images/image-placeholder.svg') }}" alt="" loading="lazy"></div>
                            <div class="v2-advantage__body">
                                <h3 data-ve-field="title">{{ $advantage->title }}</h3>
                                <div class="v2-richtext v2-advantage__copy" data-ve-field="short_text">{!! $advantage->short_text ?: \Illuminate\Support\Str::words(strip_tags($advantage->text), 26, '') !!}</div>
                                <div class="v2-advantage__full" data-ve-field="text" hidden>{!! $advantage->text !!}</div>
                                @if(auth()->user()?->is_admin)<button type="button" class="ve-full-button" data-ve-edit-full>Править полный текст</button>@endif
                                <button class="v2-more-link v2-advantage__more" type="button">Читать полностью <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="v2-section v2-services" id="v2-services">
        <div class="v2-shell">
            <h2 class="v2-heading"><span data-ve-type="label" data-ve-key="services" data-ve-field="home_label">{{ $siteSettings?->home_labels['services'] ?? 'Услуги' }}</span> <span class="v2-paw" aria-hidden="true"></span> @if(auth()->user()?->is_admin)<a class="ve-add" href="{{ route('admin.services.create') }}" aria-label="Добавить услугу" title="Добавить услугу">+</a>@endif</h2>
            @if($services->isNotEmpty())
                <div class="v2-services__grid" data-ve-group="services">
                    @foreach($services as $service)
                        <article class="v2-service {{ str_contains(mb_strtolower($service->title), 'рыб') ? 'v2-service--fish' : '' }}" data-sticker data-fold-side="left" data-ve-item="services" data-ve-id="{{ $service->id }}" data-ve-type="service" @if($service->sticker_color || $service->tape_color) style="@if($service->sticker_color)--sticker-base:{{ $service->sticker_color }};@endif @if($service->tape_color)--tape-override:{{ $service->tape_color }};@endif" @endif @if($service->sticker_color) data-custom-sticker-color @endif>
                            <svg class="v2-service__paper" aria-hidden="true" focusable="false">
                                <defs>
                                    <linearGradient class="v2-service__paper-fill" id="v2-paper-{{ $loop->iteration }}" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0"/><stop offset="1"/>
                                    </linearGradient>
                                    <linearGradient class="v2-service__fold-fill" id="v2-fold-{{ $loop->iteration }}" x1="0" y1="0" x2="1" y2="1">
                                        <stop offset="0"/><stop offset=".52"/><stop offset="1"/>
                                    </linearGradient>
                                    <filter id="v2-paper-shadow-{{ $loop->iteration }}" x="-30%" y="-30%" width="160%" height="170%">
                                        <feDropShadow dx="0" dy="11" stdDeviation="11" flood-opacity=".19"/>
                                    </filter>
                                </defs>
                                <path data-silhouette fill="url(#v2-paper-{{ $loop->iteration }})" filter="url(#v2-paper-shadow-{{ $loop->iteration }})"/>
                                <path data-flap fill="url(#v2-fold-{{ $loop->iteration }})"/>
                                <path data-seam fill="none" stroke="#fff" stroke-width="7.5" stroke-linejoin="round" stroke-linecap="round"/>
                            </svg>
                            <svg class="v2-service__tape" viewBox="0 0 32 96" preserveAspectRatio="none" aria-hidden="true" focusable="false">
                                <path class="v2-service__tape-paper" d="M5 8 8 5 12 7 16 4 20 7 24 5 27 8V88l-3 3-4-2-4 3-4-3-4 2-3-3Z"/>
                            </svg>
                            <div class="v2-service__icon" data-ve-image-type="service" data-ve-image-id="{{ $service->id }}" data-ve-image-field="sticker_image"><img src="{{ $service->stickerImageUrl() }}" alt="" loading="lazy"></div>
                            @if(auth()->user()?->is_admin)
                            <div class="ve-color-tools">
                                <button type="button" data-ve-field="sticker_color" class="ve-color-button" title="Цвет стикера" aria-label="Цвет стикера">Фон</button>
                                <button type="button" data-ve-field="tape_color" class="ve-color-button" title="Цвет липучки" aria-label="Цвет липучки">Липучка</button>
                            </div>
                            @endif
                            <div class="v2-service__body">
                                <h3 data-ve-field="title">{{ $service->title }}</h3>
                                <div class="v2-richtext" data-ve-field="text">{!! $service->text !!}</div>
                                @if(!empty($service->tags))
                                    <div class="v2-service__tags">
                                        @foreach($service->tags as $tag)
                                            <span class="v2-service__tag" data-ve-field="tag:{{ $loop->index }}">{{ $tag }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <span class="ve-image-doodle" data-ve-image-type="service" data-ve-image-id="{{ $service->id }}" data-ve-image-field="sticker_doodle"><img class="v2-service__doodle" src="{{ $service->stickerDoodleUrl() }}" alt="" aria-hidden="true" loading="lazy"></span>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="v2-section v2-gallery" id="v2-gallery">
        <div class="v2-shell">
            <h2 class="v2-heading"><span data-ve-type="label" data-ve-key="gallery" data-ve-field="home_label">{{ $siteSettings?->home_labels['gallery'] ?? 'Фотоальбом' }}</span> <span class="v2-paw" aria-hidden="true"></span> @if(auth()->user()?->is_admin)<a class="ve-add" href="{{ route('admin.galleries.create') }}" aria-label="Добавить фото" title="Добавить фото">+</a>@endif</h2>
            <div class="v2-gallery__grid" id="v2-gallery-grid" data-ve-group="galleries">
                @foreach($galleries as $gallery)
                    @php
                        $thumb = preg_replace('/^galleries\\//', 'galleries/thumbs/', $gallery->image);
                        $thumbUrl = \Illuminate\Support\Facades\Storage::disk('public')->exists($thumb) ? asset('storage/' . $thumb) : asset('storage/' . $gallery->image);
                    @endphp
                    <button class="v2-gallery__item" type="button" data-ve-item="galleries" data-ve-id="{{ $gallery->id }}" data-id="{{ $gallery->id }}" data-full="{{ asset('storage/' . $gallery->image) }}" data-ve-image-type="gallery" data-ve-image-id="{{ $gallery->id }}" data-ve-image-field="image" aria-label="Открыть фото {{ $gallery->number }}"><img src="{{ $thumbUrl }}" alt="Фото {{ $gallery->number }}" loading="lazy"></button>
                @endforeach
            </div>
            @if($hasMoreGallery)
                <div class="v2-gallery__more"><button class="v2-button v2-button--violet" id="v2-gallery-more" type="button" data-offset="{{ $initialGalleryCount }}">Загрузить ещё</button></div>
            @endif
        </div>
    </section>

    <section class="v2-section v2-reviews" id="v2-reviews">
        <div class="v2-shell">
            <div class="v2-reviews__heading">
                <h2 class="v2-heading"><span data-ve-type="label" data-ve-key="reviews" data-ve-field="home_label">{{ $siteSettings?->home_labels['reviews'] ?? 'Отзывы клиентов' }}</span> <span class="v2-paw" aria-hidden="true"></span> @if(auth()->user()?->is_admin)<a class="ve-add" href="{{ route('admin.avito-reviews.create') }}" aria-label="Добавить отзыв" title="Добавить отзыв">+</a>@endif</h2>
                <button class="v2-button v2-button--violet v2-write-review" type="button" data-open-review>Оставить отзыв <i class="fa-solid fa-pen" aria-hidden="true"></i></button>
            </div>
            @if(session('review_success'))<p class="v2-review-success" role="status">{{ session('review_success') }}</p>@endif
            @if($avitoReviews->isNotEmpty() || $siteReviews->isNotEmpty())
                <div class="v2-reviews__grid" id="v2-reviews-track" data-ve-group="reviews">
                    @foreach($reviewItems as $item)
                        @php
                            $review = $item['review'];
                        @endphp
                        @if($item['type'] === 'avito')
                            <article class="v2-review" data-ve-item="reviews" data-ve-key="{{ $item['key'] }}" data-ve-type="avito_review" data-ve-id="{{ $review->id }}">
                                <div class="v2-review__top"><strong data-ve-field="name">{{ $review->name ?: 'Гость' }}</strong>@if($review->review_date)<time datetime="{{ $review->review_date->format('Y-m-d') }}">{{ $review->review_date->format('d.m.Y') }}</time>@endif</div>
                                <div class="v2-review__stars" aria-label="5 из 5 звёзд">★★★★★</div>
                                <p class="v2-review__text" data-ve-field="text">{{ $review->text }}</p>
                                <div class="v2-review__actions"><button type="button" class="v2-more-link v2-review__more">Подробнее <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>@if($review->original_url)<a class="v2-review__original" href="{{ $review->original_url }}" target="_blank" rel="noopener noreferrer">Оригинал на Avito <i class="fa-solid fa-up-right-from-square" aria-hidden="true"></i></a>@endif</div>
                            </article>
                        @else
                            <article class="v2-review" data-ve-item="reviews" data-ve-key="{{ $item['key'] }}" data-ve-type="site_review" data-ve-id="{{ $review->id }}">
                                <div class="v2-review__top"><strong data-ve-field="name">{{ $review->name }}</strong><time datetime="{{ $review->created_at->format('Y-m-d') }}">{{ $review->created_at->format('d.m.Y') }}</time></div>
                                <div class="v2-review__stars" aria-label="{{ $review->rating }} из 5 звёзд">{{ str_repeat('★', $review->rating) }}</div>
                                <p class="v2-review__text" data-ve-field="text">{{ $review->text }}</p>
                                <div class="v2-review__actions"><button type="button" class="v2-more-link v2-review__more">Подробнее <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button></div>
                            </article>
                        @endif
                    @endforeach
                </div>
                @if($avitoReviews->count() + $siteReviews->count() > 3)
                    <div class="v2-reviews__controls">
                        <button type="button" id="v2-reviews-prev" aria-label="Предыдущие отзывы"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14.5 5-7 7 7 7"/></svg></button>
                        <button type="button" id="v2-reviews-next" aria-label="Следующие отзывы"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9.5 5 7 7-7 7"/></svg></button>
                    </div>
                @endif
            @endif
        </div>
    </section>

    <section class="v2-section v2-contact" id="v2-contacts">
        <div class="v2-shell">
            <h2 class="v2-heading v2-heading--light"><span data-ve-type="label" data-ve-key="contacts" data-ve-field="home_label">{{ $siteSettings?->home_labels['contacts'] ?? 'Контакты' }}</span> <span class="v2-paw v2-paw--light" aria-hidden="true"></span></h2>
            <div class="v2-contact__grid">
                <div class="v2-panel">
                    <h3 data-ve-type="label" data-ve-key="contact_form" data-ve-field="home_label">{{ $siteSettings?->home_labels['contact_form'] ?? 'Оставить заявку' }}</h3>
                    <form data-captcha-form action="{{ route('feedback.store') }}" method="POST">
                        @csrf
                        <div class="v2-contact__fields">
                            <label><span class="visually-hidden">Ваше имя</span><input type="text" name="name" placeholder="Ваше имя" value="{{ old('name') }}" required autocomplete="name"></label>
                            <label><span class="visually-hidden">Телефон</span><input type="tel" name="phone" placeholder="+7 (999) 999-99-99" value="{{ old('phone') }}" required autocomplete="tel"></label>
                        </div>
                        <label><span class="visually-hidden">Сообщение</span><textarea name="message" placeholder="Опишите необходимую услугу, животное, даты и адрес" rows="4" required>{{ old('message') }}</textarea></label>
                                    @include('partials.captcha-inline')
                        <div class="v2-consent"><input type="checkbox" id="v2-consent" name="personal_data_consent" value="1" required><span><label for="v2-consent">Нажимая кнопку, я даю согласие на обработку персональных данных.</label> <button type="button" class="v2-consent__link" data-consent-open>Ознакомиться с документом</button></span></div>
                        @if($errors->any())<p class="v2-form-error">{{ $errors->first() }}</p>@endif
                        @if(session('success'))<p class="v2-form-success">{{ session('success') }}</p>@endif
                        <button class="v2-button v2-button--pink" type="submit">Отправить заявку <i class="fa-solid fa-paw" aria-hidden="true"></i></button>
                    </form>
                </div>
                <div class="v2-panel v2-socials" data-ve-group="socials">
                    <h3 data-ve-type="label" data-ve-key="socials" data-ve-field="home_label">{{ $siteSettings?->home_labels['socials'] ?? 'Или напишите напрямую' }} @if(auth()->user()?->is_admin)<a class="ve-add" href="{{ route('admin.socials.create') }}" aria-label="Добавить социальный контакт" title="Добавить социальный контакт">+</a>@endif</h3>
                    @foreach($socials as $social)
                        @php
                            $channel = mb_strtolower($social->title . ' ' . $social->link);
                            $brand = str_contains($channel, 'whatsapp') || str_contains($channel, 'wa.me') ? 'whatsapp' : (str_contains($channel, 'telegram') || str_contains($channel, 't.me') ? 'telegram' : (str_contains($channel, 'vk.com') || str_contains($channel, 'вконтакте') ? 'vk' : (str_contains($channel, 'ok.ru') || str_contains($channel, 'одноклассники') ? 'ok' : 'other')));
                            $brandIcon = ['whatsapp' => 'fa-brands fa-whatsapp', 'telegram' => 'fa-brands fa-telegram', 'vk' => 'fa-brands fa-vk', 'ok' => 'fa-brands fa-odnoklassniki'][$brand] ?? $social->icon;
                        @endphp
                        <a class="v2-social v2-social--{{ $brand }}" data-ve-item="socials" data-ve-id="{{ $social->id }}" data-ve-type="social" href="{{ $social->link }}" target="_blank" rel="noopener noreferrer">
                            <span class="v2-social__icon"><i class="{{ $brandIcon }}" aria-hidden="true"></i></span>
                            <span class="v2-social__details"><strong data-ve-field="title">{{ $social->title }}</strong>@if($social->text)<span class="v2-social__description" data-ve-field="text">{!! $social->text !!}</span>@endif</span>
                            <span class="v2-social__arrow" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 12h13m-5-5 5 5-5 5"/></svg></span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="v2-map" aria-label="Расположение в Барнауле">
        <script type="text/javascript" charset="utf-8" async src="https://api-maps.yandex.ru/services/constructor/1.0/js/?um=constructor%3Af65e8307b40f10df603f7a788a3c051606fd92405aef27868560308c0349b593&amp;width=100%25&amp;height=330&amp;lang=ru_RU&amp;scroll=false&amp;drag=false"></script>
    </section>
    <footer class="v2-footer"><div class="v2-shell">© {{ date('Y') }} Зоолэнд22. Уход за вашими питомцами в Барнауле.</div></footer>
</main>
@include('sections.social-bar')
<aside class="v2-quick-contact" id="v2QuickContactBadge" aria-label="Быстрая заявка" hidden>
    <a class="v2-quick-contact__link" href="#v2-contacts"><i class="fa-solid fa-paw" aria-hidden="true"></i><span>Оставить заявку</span></a>
    <button class="v2-quick-contact__close" type="button" aria-label="Скрыть плашку">×</button>
</aside>
<div id="personalDataConsentModal" class="consent-modal" aria-hidden="true">
    <div class="consent-modal__backdrop" data-consent-close></div>
    <div class="consent-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="consentModalTitle">
        <button type="button" class="consent-modal__close" aria-label="Закрыть" data-consent-close>&times;</button>
        <h4 id="consentModalTitle">Согласие на обработку персональных данных</h4>
        <div class="consent-modal__content">
            @if(!empty(trim(strip_tags((string)$personalDataConsentText))))
                {!! $personalDataConsentText !!}
            @else
                <p>Текст согласия временно недоступен.</p>
            @endif
        </div>
    </div>
</div>
<div class="v2-modal" id="v2-detail-modal" role="dialog" aria-modal="true" aria-labelledby="v2-detail-title" hidden>
    <div class="v2-modal__backdrop" data-close-modal></div>
    <div class="v2-modal__dialog">
        <button class="v2-modal__close" type="button" data-close-modal aria-label="Закрыть">×</button>
        <h2 id="v2-detail-title"></h2>
        <div class="v2-modal__content" id="v2-detail-content"></div>
    </div>
</div>
<div class="v2-modal" id="v2-review-modal" data-reopen="{{ old('_form') === 'site-review' && $errors->any() ? '1' : '0' }}" role="dialog" aria-modal="true" aria-labelledby="v2-review-title" hidden>
    <div class="v2-modal__backdrop" data-close-modal></div>
    <div class="v2-modal__dialog">
        <button class="v2-modal__close" type="button" data-close-modal aria-label="Закрыть">×</button>
        <h2 id="v2-review-title">Оставить отзыв</h2>
        <p class="v2-modal__intro">Расскажите, как прошёл уход за вашим питомцем. Отзыв появится на сайте после проверки.</p>
        <form class="v2-review-form" data-captcha-form action="{{ route('site-reviews.store') }}" method="POST">
            @csrf
            <input type="hidden" name="_form" value="site-review">
            <div class="v2-honeypot" aria-hidden="true"><label>Ваш сайт<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
            <label class="v2-review-form__label" for="v2-review-name">Ваше имя</label>
            <input id="v2-review-name" type="text" name="name" value="{{ old('_form') === 'site-review' ? old('name') : '' }}" maxlength="120" required>

            <label class="v2-review-form__label" for="v2-review-text">Ваш отзыв</label>
            <textarea id="v2-review-text" name="text" rows="6" minlength="10" maxlength="5000" required>{{ old('_form') === 'site-review' ? old('text') : '' }}</textarea>
            <fieldset class="v2-rating"><legend>Ваша оценка</legend>
                <div class="v2-rating__stars">
                    @for($star = 5; $star >= 1; $star--)
                        <input type="radio" id="v2-rating-{{ $star }}" name="rating" value="{{ $star }}" @checked(old('_form') === 'site-review' && old('rating') == $star) required>
                        <label for="v2-rating-{{ $star }}" aria-label="{{ $star }} из 5 звёзд">★</label>
                    @endfor
                </div>
            </fieldset>
            @include('partials.captcha-inline')
            <div class="v2-consent"><input type="checkbox" id="v2-review-consent" name="personal_data_consent" value="1" required><span><label for="v2-review-consent">Я согласен(на) на обработку персональных данных.</label> <button type="button" class="v2-consent__link" data-consent-open>Ознакомиться с документом</button></span></div>
            @if(old('_form') === 'site-review' && $errors->any())<p class="v2-form-error">{{ $errors->first() }}</p>@endif
            <button class="v2-button v2-button--pink" type="submit">Отправить отзыв <i class="fa-solid fa-paw" aria-hidden="true"></i></button>
        </form>
    </div>
</div>
<div class="v2-lightbox" id="v2-lightbox" role="dialog" aria-modal="true" aria-label="Просмотр фотографии" hidden><button type="button" class="v2-lightbox__close" aria-label="Закрыть">×</button><button type="button" class="v2-lightbox__prev" aria-label="Предыдущее фото">‹</button><img src="" alt="Фото питомца"><button type="button" class="v2-lightbox__next" aria-label="Следующее фото">›</button></div>
@push('scripts')
<script src="{{ asset('js/v2.js') }}?v=20261029-2" defer></script>
<script src="{{ asset('js/v2-reveal.js') }}?v=20261029-1" defer></script>
@if(auth()->user()?->is_admin)
<script src="{{ asset('js/admin-editor.js') }}?v=20261029-1" defer></script>
<script src="{{ asset('js/visual-editor.js') }}?v=20261029-2" defer></script>
@endif
@endpush
@endsection
