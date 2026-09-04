@props([
    'label',
    'href' => null,
    'target' => null,
    'icon' => 'fa-plus',
])

@if($href || $target)
    @if($href)
        <a {{ $attributes->class(['admin-fab']) }} href="{{ $href }}" data-tooltip="{{ $label }}" aria-label="{{ $label }}">
            <i class="fa {{ $icon }}" aria-hidden="true"></i>
        </a>
    @else
        <button {{ $attributes->class(['admin-fab']) }} type="button" data-fab-target="{{ $target }}" data-tooltip="{{ $label }}" aria-label="{{ $label }}">
            <i class="fa {{ $icon }}" aria-hidden="true"></i>
        </button>
    @endif
@endif
