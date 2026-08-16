@extends('admin.index')

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Редактировать отзыв Avito</h1>
    <form action="{{ route('admin.avito-reviews.update', $review->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="name" class="form-label">Имя</label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $review->name) }}">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="review_date" class="form-label">Дата отзыва</label>
                    <input type="datetime-local" class="form-control" id="review_date" name="review_date"
                           value="{{ old('review_date', $review->review_date ? $review->review_date->format('Y-m-d\TH:i') : '') }}">
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label for="text" class="form-label">Текст отзыва</label>
            <textarea class="form-control" id="text" name="text" rows="6">{{ old('text', $review->text) }}</textarea>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="status" class="form-label">Статус</label>
                    <select class="form-select" id="status" name="status">
                        @php $currentStatus = old('status', $review->status); @endphp
                        <option value="new" {{ $currentStatus === 'new' ? 'selected' : '' }}>Новое</option>
                        <option value="published" {{ $currentStatus === 'published' ? 'selected' : '' }}>Опубликовано</option>
                        <option value="hidden" {{ $currentStatus === 'hidden' ? 'selected' : '' }}>Скрыто</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="photos_raw" class="form-label">Фото (URL, по одному в строке)</label>
                    <textarea class="form-control" id="photos_raw" name="photos_raw" rows="4">@php
                        $photos = is_array($review->photos) ? $review->photos : [];
                        echo old('photos_raw', implode("\n", $photos));
                    @endphp</textarea>
                    <small class="text-muted">Здесь можно указать ссылки на изображения (в том числе локальные пути из хранилища).</small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Загрузить фото на сервер</label>
                    <input type="file" name="photos_upload[]" class="form-control" multiple accept="image/*">
                    <small class="text-muted">Новые изображения добавятся к списку выше.</small>
                </div>
            </div>
            <div class="col-md-6">
                @php $photos = is_array($review->photos) ? $review->photos : []; @endphp
                @if(count($photos))
                    <div class="mb-3">
                        <label class="form-label">Текущие фото</label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($photos as $photo)
                                @php
                                    $src = preg_match('#^https?://#', $photo) ? $photo : asset('storage/' . ltrim($photo, '/'));
                                @endphp
                                <div style="width:70px;height:70px;border-radius:8px;overflow:hidden;border:1px solid #ddd;">
                                    <img src="{{ $src }}" alt="" style="width:100%;height:100%;object-fit:cover;">
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="d-flex justify-content-between mt-3">
            <button type="submit" class="btn btn-primary">Сохранить</button>
        </div>
    </form>
</div>
@endsection
