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
                            $rawPhoto = $photos[0] ?? null;
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
                                <div class="review-card__header">
                                    <div class="review-card__avatar {{ $photoUrl ? '' : 'review-card__avatar--placeholder' }}">
                                        @if($photoUrl)
                                            <img src="{{ $photoUrl }}" alt="Фото питомца или клиента">
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

        if (prevBtn) prevBtn.disabled = index === 0;
        if (nextBtn) nextBtn.disabled = index === lastIndex;
        updateControlsVisibility();
        syncDots();
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

    if (prevBtn) prevBtn.addEventListener('click', () => goTo(index - 1));
    if (nextBtn) nextBtn.addEventListener('click', () => goTo(index + 1));

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

    // Читать весь / свернуть
    const cards = carousel.querySelectorAll('.review-card');
    cards.forEach(card => {
        const body = card.querySelector('.review-card__body');
        const p = body ? body.querySelector('p') : null;
        if (!body || !p) return;
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
