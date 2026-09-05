@props([
    'action',
    'filters' => [],
    'placeholder' => 'Поиск',
    'auto' => false,
    'attached' => false,
])

@php($hasActiveFilters = collect($filters)->except(['per_page', 'page'])->filter(fn ($value) => filled($value))->isNotEmpty())

<form method="GET" action="{{ $action }}" class="admin-filter-bar @if($attached) admin-filter-bar--attached @endif" role="search" @if($auto) data-auto-filters @endif @if($attached) data-filter-layout="attached" @endif>
    @if(filled($filters['per_page'] ?? null))
        <input type="hidden" name="per_page" value="{{ $filters['per_page'] }}">
    @endif
    <label class="admin-filter-bar__search">
        <span class="admin-filter-bar__label">Поиск</span>
        <i class="fa fa-magnifying-glass" aria-hidden="true"></i>
        <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ $placeholder }}" autocomplete="off">
    </label>

    {{ $slot }}

    @unless($auto)
        <button type="submit" class="btn btn-primary admin-filter-bar__apply">Применить</button>
    @endunless
    @if($hasActiveFilters)
        <a href="{{ $action }}" class="btn btn-light admin-filter-bar__reset"><i class="fa fa-arrow-rotate-left" aria-hidden="true"></i><span>Сбросить</span></a>
    @endif
</form>

@if($auto)
    @once
        @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-auto-filters]').forEach((form) => {
                const search = form.querySelector('input[name="search"]');
                let timer;
                let composing = false;
                const submit = () => {
                    window.clearTimeout(timer);
                    form.requestSubmit();
                };

                form.querySelectorAll('select, input[type="date"], input[type="datetime-local"], input[type="month"], input[type="week"]').forEach((field) => {
                    field.addEventListener('change', submit);
                });

                if (!search) return;
                search.addEventListener('compositionstart', () => { composing = true; });
                search.addEventListener('compositionend', () => {
                    composing = false;
                    window.clearTimeout(timer);
                    timer = window.setTimeout(submit, 400);
                });
                search.addEventListener('input', () => {
                    if (composing) return;
                    window.clearTimeout(timer);
                    timer = window.setTimeout(submit, 400);
                });
                search.addEventListener('keydown', (event) => {
                    if (event.key !== 'Enter' || composing) return;
                    event.preventDefault();
                    submit();
                });
            });
        });
        </script>
        @endpush
    @endonce
@endif
