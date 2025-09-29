@foreach($items as $gallery)
    @php
        $thumb = preg_replace('/^galleries\//', 'galleries/thumbs/', $gallery->image);
        $thumbExists = \Illuminate\Support\Facades\Storage::disk('public')->exists($thumb);
        $thumbUrl = $thumbExists ? asset('storage/' . $thumb) : asset('storage/' . $gallery->image);
        $fullUrl = asset('storage/' . $gallery->image);
    @endphp
    <div class="gallery-content__item">
        <img src="{{ $thumbUrl }}" data-full="{{ $fullUrl }}" alt="Фото {{ $gallery->number }}" class="js-gallery-thumb">
    </div>
@endforeach

