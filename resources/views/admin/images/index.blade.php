@extends('admin.index')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">Изображения</h1>
        <span class="text-muted">{{ $images->total() }} файлов</span>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row g-3 row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-4 row-cols-xxl-5">
        @forelse($images as $img)
            <div class="col">
                <div class="card shadow-sm h-100 js-image-card" data-size-kb="{{ $img['size_kb'] ?? 0 }}">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="fw-bold">{{ $img['label'] }}</div>
                            <span class="badge bg-light text-dark">{{ $img['type'] }}</span>
                        </div>
                        <div class="ratio ratio-16x9 mb-3 bg-light rounded overflow-hidden">
                            <img src="{{ $img['url'] }}" alt="" class="w-100 h-100" style="object-fit: contain;">
                        </div>
                        <div class="small text-muted mb-1">{{ $img['path'] }}</div>
                        @if(!empty($img['size_kb']))
                            <div class="small text-muted mb-3">Размер: {{ $img['size_kb'] }} КБ ({{ $img['size_mb'] }} МБ)</div>
                        @endif
                        <form action="{{ route('admin.images.refresh') }}" method="POST" class="mt-auto js-refresh-form">
                            @csrf
                            <input type="hidden" name="type" value="{{ $img['type'] }}">
                            <input type="hidden" name="id" value="{{ $img['id'] }}">
                            <input type="hidden" name="field" value="{{ $img['field'] }}">
                            <input type="hidden" name="path" value="{{ $img['path'] }}">
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label mb-1 small">Размер (%)</label>
                                    <input type="number" name="scale" class="form-control" value="100" min="10" max="100">
                                </div>
                                <div class="col-6">
                                    <label class="form-label mb-1 small">Качество (%)</label>
                                    <input type="number" name="quality" class="form-control" value="85" min="40" max="100">
                                </div>
                            </div>
                            <div class="small text-muted mb-2 js-size-estimate"></div>
                            <button class="btn btn-primary w-100">Перегенерировать</button>
                        </form>
                        @if(!empty($img['backup']['path']))
                            <form action="{{ route('admin.images.revert') }}" method="POST" class="mt-2">
                                @csrf
                                <input type="hidden" name="type" value="{{ $img['type'] }}">
                                <input type="hidden" name="id" value="{{ $img['id'] }}">
                                <input type="hidden" name="field" value="{{ $img['field'] }}">
                                <button class="btn btn-outline-secondary w-100">Откатить</button>
                                <div class="small text-muted mt-1">Есть резерв: {{ $img['backup']['path'] }}</div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-muted">Изображения не найдены.</div>
        @endforelse
    </div>

    <div class="mt-3 d-flex justify-content-center">
        {{ $images->onEachSide(1)->links('pagination::bootstrap-4') }}
    </div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', ()=>{
    document.querySelectorAll('.js-image-card').forEach(card=>{
        const baseKb = parseFloat(card.dataset.sizeKb || '0');
        const form = card.querySelector('.js-refresh-form');
        const scaleInput = form?.querySelector('[name="scale"]');
        const qualityInput = form?.querySelector('[name="quality"]');
        const hint = form?.querySelector('.js-size-estimate');
        const currentText = baseKb ? `Текущий: ${baseKb} КБ (${(baseKb/1024).toFixed(2)} МБ)` : '';
        const updateHint = ()=>{
            if(!hint || !scaleInput || !qualityInput){ return; }
            const s = Math.max(10, Math.min(100, parseInt(scaleInput.value||'100',10)));
            const q = Math.max(40, Math.min(100, parseInt(qualityInput.value||'85',10)));
            if(!baseKb){ hint.textContent = currentText; return; }
            const estimated = (baseKb * (s/100) * (q/100));
            hint.textContent = `${currentText}${currentText ? ' · ' : ''}Ожидаемо: ${estimated.toFixed(1)} КБ (${(estimated/1024).toFixed(2)} МБ)`;
        };
        if(hint){ hint.textContent = currentText; }
        [scaleInput, qualityInput].forEach(inp=>inp?.addEventListener('input', updateHint));
        updateHint();
    });
});
</script>
@endpush
@endsection
