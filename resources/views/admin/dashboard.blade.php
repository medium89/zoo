@extends('admin.index')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4">Настройки сайта</h1>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <form action="{{ route('admin.settings.site') }}" method="POST">
        @csrf
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="site_closed" id="site_closed" {{ $siteClosed ? 'checked' : '' }}>
            <label class="form-check-label" for="site_closed">Закрыть сайт</label>
        </div>
        <button type="submit" class="btn btn-primary">Сохранить</button>
    </form>
</div>
@endsection
