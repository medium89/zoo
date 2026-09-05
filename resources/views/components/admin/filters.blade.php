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
            const closeFilterSelects = (except = null) => document.querySelectorAll('.admin-filter-select.is-open').forEach((select) => {
                if (select !== except) {
                    select.classList.remove('is-open');
                    select.querySelector('.admin-filter-select__toggle')?.setAttribute('aria-expanded', 'false');
                    select.querySelector('.admin-filter-select__menu')?.classList.add('is-hidden');
                }
            });

            document.querySelectorAll('.admin-filter-bar .admin-filter-bar__field select').forEach((native) => {
                if (native.closest('.admin-filter-select')) return;
                const wrapper = document.createElement('div');
                wrapper.className = 'admin-filter-select';
                const toggle = document.createElement('button');
                toggle.type = 'button';
                toggle.className = 'admin-filter-select__toggle';
                toggle.setAttribute('aria-haspopup', 'listbox');
                toggle.setAttribute('aria-expanded', 'false');
                const value = document.createElement('span');
                const icon = document.createElement('i');
                icon.className = 'fa fa-chevron-down';
                icon.setAttribute('aria-hidden', 'true');
                toggle.append(value, icon);
                const menu = document.createElement('div');
                menu.className = 'admin-filter-select__menu is-hidden';
                menu.setAttribute('role', 'listbox');
                menu.setAttribute('aria-label', native.name || 'Фильтр');
                Array.from(native.options).forEach((option) => {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'admin-filter-select__option';
                    item.dataset.value = option.value;
                    item.textContent = option.textContent;
                    item.setAttribute('role', 'option');
                    item.setAttribute('aria-selected', String(option.selected));
                    item.addEventListener('click', () => {
                        native.value = option.value;
                        native.dispatchEvent(new Event('change', { bubbles: true }));
                        closeFilterSelects();
                    });
                    menu.append(item);
                });
                const sync = () => {
                    const selected = native.options[native.selectedIndex];
                    value.textContent = selected?.textContent || '';
                    menu.querySelectorAll('.admin-filter-select__option').forEach((item) => item.setAttribute('aria-selected', String(item.dataset.value === native.value)));
                };
                native.classList.add('admin-filter-select__native');
                native.parentNode.insertBefore(wrapper, native);
                wrapper.append(native, toggle, menu);
                toggle.addEventListener('click', (event) => {
                    event.stopPropagation();
                    const opening = !wrapper.classList.contains('is-open');
                    closeFilterSelects(wrapper);
                    wrapper.classList.toggle('is-open', opening);
                    toggle.setAttribute('aria-expanded', String(opening));
                    menu.classList.toggle('is-hidden', !opening);
                    if (opening) {
                        const selected = menu.querySelector('[aria-selected="true"]') || menu.querySelector('.admin-filter-select__option');
                        selected?.focus();
                    }
                });
                toggle.addEventListener('keydown', (event) => {
                    if (!['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key)) return;
                    event.preventDefault();
                    if (!wrapper.classList.contains('is-open')) toggle.click();
                });
                const options = () => Array.from(menu.querySelectorAll('.admin-filter-select__option'));
                menu.addEventListener('keydown', (event) => {
                    const items = options();
                    const current = document.activeElement;
                    const index = items.indexOf(current);
                    if (event.key === 'Escape') {
                        event.preventDefault();
                        closeFilterSelects();
                        toggle.focus();
                        return;
                    }
                    if (event.key === 'Tab') {
                        closeFilterSelects();
                        return;
                    }
                    if (['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) {
                        event.preventDefault();
                        const next = event.key === 'Home' ? 0 : event.key === 'End' ? items.length - 1 : (index + (event.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length;
                        items[next]?.focus();
                        return;
                    }
                    if ((event.key === 'Enter' || event.key === ' ') && index >= 0) {
                        event.preventDefault();
                        items[index].click();
                    }
                });
                native.addEventListener('change', sync);
                sync();
            });
            document.addEventListener('click', () => closeFilterSelects());
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') closeFilterSelects();
            });

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
