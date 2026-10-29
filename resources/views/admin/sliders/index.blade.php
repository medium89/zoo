@extends('admin.index')
@section('content')
@php
    $heroMode = $siteSettings?->hero_mode === 'slider' ? 'slider' : 'image';
    $heroTab = in_array(request('tab'), ['slider', 'image'], true) ? request('tab') : $heroMode;
    $heroPreview = $siteSettings?->hero_image ? asset('storage/' . $siteSettings->hero_image) : asset('images/v2/hero.webp');
@endphp
<h1 class="mb-3">Первый экран главной</h1>
<p class="text-muted mb-4">Выберите, что показывать на главной: слайды или одну картинку. Активным может быть только один вариант.</p>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<ul class="nav nav-tabs mb-4" id="heroModeTabs" role="tablist">
    <li class="nav-item" role="presentation"><button class="nav-link {{ $heroTab === 'slider' ? 'active' : '' }}" id="hero-slider-tab" data-bs-toggle="tab" data-bs-target="#hero-slider-panel" type="button" role="tab" aria-controls="hero-slider-panel" aria-selected="{{ $heroTab === 'slider' ? 'true' : 'false' }}">Слайдер @if($heroMode === 'slider')<span class="badge bg-success ms-1">Активен</span>@endif</button></li>
    <li class="nav-item" role="presentation"><button class="nav-link {{ $heroTab === 'image' ? 'active' : '' }}" id="hero-image-tab" data-bs-toggle="tab" data-bs-target="#hero-image-panel" type="button" role="tab" aria-controls="hero-image-panel" aria-selected="{{ $heroTab === 'image' ? 'true' : 'false' }}">Единая картинка @if($heroMode === 'image')<span class="badge bg-success ms-1">Активна</span>@endif</button></li>
</ul>
<div class="tab-content" id="heroModePanels">
    <div class="tab-pane fade {{ $heroTab === 'slider' ? 'show active' : '' }}" id="hero-slider-panel" role="tabpanel" aria-labelledby="hero-slider-tab">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <form action="{{ route('admin.sliders.hero-mode') }}" method="POST" class="js-hero-mode-form">
                @csrf
                <input type="hidden" name="mode" value="slider">
                <div class="form-check form-switch">
                    <input class="form-check-input js-hero-mode" type="checkbox" id="hero-slider-active" {{ $heroMode === 'slider' ? 'checked' : '' }}>
                    <label class="form-check-label" for="hero-slider-active">Показывать слайдер на главной</label>
                </div>
            </form>
            <a href="{{ route('admin.sliders.create') }}" class="btn btn-primary">Добавить слайд</a>
        </div>
        <p class="text-muted small">Ниже можно включать отдельные слайды и менять их порядок.</p>
<form id="status-form" action="{{ route('admin.sliders.status') }}" method="POST">
    @csrf
</form>
<div class="admin-grid" style="--grid-cols: 120px 200px 1fr 180px;">
    <div class="admin-grid-header">
        <div>Порядок</div>
        <div>Изображение</div>
        <div>Статус</div>
        <div class="text-end">Действия</div>
    </div>
    <div class="admin-grid-body js-sortable" id="slidersSort" data-custom-sort="1">
        @foreach($sliders as $slider)
            <div class="admin-grid-row admin-grid-row--media" data-id="{{ $slider->id }}">
                <div class="js-order-label text-muted admin-grid-cell--order" style="cursor:grab;"><i class="fa fa-grip-vertical me-1"></i>{{ $loop->iteration }}</div>
                <div class="admin-grid-cell--image"><img src="{{ $slider->image ? asset('storage/' . $slider->image) : asset('images/image-placeholder.svg') }}" alt="" width="120"></div>
                <div class="admin-grid-cell--status">
                    <div class="d-flex align-items-center gap-2">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input js-status-toggle" type="checkbox" data-id="{{ $slider->id }}" {{ $slider->active ? 'checked' : '' }} aria-label="Показывать слайд">
                        </div>
                    </div>
                    <span class="admin-status-label">{{ $slider->active ? 'Включено' : 'Выключено' }}</span>
                    <input type="hidden" name="orders[{{ $slider->id }}]" value="{{ $loop->iteration }}" class="js-order-input" form="status-form">
                </div>
                <div class="actions admin-grid-cell--actions">
                    <x-admin.actions-menu label="Действия со слайдом">
                        <a href="{{ route('admin.sliders.edit', $slider) }}" class="admin-actions-menu__item"><i class="fa fa-pen" aria-hidden="true"></i><span>Редактировать</span></a>
                        <form action="{{ route('admin.sliders.destroy', $slider) }}" method="POST" class="js-delete-form" data-confirm="Удалить слайд?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="admin-actions-menu__item admin-actions-menu__item--danger"><i class="fa fa-trash" aria-hidden="true"></i><span>Удалить</span></button>
                        </form>
                    </x-admin.actions-menu>
                </div>
            </div>
        @endforeach
    </div>
</div>
    </div>
    <div class="tab-pane fade {{ $heroTab === 'image' ? 'show active' : '' }}" id="hero-image-panel" role="tabpanel" aria-labelledby="hero-image-tab">
        <form action="{{ route('admin.sliders.hero-mode') }}" method="POST" class="js-hero-mode-form mb-4">
            @csrf
            <input type="hidden" name="mode" value="image">
            <div class="form-check form-switch">
                <input class="form-check-input js-hero-mode" type="checkbox" id="hero-image-active" {{ $heroMode === 'image' ? 'checked' : '' }}>
                <label class="form-check-label" for="hero-image-active">Показывать единую картинку на главной</label>
            </div>
        </form>
        <div class="admin-live-preview-layout"><div class="admin-live-preview-layout__fields">
        <form action="{{ route('admin.sliders.hero-image') }}" method="POST" enctype="multipart/form-data" class="card">
            @csrf
            <div class="card-body">
                <h2 class="h5 mb-3">Единая картинка</h2>
                @if($siteSettings?->hero_image)
                    <img data-upload-old-preview src="{{ $heroPreview }}" alt="Текущая картинка" class="d-block rounded mb-3" style="max-width:320px;max-height:180px;object-fit:cover">
                @else
                    <img src="{{ $heroPreview }}" alt="Картинка по умолчанию" class="d-block rounded mb-3" style="max-width:320px;max-height:180px;object-fit:cover">
                @endif
                <div class="mb-3">
                    <label for="hero_image" class="form-label">Изображение первого экрана</label>
                    <input type="file" class="form-control" id="hero_image" name="hero_image" accept="image/*" data-current-src="{{ $siteSettings?->hero_image ? $heroPreview : '' }}" data-remove-field="remove_hero_image">
                    <div class="form-text">Если удалить загруженное изображение, снова будет показана картинка V2 по умолчанию.</div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-sm-6"><label class="form-label" for="hero_image_scale">Размер <span id="hero-scale-value">100</span>%</label><input class="form-range" type="range" id="hero_image_scale" name="hero_image_scale" min="10" max="100" step="5" value="100" oninput="document.getElementById('hero-scale-value').textContent=this.value"></div>
                    <div class="col-sm-6"><label class="form-label" for="hero_image_quality">Качество <span id="hero-quality-value">85</span>%</label><input class="form-range" type="range" id="hero_image_quality" name="hero_image_quality" min="40" max="100" step="5" value="85" oninput="document.getElementById('hero-quality-value').textContent=this.value"></div>
                </div>
                <div class="mb-3">
                    <label for="hero_text" class="form-label">Заголовок поверх картинки</label>
                    <input type="text" class="form-control" id="hero_text" name="hero_text" maxlength="255" value="{{ old('hero_text', $siteSettings?->hero_text) }}" placeholder="Оставить заявку можно в разделе «Контакты»">
                </div>
                <button type="submit" class="btn btn-success">Сохранить картинку и текст</button>
            </div>
        </form>
        </div>
        @include('admin.partials.live-preview', ['kind' => 'hero_image'])
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', ()=>{
    document.querySelectorAll('.js-hero-mode').forEach((toggle) => {
        toggle.addEventListener('change', () => {
            if (!toggle.checked) { toggle.checked = true; return; }
            toggle.closest('form').requestSubmit();
        });
    });
    const renumber = ()=>{
        document.querySelectorAll('#slidersSort .admin-grid-row').forEach((row, idx)=>{
            const label = row.querySelector('.js-order-label');
            const orderField = row.querySelector('.js-order-input');
            if (label) label.innerHTML = `<i class="fa fa-grip-vertical me-1"></i>${idx+1}`;
            if (orderField) orderField.value = idx + 1;
        });
    };
    renumber();

    document.querySelectorAll('.js-status-toggle').forEach(toggle=>{
        toggle.addEventListener('change', ()=>{
            const form = document.getElementById('status-form');
            // убрать старые статусы
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
        Sortable.create(document.getElementById('slidersSort'), {
            animation:150,
            handle: '.js-order-label',
            onEnd: ()=>{
                renumber();
                document.getElementById('status-form').submit();
            }
        });
    }
});
</script>
@endsection 
