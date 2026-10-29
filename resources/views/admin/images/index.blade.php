@extends('admin.index')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center">
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
                <div class="card shadow-sm h-100 js-image-card" data-size-kb="{{ $img['size_kb'] ?? 0 }}" data-size-w="{{ $img['dims']['w'] ?? 0 }}" data-size-h="{{ $img['dims']['h'] ?? 0 }}">
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
                            <div class="small text-muted mb-3">Размер: {{ $img['size_kb'] }} КБ ({{ $img['size_mb'] }} МБ) @if(!empty($img['dims'])) · {{ $img['dims']['w'] }}×{{ $img['dims']['h'] }} px @endif</div>
                        @endif
                        <form id="image-refresh-{{ $loop->index }}" action="{{ route('admin.images.refresh') }}" method="POST" class="mt-auto js-refresh-form">
                            @csrf
                            <input type="hidden" name="type" value="{{ $img['type'] }}">
                            <input type="hidden" name="id" value="{{ $img['id'] }}">
                            <input type="hidden" name="field" value="{{ $img['field'] }}">
                            <input type="hidden" name="path" value="{{ $img['path'] }}">
                            <input type="hidden" name="crop_x" value="">
                            <input type="hidden" name="crop_y" value="">
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
                            @if(!empty($img['is_raster']))
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input js-webp-switch" type="checkbox" role="switch" name="webp" value="1" id="webp-{{ $loop->index }}" @checked(str_ends_with(strtolower($img['path']), '.webp'))>
                                    <label class="form-check-label small" for="webp-{{ $loop->index }}">WEBP — конвертировать и показать ожидаемый размер</label>
                                </div>
                            @endif
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label mb-1 small">Кадрировать W (px)</label>
                                    <input type="number" name="crop_width" class="form-control" min="1" max="8000" placeholder="auto">
                                </div>
                                <div class="col-6">
                                    <label class="form-label mb-1 small">Кадрировать H (px)</label>
                                    <input type="number" name="crop_height" class="form-control" min="1" max="8000" placeholder="auto">
                                </div>
                            </div>
                            <div class="small text-muted mb-2 js-size-estimate"></div>
                        </form>
                        @if(!empty($img['backup']['path']))
                            <form id="image-revert-{{ $loop->index }}" action="{{ route('admin.images.revert') }}" method="POST" class="d-none">
                                @csrf
                                <input type="hidden" name="type" value="{{ $img['type'] }}">
                                <input type="hidden" name="id" value="{{ $img['id'] }}">
                                <input type="hidden" name="field" value="{{ $img['field'] }}">
                            </form>
                        @endif
                        <div class="d-flex align-items-center justify-content-between gap-2 mt-2">
                            @if(!empty($img['backup']['path']))<span class="small text-muted">Есть резервная копия</span>@else<span></span>@endif
                            <x-admin.actions-menu label="Действия с изображением {{ $img['label'] }}">
                                <button class="admin-actions-menu__item js-open-crop" type="button" form="image-refresh-{{ $loop->index }}" data-url="{{ $img['url'] }}"><i class="fa fa-crop-simple" aria-hidden="true"></i><span>Открыть кроп</span></button>
                                <button class="admin-actions-menu__item" type="submit" form="image-refresh-{{ $loop->index }}"><i class="fa fa-arrows-rotate" aria-hidden="true"></i><span>Перегенерировать</span></button>
                                @if(!empty($img['backup']['path']))<button class="admin-actions-menu__item" type="submit" form="image-revert-{{ $loop->index }}"><i class="fa fa-rotate-left" aria-hidden="true"></i><span>Откатить</span></button>@endif
                            </x-admin.actions-menu>
                        </div>
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
<link rel="stylesheet" href="https://unpkg.com/cropperjs@1.5.13/dist/cropper.min.css">
<script src="https://unpkg.com/cropperjs@1.5.13/dist/cropper.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', ()=>{
    document.querySelectorAll('.js-image-card').forEach(card=>{
        const baseKb = parseFloat(card.dataset.sizeKb || '0');
        const baseW = parseInt(card.dataset.sizeW || '0', 10);
        const baseH = parseInt(card.dataset.sizeH || '0', 10);
        const form = card.querySelector('.js-refresh-form');
        const scaleInput = form?.querySelector('[name="scale"]');
        const qualityInput = form?.querySelector('[name="quality"]');
        const cropWInput = form?.querySelector('[name="crop_width"]');
        const cropHInput = form?.querySelector('[name="crop_height"]');
        const cropXInput = form?.querySelector('[name="crop_x"]');
        const cropYInput = form?.querySelector('[name="crop_y"]');
        const webpInput = form?.querySelector('.js-webp-switch');
        const sourceImage = card.querySelector('img');
        const hint = form?.querySelector('.js-size-estimate');
        const currentText = baseKb ? 'Текущий: ' + baseKb + ' КБ (' + (baseKb / 1024).toFixed(2) + ' МБ)' : '';
        const currentDims = baseW && baseH ? ' · ' + baseW + '×' + baseH + ' px' : '';
        let estimateRevision = 0;
        const updateHint = ()=>{
            if(!hint || !scaleInput || !qualityInput){ return; }
            const scale = Math.max(10, Math.min(100, parseInt(scaleInput.value || '100', 10)));
            const quality = Math.max(40, Math.min(100, parseInt(qualityInput.value || '85', 10)));
            const cropW = cropWInput && cropWInput.value ? Math.max(1, parseInt(cropWInput.value, 10)) : null;
            const cropH = cropHInput && cropHInput.value ? Math.max(1, parseInt(cropHInput.value, 10)) : null;
            const prefix = currentText + currentDims + (currentText || currentDims ? ' · ' : '');
            if(!webpInput?.checked){
                hint.textContent = prefix + 'Отметьте WEBP, чтобы увидеть ожидаемый размер.';
                return;
            }
            const revision = ++estimateRevision;
            const width = sourceImage?.naturalWidth || baseW;
            const height = sourceImage?.naturalHeight || baseH;
            if(!width || !height || !sourceImage?.complete){
                hint.textContent = prefix + 'Загружаем изображение для расчёта WebP…';
                return;
            }
            const sourceW = Math.min(cropW || width, width);
            const sourceH = Math.min(cropH || height, height);
            const sourceX = cropXInput?.value ? Math.max(0, Math.min(width - sourceW, parseInt(cropXInput.value, 10))) : Math.max(0, Math.round((width - sourceW) / 2));
            const sourceY = cropYInput?.value ? Math.max(0, Math.min(height - sourceH, parseInt(cropYInput.value, 10))) : Math.max(0, Math.round((height - sourceH) / 2));
            const targetW = Math.max(1, Math.round(sourceW * scale / 100));
            const targetH = Math.max(1, Math.round(sourceH * scale / 100));
            const canvas = document.createElement('canvas');
            canvas.width = targetW;
            canvas.height = targetH;
            const context = canvas.getContext('2d');
            context.drawImage(sourceImage, sourceX, sourceY, sourceW, sourceH, 0, 0, targetW, targetH);
            hint.textContent = prefix + 'Считаем размер WebP…';
            canvas.toBlob(blob=>{
                if(revision !== estimateRevision){ return; }
                if(!blob){
                    hint.textContent = prefix + 'Не удалось рассчитать размер WebP.';
                    return;
                }
                const kb = blob.size / 1024;
                hint.textContent = prefix + 'Ожидаемый WebP: ' + kb.toFixed(1) + ' КБ (' + (kb / 1024).toFixed(2) + ' МБ) · ' + targetW + '×' + targetH + ' px';
            }, 'image/webp', quality / 100);
        };
        sourceImage?.addEventListener('load', updateHint, { once:true });
        [scaleInput, qualityInput, cropWInput, cropHInput].forEach(inp=>inp?.addEventListener('input', updateHint));
        webpInput?.addEventListener('change', updateHint);
        updateHint();
    });

    // Cropper modal
    const modalTpl = document.createElement('div');
    modalTpl.className = 'modal fade admin-modal';
    modalTpl.id = 'cropModal';
    modalTpl.tabIndex = -1;
    modalTpl.innerHTML = `
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Кадрирование</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
        </div>
        <div class="modal-body">
          <div class="ratio ratio-16x9 bg-light">
            <img id="cropModalImage" src="" alt="" class="w-100 h-100" style="object-fit:contain;">
          </div>
        </div>
        <div class="modal-footer">
          <div class="small text-muted me-auto" id="cropInfo"></div>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
          <button type="button" class="btn btn-primary" id="cropApply">Применить</button>
        </div>
      </div>
    </div>`;
    document.body.appendChild(modalTpl);
    const cropModalEl = document.getElementById('cropModal');
    const cropModal = cropModalEl ? new bootstrap.Modal(cropModalEl) : null;
    const cropImg = document.getElementById('cropModalImage');
    const cropInfo = document.getElementById('cropInfo');
    let cropper = null;
    let activeForm = null;

    document.querySelectorAll('.js-open-crop').forEach(btn=>{
        btn.addEventListener('click', ()=>{
            if(!cropModal) return;
            const form = btn.form || btn.closest('form');
            activeForm = form;
            cropImg.src = btn.dataset.url;
            cropModal.show();
            cropModalEl.addEventListener('shown.bs.modal', ()=>{
                cropper = new Cropper(cropImg, {
                    viewMode: 1,
                    autoCropArea: 1,
                    movable: true,
                    zoomable: true,
                    scalable: false,
                    ready(){
                        updateInfo();
                    },
                    crop(){ updateInfo(); }
                });
            }, { once:true });
            cropModalEl.addEventListener('hidden.bs.modal', ()=>{
                if(cropper){ cropper.destroy(); cropper=null; }
                activeForm = null;
            }, { once:true });
        });
    });

    function updateInfo(){
        if(!cropper) return;
        const data = cropper.getData();
        cropInfo.textContent = `Выбрано: ${Math.round(data.width)}×${Math.round(data.height)} px`;
    }

    const applyBtn = document.getElementById('cropApply');
    applyBtn?.addEventListener('click', ()=>{
        if(!cropper || !activeForm) return;
        const data = cropper.getData();
        activeForm.querySelector('[name="crop_x"]').value = Math.round(data.x);
        activeForm.querySelector('[name="crop_y"]').value = Math.round(data.y);
        activeForm.querySelector('[name="crop_width"]').value = Math.round(data.width);
        activeForm.querySelector('[name="crop_height"]').value = Math.round(data.height);
        const evt = new Event('input');
        activeForm.querySelectorAll('[name="crop_width"], [name="crop_height"]').forEach(inp=>inp.dispatchEvent(evt));
        cropModal.hide();
    });
});
</script>
@endpush
@endsection
