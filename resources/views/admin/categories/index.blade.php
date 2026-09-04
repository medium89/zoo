@extends('admin.index')

@section('content')
<div class="container-fluid admin-list-page">
    <h1 class="visually-hidden">Категории животных</h1>
    <div class="admin-list-page__actionbar">
        <a href="{{ route('admin.categories.create') }}" class="btn btn-primary"><i class="fa fa-plus me-1" aria-hidden="true"></i>Добавить категорию</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('admin.categories.index') }}" class="admin-filter-bar" role="search">
        <label class="admin-filter-bar__search">
            <i class="fa fa-magnifying-glass" aria-hidden="true"></i>
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Название или slug" autocomplete="off">
        </label>
        <button type="submit" class="btn btn-primary admin-filter-bar__apply">Применить</button>
        @if(filled($filters['search'] ?? null))
            <a href="{{ route('admin.categories.index') }}" class="btn btn-light admin-filter-bar__reset"><i class="fa fa-arrow-rotate-left" aria-hidden="true"></i><span>Сбросить</span></a>
        @endif
    </form>

    @if($categories->count())
        <section class="admin-entity-list" aria-label="Список категорий" style="--entity-cols: minmax(190px,1.25fr) minmax(170px,1fr) 150px; --entity-cols-mobile: 1fr 120px;">
            <div class="admin-entity-list__head">
                <div>Категория</div><div>Slug</div><div class="text-end">Действия</div>
            </div>
            <div class="admin-entity-list__body">
                @foreach($categories as $category)
                    <div class="admin-entity-list__row">
                        <div class="admin-entity-list__primary" data-label="Категория"><strong>{{ $category->name }}</strong></div>
                        <div class="admin-entity-list__muted" data-label="Slug">{{ $category->slug ?: '—' }}</div>
                        <div class="admin-entity-list__actions" data-label="Действия">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-outline-secondary" aria-label="Редактировать {{ $category->name }}" title="Редактировать"><i class="fa fa-pen" aria-hidden="true"></i></a>
                                <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="d-inline js-delete-form" data-confirm="Удалить категорию?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" aria-label="Удалить {{ $category->name }}" title="Удалить"><i class="fa fa-trash" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
        <footer class="admin-entity-list__footer">
            <span>Показано {{ $categories->firstItem() }}–{{ $categories->lastItem() }} из {{ $categories->total() }} {{ trans_choice('категории|категорий|категорий', $categories->total()) }}</span>
            <form method="GET" action="{{ route('admin.categories.index') }}">
                @if(filled($filters['search'] ?? null))<input type="hidden" name="search" value="{{ $filters['search'] }}">@endif
                <label for="categoriesPerPage">На странице</label>
                <select id="categoriesPerPage" name="per_page" class="form-select" onchange="this.form.submit()">
                    @foreach([10, 25, 50, 100] as $option)<option value="{{ $option }}" @selected((int) request('per_page', 25) === $option)>{{ $option }}</option>@endforeach
                </select>
            </form>
            <div class="admin-entity-list__footer-pagination">{{ $categories->onEachSide(1)->links('pagination::bootstrap-4') }}</div>
        </footer>
    @elseif(filled($filters['search'] ?? null))
        <section class="admin-entity-list__empty"><i class="fa fa-magnifying-glass mb-2" aria-hidden="true"></i><h2 class="h5">Ничего не нашли</h2><p class="mb-3">Попробуйте изменить поисковый запрос.</p><a href="{{ route('admin.categories.index') }}" class="btn btn-outline-primary">Сбросить поиск</a></section>
    @else
        <section class="admin-entity-list__empty"><i class="fa fa-layer-group mb-2" aria-hidden="true"></i><h2 class="h5">Категорий пока нет</h2><p class="mb-0">Добавьте первый вид животного.</p></section>
    @endif
</div>
@endsection
