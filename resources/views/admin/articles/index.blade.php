@extends('admin.index')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Статьи</h1>
        <a href="{{ route('admin.articles.create') }}" class="btn btn-primary">Добавить</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <x-admin.filters :action="route('admin.articles.index')" :filters="$filters" placeholder="Название статьи">
        <label class="admin-filter-bar__field">Категория<select name="category_id" class="form-select"><option value="">Все</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>@endforeach</select></label>
        <label class="admin-filter-bar__field">Публикация<select name="status" class="form-select"><option value="">Все</option><option value="active" @selected(($filters['status'] ?? '') === 'active')>Опубликованы</option><option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Черновики</option></select></label>
    </x-admin.filters>

    <form id="articles-form" action="{{ route('admin.articles.status') }}" method="POST">@csrf</form>
    <div class="admin-grid" style="--grid-cols: 100px 1.6fr 1.2fr 1.2fr 1fr 140px 180px;">
        <div class="admin-grid-header">
            <div>Порядок</div>
            <div>Заголовок</div>
            <div>Создана</div>
            <div>Публикация</div>
            <div>Категория</div>
            <div>Статус</div>
            <div class="text-end">Действия</div>
        </div>
        <div class="admin-grid-body js-sortable" id="articlesSort" data-custom-sort="1">
            @forelse($articles as $article)
                <div class="admin-grid-row" data-id="{{ $article->id }}">
                    <div class="js-order-label text-muted" style="cursor:grab;"><i class="fa fa-grip-vertical me-1"></i>{{ $loop->iteration }}</div>
                    <div class="text-clip">{{ $article->title }}</div>
                    <div>{{ $article->created_at->format('d.m.Y H:i') }}</div>
                    <div>{{ $article->published_at ? $article->published_at->format('d.m.Y H:i') : '—' }}</div>
                    <div>{{ $article->category?->name ?? '—' }}</div>
                    <div class="d-flex align-items-center">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input js-status-toggle" type="checkbox" data-id="{{ $article->id }}" {{ $article->active ? 'checked' : '' }}>
                        </div>
                        <input type="hidden" name="orders[{{ $article->id }}]" value="{{ $loop->iteration }}" class="js-order-input" form="articles-form">
                    </div>
                    <div class="actions">
                        <x-admin.actions-menu label="Действия со статьёй {{ $article->title }}">
                            <a href="{{ route('admin.articles.edit', $article) }}" class="admin-actions-menu__item"><i class="fa fa-pen" aria-hidden="true"></i><span>Редактировать</span></a>
                            <form action="{{ route('admin.articles.destroy', $article) }}" method="POST" class="js-delete-form" data-confirm="Удалить статью?">
                                @csrf @method('DELETE')
                                <button class="admin-actions-menu__item admin-actions-menu__item--danger" type="submit"><i class="fa fa-trash" aria-hidden="true"></i><span>Удалить</span></button>
                            </form>
                        </x-admin.actions-menu>
                    </div>
                </div>
            @empty
                <div class="text-muted">Статей нет.</div>
            @endforelse
        </div>
    </div>

    {{ $articles->links() }}
</div>
<script>
document.addEventListener('DOMContentLoaded', ()=>{
    const renumber = ()=>{
        document.querySelectorAll('#articlesSort .admin-grid-row').forEach((row, idx)=>{
            row.querySelector('.js-order-label').innerHTML = `<i class="fa fa-grip-vertical me-1"></i>${idx+1}`;
            const orderInput = row.querySelector('.js-order-input');
            if (orderInput) orderInput.value = idx+1;
        });
    };
    renumber();

    const form = document.getElementById('articles-form');
    document.querySelectorAll('#articlesSort .js-status-toggle').forEach(toggle=>{
        toggle.addEventListener('change', ()=>{
            form.querySelectorAll('input[name^="statuses["]').forEach(el=>el.remove());
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `statuses[${toggle.dataset.id}]`;
            input.value = toggle.checked ? 1 : 0;
            form.appendChild(input);
            form.submit();
        });
    });

    if (window.Sortable) {
        Sortable.create(document.getElementById('articlesSort'), {
            animation:150,
            handle: '.js-order-label',
            onEnd: ()=>{
                renumber();
                form.submit();
            }
        });
    }
});
</script>
@endsection
