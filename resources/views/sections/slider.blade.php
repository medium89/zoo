<section id="slider">
    <div class="slider-container">
        @if($sliders->count() > 0)
            <div class="slider-content">
                @foreach($sliders as $slider)
                    <div class="slider-content__item">
                        <img src="{{ asset('storage/' . $slider->image) }}" alt="Слайд">
                    </div>
                @endforeach
            </div>
        @else
        <div class="slider-content__empty-message">
            <h2 style="text-align: center; padding: 20px;">На данный момент слайды отсутствуют.</h2>
        </div>
        @endif
    </div>
</section>