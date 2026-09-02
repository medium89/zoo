@extends('admin.index')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Отзывы Avito</h1>
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('admin.avito-reviews.create') }}" class="btn btn-success">
                <i class="fa fa-plus"></i> Новый отзыв
            </a>
            <form action="{{ route('admin.avito-reviews.refresh') }}" method="POST" class="mb-0">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-rotate"></i> Обновить с сайта
                </button>
            </form>
            <form action="{{ route('admin.avito-reviews.sort-by-date') }}" method="POST" class="mb-0">
                @csrf
                <button type="submit" class="btn btn-outline-primary">
                    <i class="fa fa-arrow-up-wide-short"></i> Сортировка по дате
                </button>
            </form>
            <form action="{{ route('admin.avito-reviews.import') }}" method="POST" enctype="multipart/form-data" class="mb-0 d-flex align-items-center gap-2">
                @csrf
                <input type="file" name="html_file" accept=".html,.htm,.txt" class="form-control form-control-sm" required>
                <button type="submit" class="btn btn-outline-secondary btn-sm">
                    Импорт из файла
                </button>
            </form>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <x-admin.filters :action="route('admin.avito-reviews.index')" :filters="$filters" placeholder="Автор или текст отзыва">
        <label class="admin-filter-bar__field">Статус<select name="status" class="form-select"><option value="">Все</option><option value="new" @selected(($filters['status'] ?? '') === 'new')>Новые</option><option value="published" @selected(($filters['status'] ?? '') === 'published')>Опубликованы</option><option value="hidden" @selected(($filters['status'] ?? '') === 'hidden')>Скрыты</option></select></label>
    </x-admin.filters>

    <div class="admin-grid" style="--grid-cols: 100px 1fr 140px 2fr 120px 120px 140px;">
        <div class="admin-grid-header">
            <div>Порядок</div>
            <div>Имя</div>
            <div>Дата</div>
            <div>Текст</div>
            <div>Фото</div>
            <div>Статус</div>
            <div class="text-end">Действия</div>
        </div>
        <form id="avito-reviews-form" action="{{ route('admin.avito-reviews.reorder') }}" method="POST">@csrf</form>
        <div class="admin-grid-body js-sortable" id="avitoReviewsSort" data-custom-sort="1">
            @forelse ($reviews as $review)
                <div class="admin-grid-row" data-id="{{ $review->id }}">
                    <div class="js-order-label text-muted" style="cursor:grab;">
                        <i class="fa fa-grip-vertical me-1"></i>{{ $loop->iteration }}
                    </div>
                    <div>{{ $review->name ?? 'Без имени' }}</div>
                    <div>{{ $review->review_date ? $review->review_date->format('d.m.Y') : 'Не указана' }}</div>
                    <div class="text-clip">{{ \Illuminate\Support\Str::limit($review->text, 180) }}</div>
                    <div>
                        @php $count = is_array($review->photos) ? count($review->photos) : 0; @endphp
                        {{ $count > 0 ? $count . ' шт.' : 'Нет' }}
                    </div>
                    <div>
                        <form action="{{ route('admin.avito-reviews.status', $review->id) }}" method="POST" class="mb-0">
                            @csrf
                            @php $currentStatus = $review->status; @endphp
                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="new" {{ $currentStatus === 'new' ? 'selected' : '' }}>Новое</option>
                                <option value="published" {{ $currentStatus === 'published' ? 'selected' : '' }}>Опубликовано</option>
                                <option value="hidden" {{ $currentStatus === 'hidden' ? 'selected' : '' }}>Скрыто</option>
                            </select>
                        </form>
                        <input type="hidden" name="orders[{{ $review->id }}]" value="{{ $loop->iteration }}" class="js-order-input" form="avito-reviews-form">
                    </div>
                    <div class="actions">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.avito-reviews.edit', $review->id) }}" class="btn btn-sm btn-primary text-white">
                                <i class="fa fa-pen"></i>
                            </a>
                            <form action="{{ route('admin.avito-reviews.destroy', $review->id) }}" method="POST" onsubmit="return confirm('Удалить отзыв?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="admin-grid-row">
                    <div class="text-muted" style="grid-column: 1 / -1;">
                        Отзывов пока нет. Нажмите «Обновить с сайта» или загрузите сохранённый HTML-файл страницы Avito.
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    <div class="mt-3 d-flex justify-content-start">
        {{ $reviews->links('pagination::bootstrap-4') }}
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', ()=>{
    const renumber = ()=>{
        document.querySelectorAll('#avitoReviewsSort .admin-grid-row').forEach((row, idx)=>{
            const label = row.querySelector('.js-order-label');
            if (label) {
                label.innerHTML = `<i class="fa fa-grip-vertical me-1"></i>${idx+1}`;
            }
            const orderInput = row.querySelector('.js-order-input');
            if (orderInput) orderInput.value = idx+1;
        });
    };
    renumber();

    const form = document.getElementById('avito-reviews-form');

    if (window.Sortable) {
        Sortable.create(document.getElementById('avitoReviewsSort'), {
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
