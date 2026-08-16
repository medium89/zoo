@php($editorTags = old('tags', $tags ?? []))
<div class="tag-editor" data-tag-editor data-tag-index="{{ count($editorTags) }}">
    <label class="form-label">Теги</label>
    <div class="tag-editor__controls">
        <input type="text" class="form-control" data-tag-input maxlength="60" placeholder="Например, кусается — нажмите Enter">
    </div>
    <div class="tag-editor__list" data-tag-list>
        @foreach($editorTags as $index => $tag)
            @if(!empty($tag['name']))
                @php($type = ($tag['type'] ?? '') === 'positive' ? 'positive' : 'negative')
                <span class="tag-editor__item entity-tag entity-tag--{{ $type }}" data-tag-item title="Нажмите, чтобы изменить тип или удалить">
                    <button type="button" class="tag-editor__label" data-tag-toggle>{{ $tag['name'] }}</button>
                    <span class="tag-editor__actions" data-tag-actions hidden>
                        <button type="button" class="tag-editor__action" data-set-tag-type="positive">Хороший</button>
                        <button type="button" class="tag-editor__action" data-set-tag-type="negative">Проблемный</button>
                        <button type="button" class="tag-editor__action tag-editor__action--remove" data-remove-tag>Удалить</button>
                    </span>
                    <input type="hidden" name="tags[{{ $index }}][name]" value="{{ $tag['name'] }}">
                    <input type="hidden" name="tags[{{ $index }}][type]" value="{{ $type }}">
                </span>
            @endif
        @endforeach
    </div>
    <div class="form-text">Введите тег и нажмите Enter. ИИ определит его тип; по нажатию на плашку можно исправить результат.</div>
</div>
