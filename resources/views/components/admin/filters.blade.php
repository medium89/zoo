@props([
    'action',
    'filters' => [],
    'placeholder' => 'Поиск',
])

<form method="GET" action="{{ $action }}" class="admin-filter-bar" role="search">
    <label class="admin-filter-bar__search">
        <i class="fa fa-magnifying-glass" aria-hidden="true"></i>
        <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ $placeholder }}" autocomplete="off">
    </label>

    {{ $slot }}

    <button type="submit" class="btn btn-primary admin-filter-bar__apply">Применить</button>
    @if(collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty())
        <a href="{{ $action }}" class="btn btn-light admin-filter-bar__reset"><i class="fa fa-arrow-rotate-left" aria-hidden="true"></i><span>Сбросить</span></a>
    @endif
</form>
