@extends('admin.index')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4">Настройки сайта</h1>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <form action="{{ route('admin.settings.site') }}" method="POST">
        @csrf
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="site_closed" id="site_closed" {{ ($settings->site_closed ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="site_closed">Закрыть сайт</label>
                </div>
                <div class="mb-3">
                    <label for="title" class="form-label">Meta Title</label>
                    <input type="text" class="form-control" id="title" name="title" value="{{ $settings->title ?? '' }}">
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Meta Description</label>
                    <input type="text" class="form-control" id="description" name="description" value="{{ $settings->description ?? '' }}">
                </div>
                <div class="mb-3">
                    <label for="robots" class="form-label">Meta Robots</label>
                    <input type="text" class="form-control" id="robots" name="robots" value="{{ $settings->robots ?? '' }}">
                </div>
                <div class="mb-3">
                    <label for="charset" class="form-label">Charset</label>
                    <input type="text" class="form-control" id="charset" name="charset" value="{{ $settings->charset ?? 'UTF-8' }}">
                </div>
            </div>
            <div class="col-lg-6">
                <div class="mb-3">
                    <label for="og_title" class="form-label">OG Title</label>
                    <input type="text" class="form-control" id="og_title" name="og_title" value="{{ $settings->og_title ?? '' }}">
                </div>
                <div class="mb-3">
                    <label for="og_description" class="form-label">OG Description</label>
                    <input type="text" class="form-control" id="og_description" name="og_description" value="{{ $settings->og_description ?? '' }}">
                </div>
                <div class="mb-3">
                    <label for="og_image" class="form-label">OG Image URL</label>
                    <input type="text" class="form-control" id="og_image" name="og_image" value="{{ $settings->og_image ?? '' }}">
                </div>
                <div class="mb-3">
                    <label for="og_url" class="form-label">OG URL</label>
                    <input type="text" class="form-control" id="og_url" name="og_url" value="{{ $settings->og_url ?? '' }}">
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Сохранить</button>
    </form>
</div>
@endsection
