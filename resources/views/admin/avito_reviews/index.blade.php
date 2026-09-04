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

    <x-admin.filters :action="route('admin.avito-reviews.index')" :filters="$filters" placeholder="Автор или текст отзыва" :auto="true">
        <label class="admin-filter-bar__field">Статус<select name="status" class="form-select"><option value="">Все</option><option value="new" @selected(($filters['status'] ?? '') === 'new')>Новые</option><option value="published" @selected(($filters['status'] ?? '') === 'published')>Опубликованы</option><option value="hidden" @selected(($filters['status'] ?? '') === 'hidden')>Скрыты</option></select></label>
        <label class="admin-filter-bar__field">По дате<select name="sort" class="form-select"><option value="date_desc" @selected(($filters['sort'] ?? 'date_desc') === 'date_desc')>Сначала новые</option><option value="date_asc" @selected(($filters['sort'] ?? '') === 'date_asc')>Сначала старые</option></select></label>
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
                        <i class="fa fa-grip-vertical me-1"></i>{{ $reviews->firstItem() + $loop->iteration - 1 }}
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
                        <input type="hidden" name="orders[{{ $review->id }}]" value="{{ $reviews->firstItem() + $loop->iteration - 1 }}" class="js-order-input" form="avito-reviews-form">
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

    @if ($reviews->total() > 0)
        <footer class="admin-pagination-bar mt-3">
            <p class="admin-pagination-bar__summary mb-0">
                Показано {{ $reviews->firstItem() }}–{{ $reviews->lastItem() }} из {{ $reviews->total() }} отзывов
            </p>
            <form method="GET" action="{{ route('admin.avito-reviews.index') }}" class="admin-pagination-bar__per-page">
                @if (!empty($filters['search']))<input type="hidden" name="search" value="{{ $filters['search'] }}">@endif
                @if (!empty($filters['status']))<input type="hidden" name="status" value="{{ $filters['status'] }}">@endif
                @if (!empty($filters['sort']))<input type="hidden" name="sort" value="{{ $filters['sort'] }}">@endif
                <label for="reviewsPerPage">Отзывов на странице</label>
                <select class="form-select form-select-sm" id="reviewsPerPage" name="per_page" onchange="this.form.submit()">
                    @foreach ([10, 25, 50, 100] as $perPage)
                        <option value="{{ $perPage }}" @selected(($filters['per_page'] ?? 25) == $perPage)>{{ $perPage }}</option>
                    @endforeach
                </select>
            </form>
            <nav class="admin-pagination-bar__pages" aria-label="Страницы отзывов">
                {{ $reviews->onEachSide(1)->links('pagination::bootstrap-4') }}
            </nav>
        </footer>
    @endif
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', ()=>{
    const firstOrder = {{ $reviews->firstItem() ?? 1 }};
    const renumber = ()=>{
        document.querySelectorAll('#avitoReviewsSort .admin-grid-row').forEach((row, idx)=>{
            const label = row.querySelector('.js-order-label');
            if (label) {
                label.innerHTML = `<i class="fa fa-grip-vertical me-1"></i>${firstOrder + idx}`;
            }
            const orderInput = row.querySelector('.js-order-input');
            if (orderInput) orderInput.value = firstOrder + idx;
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
@push('styles')
<style>
.admin-pagination-bar{display:flex;align-items:center;gap:16px;flex-wrap:wrap;padding:14px 16px;border:1px solid #e1e8f0;border-radius:12px;background:#fff}.admin-pagination-bar__summary{color:#63748a;font-size:.86rem}.admin-pagination-bar__per-page{display:flex;align-items:center;gap:8px;margin-left:auto;color:#55677c;font-size:.82rem;font-weight:700}.admin-pagination-bar__per-page .form-select{width:auto;min-width:74px}.admin-pagination-bar__pages .pagination{margin:0}.admin-pagination-bar__pages .page-link{color:#3d638d}.admin-pagination-bar__pages .active .page-link{background:#3979bb;border-color:#3979bb}@media(max-width:640px){.admin-pagination-bar{align-items:stretch}.admin-pagination-bar__per-page{justify-content:space-between;margin-left:0}.admin-pagination-bar__pages{overflow:auto}.admin-pagination-bar__pages .pagination{width:max-content}}
</style>
@endpush
@endsection
