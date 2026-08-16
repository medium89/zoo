@php($editorTags = old('tags', $tags ?? []))
<div class="tag-editor" data-tag-editor data-tag-index="{{ count($editorTags) }}">
    <label class="form-label">Теги</label>
    <div class="tag-editor__controls">
        <input type="text" class="form-control" data-tag-input maxlength="60" placeholder="Например, кусается — затем Enter">
        <div class="btn-group" role="group" aria-label="Тип тега">
            <button type="button" class="btn btn-sm btn-outline-success" data-tag-type="positive">Хороший</button>
            <button type="button" class="btn btn-sm btn-outline-danger active" data-tag-type="negative">Проблемный</button>
        </div>
    </div>
    <div class="tag-editor__list" data-tag-list>
        @foreach($editorTags as $index => $tag)
            @if(!empty($tag['name']))
                @php($type = ($tag['type'] ?? '') === 'positive' ? 'positive' : 'negative')
                <span class="tag-editor__item entity-tag entity-tag--{{ $type }}">
                    <span>{{ $tag['name'] }}</span>
                    <button type="button" class="tag-editor__remove" data-remove-tag aria-label="Удалить тег {{ $tag['name'] }}">×</button>
                    <input type="hidden" name="tags[{{ $index }}][name]" value="{{ $tag['name'] }}">
                    <input type="hidden" name="tags[{{ $index }}][type]" value="{{ $type }}">
                </span>
            @endif
        @endforeach
    </div>
    <div class="form-text">Выберите тип, введите тег и нажмите Enter.</div>
</div>
