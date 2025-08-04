<section id="advantages">
    <div class="advantages-container container">
        @if($advantages->where('active', true)->count() > 0)
            <div class="advantages-header">
                <h2 class="white">Преимущества</h2>
                <div class="foot foot-white"></div>
            </div>
            <div class="advantages-content">
                @foreach($advantages->where('active', true) as $advantage)
                    <div class="advantages-content__item">
                        <div class="advantages-content__item-img">
                            <img src="{{ asset('storage/' . $advantage->image) }}" alt="{{ $advantage->title }}">
                        </div>
                        <div class="advantages-content__item-title">
                            {{ $advantage->title }}
                        </div>
                        <div class="advantages-content__item-text">
                            {!! $advantage->text !!}
                        </div>
                    </div>
                @endforeach
            </div>
        @else
        <div class="advantages-content__empty-message">
            <h2 style="text-align: center; padding: 20px; color: #fff;">На данный момент преимущества отсутствуют.</h2>
        </div>
        @endif
    </div>
</section>