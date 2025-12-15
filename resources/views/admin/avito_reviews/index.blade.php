@extends('admin.index')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Отзывы Avito</h1>
        <form action="{{ route('admin.avito-reviews.refresh') }}" method="POST" class="mb-0">
            @csrf
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-rotate"></i> Обновить
            </button>
        </form>
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

    <div class="admin-grid" style="--grid-cols: 1fr 140px 2fr 120px 120px 140px;">
        <div class="admin-grid-header">
            <div>Имя</div>
            <div>Дата</div>
            <div>Текст</div>
            <div>Фото</div>
            <div>Статус</div>
            <div class="text-end">Действия</div>
        </div>
        <div class="admin-grid-body">
            @forelse ($reviews as $review)
                <div class="admin-grid-row">
                    <div>{{ $review->name ?? 'Без имени' }}</div>
                    <div>{{ $review->review_date ? $review->review_date->format('d.m.Y') : 'Не указана' }}</div>
                    <div class="text-clip">{{ \Illuminate\Support\Str::limit($review->text, 180) }}</div>
                    <div>
                        @php $count = is_array($review->photos) ? count($review->photos) : 0; @endphp
                        {{ $count > 0 ? $count . ' шт.' : 'Нет' }}
                    </div>
                    <div>{{ $review->status }}</div>
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
                        Отзывов пока нет. Нажмите «Обновить», чтобы загрузить их с Avito.
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    <div class="mt-3 d-flex justify-content-start">
        {{ $reviews->links('pagination::bootstrap-4') }}
    </div>
</div>
@endsection

