@if(!empty($tags))
    <div class="entity-tags" aria-label="Теги">
        @foreach($tags as $tag)
            @if(!empty($tag['name']))
                <span class="entity-tag entity-tag--{{ ($tag['type'] ?? '') === 'positive' ? 'positive' : 'negative' }}">{{ $tag['name'] }}</span>
            @endif
        @endforeach
    </div>
@endif
