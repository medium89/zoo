<section id="about">
    <div class="about-container container">
        @if($about->count() < 2)    
            <div class="about-header">
                <h2>Обо мне</h2>
                <div class="foot"></div>
            </div>
            <div class="about-content">
                <div class="about-content__item">
                    <div class="about-content__item-img">
                        @if($about && $about->image)
                            <img src="{{ asset('storage/' . $about->image) }}" alt="Это я">
                        @endif
                    </div>
                </div>
                <div class="about-content__item">
                    <div class="about-content__item-text">
                        @if($about)
                            {!! $about->text !!}
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div class="about-content" style="grid-template-columns: 1fr;">
                <h2 style="text-align: center; padding: 20px;">На данный момент информация об обо мне отсутствует.</h2>
            </div>
        @endif
    </div>
</section>