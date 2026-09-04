@props(['label' => 'Действия'])

<div class="admin-actions-menu" data-admin-actions-menu>
    <button
        class="admin-actions-menu__toggle"
        type="button"
        aria-label="{{ $label }}"
        aria-haspopup="menu"
        aria-expanded="false"
    >
        <i class="fa fa-ellipsis-vertical" aria-hidden="true"></i>
    </button>
    <div class="admin-actions-menu__popup" role="menu" aria-label="{{ $label }}" hidden>
        {{ $slot }}
    </div>
</div>
