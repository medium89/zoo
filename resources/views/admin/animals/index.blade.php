@extends('admin.index')

@section('content')
<section class="animals-workspace">
    <div class="admin-list-page__actionbar">
        <h1 class="visually-hidden">Питомцы</h1>
        <a href="{{ route('admin.animals.create') }}" class="btn btn-primary animals-create"><i class="fa fa-plus" aria-hidden="true"></i><span>Новый питомец</span></a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <x-admin.filters :action="route('admin.animals.index')" :filters="$filters" placeholder="Кличка или хозяин" :auto="true">
        <label class="admin-filter-bar__field">Вид<select name="category_id" class="form-select"><option value="">Все виды</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>@endforeach</select></label>
        <label class="admin-filter-bar__field">Хозяин<select name="owner" class="form-select"><option value="">Все</option><option value="with" @selected(($filters['owner'] ?? '') === 'with')>Указан</option><option value="without" @selected(($filters['owner'] ?? '') === 'without')>Не указан</option></select></label>
    </x-admin.filters>

    @if($animals->count())
        <div class="admin-entity-list" style="--entity-cols: 54px minmax(170px,1.15fr) minmax(130px,1fr) minmax(140px,1fr) 90px 150px; --entity-cols-mobile: 54px minmax(0,1fr) 120px;">
            <div class="admin-entity-list__head">
                <div></div>
                <div>Кличка</div>
                <div>Категория</div>
                <div>Хозяин</div>
                <div>Записи</div>
                <div class="text-end">Действия</div>
            </div>
            <div class="admin-entity-list__body">
                @foreach($animals as $animal)
                    <div class="admin-entity-list__row">
                        <div><img src="{{ $animal->photos->first()?->path ? Storage::url($animal->photos->first()->path) : asset('images/animal-types/other.png') }}" alt="{{ $animal->name }}" class="admin-entity-list__avatar"></div>
                        <div class="admin-entity-list__primary" data-label="Питомец">
                            <a href="{{ route('admin.animals.show', $animal) }}">{{ $animal->name }}</a>
                            @include('admin.partials.tags-list', ['tags' => $animal->tags])
                        </div>
                        <div class="admin-entity-list__muted" data-label="Вид">{{ $animal->category?->name ?: '—' }}</div>
                        <div class="admin-entity-list__muted" data-label="Хозяин">{{ $animal->client?->name ?: '—' }}</div>
                        <div data-label="Записи">{{ $animal->boardings_count ?: 'Нет' }}</div>
                        <div class="admin-entity-list__actions actions" data-label="Действия">
                            <x-admin.actions-menu label="Действия с питомцем {{ $animal->name }}">
                                <a href="{{ route('admin.animals.show', $animal) }}" class="admin-actions-menu__item"><i class="fa fa-eye" aria-hidden="true"></i><span>Просмотреть</span></a>
                                <a href="{{ route('admin.animals.edit', $animal) }}" class="admin-actions-menu__item"><i class="fa fa-pen" aria-hidden="true"></i><span>Редактировать</span></a>
                                <form action="{{ route('admin.animals.destroy', $animal) }}" method="POST" class="js-delete-form" data-confirm="Удалить питомца?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="admin-actions-menu__item admin-actions-menu__item--danger"><i class="fa fa-trash" aria-hidden="true"></i><span>Удалить</span></button>
                                </form>
                            </x-admin.actions-menu>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <footer class="admin-entity-list__footer">
            <span>Показано {{ $animals->firstItem() }}–{{ $animals->lastItem() }} из {{ $animals->total() }} {{ trans_choice('питомца|питомцев|питомцев', $animals->total()) }}</span>
            <form method="GET" action="{{ route('admin.animals.index') }}">
                @foreach(request()->except(['per_page', 'page']) as $key => $value)
                    @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                @endforeach
                <label for="animalsPerPage">На странице</label>
                <select id="animalsPerPage" name="per_page" class="form-select" onchange="this.form.submit()">
                    @foreach([10, 25, 50, 100] as $option)<option value="{{ $option }}" @selected((int) request('per_page', 25) === $option)>{{ $option }}</option>@endforeach
                </select>
            </form>
            <div class="admin-entity-list__footer-pagination">{{ $animals->onEachSide(1)->links('pagination::bootstrap-4') }}</div>
        </footer>
    @elseif(collect($filters)->except(['per_page', 'page'])->filter(fn ($value) => filled($value))->isNotEmpty())
        <section class="admin-entity-list__empty"><i class="fa fa-magnifying-glass mb-2" aria-hidden="true"></i><h2 class="h5">Ничего не нашли</h2><p class="mb-3">Попробуйте изменить фильтры или поисковый запрос.</p><a href="{{ route('admin.animals.index') }}" class="btn btn-outline-primary">Сбросить фильтры</a></section>
    @else
        <section class="admin-entity-list__empty"><i class="fa fa-paw mb-2" aria-hidden="true"></i><h2 class="h5">Питомцев пока нет</h2><p>Добавьте первого питомца.</p><a href="{{ route('admin.animals.create') }}" class="btn btn-primary"><i class="fa fa-plus" aria-hidden="true"></i> Новый питомец</a></section>
    @endif
</section>
@endsection

@push('styles')
<style>
/* Matches the primary creation action on the orders workspace. */
.animals-workspace .animals-create {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 40px;
    padding: 8px 13px;
    border-radius: 10px;
    font-family: inherit;
    font-size: .86rem;
    font-weight: 700;
    line-height: 1.5;
}

@media (max-width: 767px) {
    .animals-workspace .animals-create { font-size: .8rem; }
}

@media (max-width: 390px) {
    .animals-workspace .animals-create { font-size: .71rem; }
}
</style>
@endpush
