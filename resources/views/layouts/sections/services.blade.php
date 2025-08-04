<section id="services">
    <div class="services-container container">
        @if($services->where('active', true)->count() > 0)
            <div class="services-header">
                <h2>Услуги</h2>
                <div class="foot"></div>
            </div>
            <div class="services-content">
                @foreach($services->where('active', true) as $service)
                    <div class="services-content__item">
                    <div class="services-content__item-title">
                        <h3>{{ $service->title }}</h3>
                    </div>
                    <div class="services-content__item-img">
                        <img src="{{ asset('storage/' . $service->image) }}" alt="{{ $service->title }}">
                    </div>
                    <div class="services-content__item-description">
                        {!! $service->text !!}
                    </div>
                    </div>
                @endforeach
            </div>
        @else
        <div class="services-content__empty-message">
            <h2 style="text-align: center; padding: 20px;">На данный момент услуги отсутствуют.</h2>
        </div>
        @endif
    </div>
</section>