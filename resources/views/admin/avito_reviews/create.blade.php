@extends('admin.index')

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Новый отзыв Avito</h1>
    <form action="{{ route('admin.avito-reviews.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="name" class="form-label">Имя</label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="review_date" class="form-label">Дата отзыва</label>
                    <input type="datetime-local" class="form-control" id="review_date" name="review_date"
                           value="{{ old('review_date') }}">
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label for="text" class="form-label">Текст отзыва</label>
            <textarea class="form-control" id="text" name="text" rows="6">{{ old('text') }}</textarea>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="status" class="form-label">Статус</label>
                    <select class="form-select" id="status" name="status">
                        @php $currentStatus = old('status', 'published'); @endphp
                        <option value="new" {{ $currentStatus === 'new' ? 'selected' : '' }}>Новое</option>
                        <option value="published" {{ $currentStatus === 'published' ? 'selected' : '' }}>Опубликовано</option>
                        <option value="hidden" {{ $currentStatus === 'hidden' ? 'selected' : '' }}>Скрыто</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="photos_raw" class="form-label">Фото (URL, по одному в строке)</label>
                    <textarea class="form-control" id="photos_raw" name="photos_raw" rows="4">{{ old('photos_raw') }}</textarea>
                    <small class="text-muted">Можно указать внешние ссылки или локальные пути из хранилища.</small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Загрузить фото на сервер</label>
                    <input type="file" name="photos_upload[]" class="form-control" multiple accept="image/*">
                    <small class="text-muted">Загруженные файлы попадут в список фото отзыва.</small>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between mt-3">
            <button type="submit" class="btn btn-primary">Создать</button>
        </div>
    </form>
</div>
@endsection
