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
        <div class="reviews-subtitle">
            Реальные впечатления клиентов с Avito
        </div>

        <div class="reviews-carousel" id="reviews-carousel">
            <button class="reviews-arrow reviews-arrow_prev" type="button" aria-label="Предыдущий отзыв">
                <i class="fa fa-chevron-left"></i>
            </button>
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
                                    <div class="review-card__badge">
                                        <i class="fa fa-star" aria-hidden="true"></i> Avito
                                    </div>
                                </div>
                            </div>
                            <div class="review-card__body">
                                <p>{{ $excerpt }}</p>
                            </div>
                        </article>
                    </div>
                @endforeach
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

    let index = 0;
    const lastIndex = slides.length - 1;

    const goTo = (i) => {
        index = Math.max(0, Math.min(lastIndex, i));
        track.style.transform = `translateX(-${index * 100}%)`;
        if (dotsContainer) {
            dotsContainer.querySelectorAll('button').forEach((dot, idx) => {
                dot.classList.toggle('active', idx === index);
            });
        }
        if (prevBtn) prevBtn.disabled = index === 0;
        if (nextBtn) nextBtn.disabled = index === lastIndex;
    };

    if (dotsContainer && slides.length > 1) {
        slides.forEach((_, i) => {
            const dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'reviews-dot';
            dot.addEventListener('click', () => goTo(i));
            dotsContainer.appendChild(dot);
        });
    }

    if (prevBtn) prevBtn.addEventListener('click', () => goTo(index - 1));
    if (nextBtn) nextBtn.addEventListener('click', () => goTo(index + 1));

    if (slides.length <= 1) {
        if (prevBtn) prevBtn.style.display = 'none';
        if (nextBtn) nextBtn.style.display = 'none';
        if (dotsContainer) dotsContainer.style.display = 'none';
    }

    goTo(0);
});
</script>
@endif
