<section id="services">
    <div class="services-container container">
        @php
            $activeServices = $services->where('active', true);
            $totalServices = $activeServices->count();
            $remainder = $totalServices % 3;
            $lastRowShortCount = $remainder !== 0 ? $remainder : 0;
            $firstIndexLastRow = $lastRowShortCount ? ($totalServices - $lastRowShortCount + 1) : null;
        @endphp
        @if($totalServices > 0)
            <div class="services-header">
                <h2>Услуги</h2>
                <div class="foot"></div>
            </div>
            <div class="services-content">
                @foreach($activeServices as $service)
                    @php
                        $idx = $loop->iteration;
                        $isLastRowShort = $lastRowShortCount && $idx >= $firstIndexLastRow;
                    @endphp
                    <div class="services-content__item{{ $isLastRowShort ? ' services-content__item--wide' : '' }}">
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
