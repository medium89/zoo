<aside class="admin-live-preview" data-admin-live-preview="{{ $kind }}" @if(in_array($kind, ['service', 'advantage'])) data-preview-orders='@json($previewOrders ?? [])' data-preview-id="{{ $service->id ?? $advantage->id ?? '' }}" @endif aria-label="Предпросмотр на сайте">
    <div class="admin-live-preview__head">
        <strong>Предпросмотр на сайте</strong>
        <span>Обновляется при изменении полей</span>
    </div>
    <div class="admin-live-preview__frame">
        <div class="v2-page admin-live-preview__site" data-admin-live-preview-canvas></div>
    </div>
    <p class="admin-live-preview__hint">Предпросмотр показывает текущие данные формы. Посетители увидят изменения после сохранения.</p>
</aside>
