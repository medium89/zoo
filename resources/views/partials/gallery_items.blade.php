@foreach($items as $gallery)
    @php
        $thumb = preg_replace('/^galleries\//', 'galleries/thumbs/', $gallery->image);
        $thumbExists = \Illuminate\Support\Facades\Storage::disk('public')->exists($thumb);
        $fullExists = \Illuminate\Support\Facades\Storage::disk('public')->exists($gallery->image);
        $placeholder = 'data:image/svg+xml;utf8,'.rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="800" height="600"><rect width="800" height="600" fill="%23f1f3f5"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="%236c757d" font-size="26" font-family="Arial, sans-serif">Нет изображения</text></svg>');
        $thumbUrl = $thumbExists ? asset('storage/' . $thumb) : ($fullExists ? asset('storage/' . $gallery->image) : $placeholder);
        $fullUrl = $fullExists ? asset('storage/' . $gallery->image) : $placeholder;
    @endphp
    <div class="gallery-content__item" data-id="{{ $gallery->id }}">
        <img src="{{ $thumbUrl }}" data-full="{{ $fullUrl }}" alt="Фото {{ $gallery->number }}" class="js-gallery-thumb">
    </div>
@endforeach
