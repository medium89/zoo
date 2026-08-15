@php
    use Illuminate\Support\Str;
@endphp

@if(isset($avitoReviews) && $avitoReviews->count())
<section id="reviews">
    <div class="reviews-container container">
        <div class="reviews-header">
            <h2>Отзывы клиентов</h2>
            <div class="foot"></div>
        </div>
        <div class="reviews-carousel" id="reviews-carousel">
            <button class="reviews-arrow reviews-arrow_prev" type="button" aria-label="Предыдущий отзыв">
                <i class="fa fa-chevron-left"></i>
            </button>
            <div class="reviews-viewport">
                <div class="reviews-track">
                    @foreach($avitoReviews as $review)
                        @php
                            $photos = is_array($review->photos) ? $review->photos : [];
                            $rawPhoto = $review->avatar_url ?: ($photos[0] ?? null);
                            $photoUrl = null;
                            if ($rawPhoto) {
                                $photoUrl = preg_match('#^https?://#', $rawPhoto)
                                    ? $rawPhoto
                                    : asset('storage/' . ltrim($rawPhoto, '/'));
                            }
                            $name = $review->name ?: 'Гость';
                            $excerpt = Str::limit($review->text ?? '', 260);
                            $text = $review->text ?? '';
                        @endphp
                        <div class="review-slide">
                            <article class="review-card">
                                <div class="review-card__rating" aria-label="Рейтинг 5 из 5">
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                    <i class="fa-solid fa-star"></i>
                                </div>
                                <div class="review-card__header">
                                    <div class="review-card__avatar {{ $photoUrl ? '' : 'review-card__avatar--placeholder' }}">
                                        @if($photoUrl)
                                            <img src="{{ $photoUrl }}" alt="Аватар автора отзыва">
                                        @else
                                            <span>{{ mb_substr($name, 0, 1) }}</span>
                                        @endif
                                    </div>
                                    <div class="review-card__meta">
                                        <div class="review-card__name">{{ $name }}</div>
                                        @if($review->review_date)
                                            <div class="review-card__date">{{ $review->review_date->format('d.m.Y') }}</div>
                                        @endif
                                    </div>
                                </div>
                                <div class="review-card__body">
                                    <p>{{ $text }}</p>
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>
            </div>
            <button class="reviews-arrow reviews-arrow_next" type="button" aria-label="Следующий отзыв">
                <i class="fa fa-chevron-right"></i>
            </button>
        </div>
        <div class="reviews-dots" id="reviews-dots"></div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const carousel = document.getElementById('reviews-carousel');
    if (!carousel) return;

    const track = carousel.querySelector('.reviews-track');
    const slides = Array.from(carousel.querySelectorAll('.review-slide'));
    const prevBtn = carousel.querySelector('.reviews-arrow_prev');
    const nextBtn = carousel.querySelector('.reviews-arrow_next');
    const viewport = carousel.querySelector('.reviews-viewport') || carousel;
    const dotsContainer = document.getElementById('reviews-dots');

    if (!slides.length) {
        carousel.style.display = 'none';
        if (dotsContainer) dotsContainer.style.display = 'none';
        return;
    }

    const getVisible = () => window.innerWidth <= 768 ? 1 : 3;
    let visible = getVisible();
    let index = 0;
    let slideWidth = slides[0] ? slides[0].getBoundingClientRect().width : 0;

    const recalcSlideWidth = () => {
        if (!slides.length) return;
        slideWidth = slides[0].getBoundingClientRect().width;
    };

    const syncDots = () => {
        if (!dotsContainer) return;
        const dots = dotsContainer.querySelectorAll('button');
        const page = Math.floor(index / visible);
        dots.forEach((dot, idx) => {
            dot.classList.toggle('active', idx === page);
        });
    };

    const updateControlsVisibility = () => {
        const enoughSlides = slides.length > visible;
        if (prevBtn) prevBtn.style.display = enoughSlides ? 'inline-flex' : 'none';
        if (nextBtn) nextBtn.style.display = enoughSlides ? 'inline-flex' : 'none';
        if (dotsContainer) dotsContainer.style.display = enoughSlides ? 'flex' : 'none';
    };

    const goTo = (i) => {
        visible = getVisible();
        const lastIndex = Math.max(0, slides.length - visible);
        index = Math.max(0, Math.min(lastIndex, i));
        recalcSlideWidth();
        const shift = slideWidth * index;
        track.style.transform = `translateX(-${shift}px)`;

        updateControlsVisibility();
        syncDots();
    };

    const next = () => {
        visible = getVisible();
        const lastIndex = Math.max(0, slides.length - visible);
        if (index >= lastIndex) {
            goTo(0);
        } else {
            goTo(index + 1);
        }
    };

    const prev = () => {
        visible = getVisible();
        const lastIndex = Math.max(0, slides.length - visible);
        if (index <= 0) {
            goTo(lastIndex);
        } else {
            goTo(index - 1);
        }
    };

    const rebuildDots = () => {
        if (!dotsContainer) return;
        dotsContainer.innerHTML = '';
        visible = getVisible();
        const pages = Math.max(1, Math.ceil(slides.length / visible));
        if (pages <= 1) {
            dotsContainer.style.display = 'none';
            return;
        }
        for (let p = 0; p < pages; p++) {
            const dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'reviews-dot';
            dot.addEventListener('click', () => goTo(p * visible));
            dotsContainer.appendChild(dot);
        }
        syncDots();
    };

    if (prevBtn) prevBtn.addEventListener('click', prev);
    if (nextBtn) nextBtn.addEventListener('click', next);

    window.addEventListener('resize', () => {
        const oldVisible = visible;
        visible = getVisible();
        if (oldVisible !== visible) {
            rebuildDots();
            goTo(index);
        } else {
            goTo(index);
        }
    });

    rebuildDots();
    goTo(0);

    // Свайп пальцем на мобильных
    let touchStartX = 0;
    let touchStartY = 0;
    let touchActive = false;

    const touchStart = (e) => {
        if (!e.touches || e.touches.length !== 1) return;
        const t = e.touches[0];
        touchStartX = t.clientX;
        touchStartY = t.clientY;
        touchActive = true;
    };

    const touchEnd = (e) => {
        if (!touchActive) return;
        touchActive = false;
        if (!e.changedTouches || !e.changedTouches.length) return;
        const t = e.changedTouches[0];
        const dx = t.clientX - touchStartX;
        const dy = t.clientY - touchStartY;
        if (Math.abs(dx) < 40 || Math.abs(dx) < Math.abs(dy)) return;
        if (dx < 0) {
            next();
        } else {
            prev();
        }
    };

    if (viewport && 'ontouchstart' in window) {
        viewport.addEventListener('touchstart', touchStart, {passive: true});
        viewport.addEventListener('touchend', touchEnd, {passive: true});
        viewport.addEventListener('touchcancel', () => { touchActive = false; }, {passive: true});
    }

    // Читать весь / свернуть
    const cards = carousel.querySelectorAll('.review-card');
    cards.forEach(card => {
        const body = card.querySelector('.review-card__body');
        const p = body ? body.querySelector('p') : null;
        if (!body || !p) return;

        // измеряем высоту с ограничением и без, чтобы понять, обрезается ли текст
        const clampedHeight = p.getBoundingClientRect().height;
        body.classList.add('expanded');
        const fullHeight = p.getBoundingClientRect().height;
        body.classList.remove('expanded');

        if (fullHeight <= clampedHeight + 1) {
            // текст и так помещается, кнопку не показываем
            return;
        }

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'review-read-more';
        btn.textContent = 'Читать весь';
        btn.addEventListener('click', () => {
            const expanded = body.classList.toggle('expanded');
            btn.textContent = expanded ? 'Свернуть' : 'Читать весь';
        });
        body.appendChild(btn);
    });
});
</script>
@endif
