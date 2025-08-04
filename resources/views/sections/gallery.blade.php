<section id="gallery">
    <div class="gallery-container container">
        @if($galleries->count() > 0)
            <div class="gallery-header">
                <h2>Фотоальбом</h2>
                <div class="foot"></div>
            </div>
            <div class="gallery-content">
                @foreach($galleries->sortBy('number') as $gallery)
                    <div class="gallery-content__item">
                        <img src="{{ asset('storage/' . $gallery->image) }}" alt="Фото {{ $gallery->number }}">
                    </div>
                @endforeach
            </div>
        @else
            <div class="gallery-content__empty-message">
                <h2 style="text-align: center; padding: 20px;">На данный момент фотоальбом отсутствует.</h2>
            </div>
        @endif
    </div>
</section>
