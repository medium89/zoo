@extends('admin.index')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Меню сайта</h1>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('admin.nav-links.status') }}" method="POST">
        @csrf
        <div class="admin-grid" style="--grid-cols: 1fr 2fr 120px;">
            <div class="admin-grid-header">
                <div>Название</div>
                <div>Ссылка</div>
                <div>Включено</div>
            </div>
            <div class="admin-grid-body">
                @foreach ($links as $link)
                    <div class="admin-grid-row">
                        <div>{{ $link->label }}</div>
                        <div class="text-clip">{{ $link->href }}</div>
                        <div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" name="active[{{ $link->id }}]" {{ $link->active ? 'checked' : '' }}>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-3 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">Сохранить</button>
        </div>
    </form>
</div>
@endsection

