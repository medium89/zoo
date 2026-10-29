<div class="ve-toolbar" id="visualEditor" data-field-url="{{ route('admin.visual.field') }}" data-save-url="{{ route('admin.visual.field.save') }}" data-reorder-url="{{ route('admin.visual.reorder') }}" data-image-url="{{ route('admin.visual.image.save') }}" data-nav-create-url="{{ route('admin.visual.nav-link.create') }}" data-start-active="{{ request()->boolean('edit') ? '1' : '0' }}">
    <button type="button" class="ve-toggle" id="veToggle" role="switch" aria-checked="false"><span class="ve-toggle__dot"></span> Редактировать страницу</button>
    <span class="ve-toolbar__hint" id="veHint">Включите режим, чтобы править страницу</span>
    <button type="button" id="veMenuButton" class="ve-toolbar__secondary" hidden>Меню</button>
    <button type="button" id="veSlidesButton" class="ve-toolbar__secondary" hidden>Слайды</button>
    <button type="button" id="veSave" class="ve-toolbar__save" hidden>Сохранить расположение</button>
    <button type="button" id="veCancel" class="ve-toolbar__secondary" hidden>Отменить</button>
    <a href="{{ route('admin.logs.index') }}" class="ve-toolbar__logs">Логи</a>
</div>
<div class="ve-slides" id="veSlides" hidden>
    <h2>Порядок слайдов <a class="ve-add" href="{{ route('admin.sliders.create') }}" aria-label="Добавить слайд" title="Добавить слайд">+</a></h2>
    <p>Перетащите слайды за ручку. Затем нажмите «Сохранить расположение».</p>
    <div data-ve-group="sliders" class="ve-slides__list">
        @foreach($sliders as $slide)
            <div class="ve-slides__item" data-ve-item="sliders" data-ve-id="{{ $slide->id }}" data-ve-image-type="slider" data-ve-image-id="{{ $slide->id }}" data-ve-image-field="image">
                @if($slide->image)<img src="{{ asset('storage/' . $slide->image) }}" alt="">@endif
                <span data-ve-type="slider" data-ve-id="{{ $slide->id }}" data-ve-field="text">{{ trim(strip_tags((string) $slide->text)) ?: 'Слайд ' . $slide->id }}</span>
            </div>
        @endforeach
    </div>
</div>
<dialog class="ve-modal ve-popover" id="veModal" aria-labelledby="veModalTitle">
    <div class="ve-modal__head"><h2 id="veModalTitle">Редактировать текст</h2><button type="button" id="veClose" aria-label="Закрыть"><span aria-hidden="true">×</span></button></div>
    <div class="ve-modal__body" id="veModalBody"></div>
    <p class="ve-modal__status" id="veModalStatus" role="status"></p>
    <div class="ve-modal__actions"><button type="button" id="veFieldCancel">Отменить</button><button type="button" id="veFieldSave">Сохранить</button></div>
</dialog>


<div class="ve-dock" id="veDock" hidden role="region" aria-label="Несохранённые изменения">
    <span id="veDockStatus">Есть несохранённые изменения</span>
    <button type="button" id="veDockUndo" class="ve-dock__undo">Отменить последнее</button>
    <button type="button" id="veDockSave" class="ve-dock__save">Сохранить</button>
</div>
<dialog class="ve-modal ve-popover" id="veImageModal" aria-labelledby="veImageTitle">
    <div class="ve-modal__head"><h2 id="veImageTitle">Заменить изображение</h2><button type="button" data-ve-image-close aria-label="Закрыть"><span aria-hidden="true">×</span></button></div>
    <label class="ve-image-drop" id="veImageDrop">Перетащите изображение сюда или выберите файл
        <input type="file" id="veImageFile" accept="image/jpeg,image/png,image/gif,image/webp,image/avif,image/bmp,image/svg+xml">
    </label>
    <img class="ve-image-preview" id="veImagePreview" alt="Предпросмотр изображения" hidden>
    <p class="ve-modal__status" id="veImageStatus" role="status"></p>
    <div class="ve-modal__actions"><button type="button" data-ve-image-close>Отменить</button><button type="button" id="veImageApply">Сохранить</button></div>
</dialog>
<dialog class="ve-modal" id="veNavModal" aria-labelledby="veNavTitle">
    <div class="ve-modal__head"><h2 id="veNavTitle">Добавить пункт меню</h2><button type="button" data-ve-nav-close aria-label="Закрыть">×</button></div>
    <label class="ve-modal__label">Название<input class="ve-modal__input" id="veNavLabel" maxlength="80"></label>
    <label class="ve-modal__label">Адрес ссылки<input class="ve-modal__input" id="veNavHref" placeholder="#v2-about, /articles или https://..."></label>
    <p class="ve-modal__status" id="veNavStatus" role="status"></p>
    <div class="ve-modal__actions"><button type="button" data-ve-nav-close>Отменить</button><button type="button" id="veNavCreate">Добавить</button></div>
</dialog>
