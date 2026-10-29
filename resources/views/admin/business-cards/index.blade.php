@extends('admin.index')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-business-cards.css') }}?v=20261027-1">
@endpush

@section('content')
<section class="business-cards">
    <div class="business-cards__heading">
        <div>
            <h1>Печать визиток</h1>
            <p>Загрузите готовый макет одной визитки. Страница A4 соберётся автоматически.</p>
        </div>
        <div class="business-cards__actions">
            <button type="button" class="btn btn-outline-secondary" id="cardsPrint" disabled><i class="fa fa-print" aria-hidden="true"></i> Печать</button>
            <button type="button" class="btn btn-primary" id="cardsDownload" disabled><i class="fa fa-download" aria-hidden="true"></i> Скачать PNG</button>
        </div>
    </div>

    <div class="business-cards__layout">
        <div class="business-cards__controls">
            <div class="business-cards__panel">
                <h2>1. Макет визитки</h2>
                <label class="business-cards__upload" for="cardsFile">
                    <i class="fa fa-image" aria-hidden="true"></i>
                    <span id="cardsFileLabel">Выбрать изображение</span>
                    <small>PNG, JPG или WEBP до 20 МБ</small>
                </label>
                <input id="cardsFile" class="visually-hidden" type="file" accept="image/png,image/jpeg,image/webp">
                <div id="cardsFileError" class="business-cards__error" role="alert" hidden></div>
                <div id="cardsResolution" class="business-cards__hint" aria-live="polite"></div>
            </div>

            <div class="business-cards__panel">
                <h2>2. Размер</h2>
                <label for="cardsPreset" class="form-label">Стандартный формат</label>
                <select id="cardsPreset" class="form-select">
                    <option value="90x50">90 × 50 мм — стандарт</option>
                    <option value="85x55">85 × 55 мм — европейский</option>
                    <option value="custom">Свой размер</option>
                </select>
                <div class="business-cards__size-fields">
                    <div>
                        <label for="cardsWidth" class="form-label">Ширина, мм</label>
                        <input id="cardsWidth" type="number" class="form-control" min="35" max="190" step="1" value="90">
                    </div>
                    <div>
                        <label for="cardsHeight" class="form-label">Высота, мм</label>
                        <input id="cardsHeight" type="number" class="form-control" min="25" max="277" step="1" value="50">
                    </div>
                </div>
                <label for="cardsScale" class="form-label business-cards__scale-label">Масштаб всех визиток <strong id="cardsScaleValue">100%</strong></label>
                <input id="cardsScale" type="range" class="form-range" min="70" max="130" step="1" value="100">
                <div class="business-cards__hint">Итоговый размер: <strong id="cardsActualSize">90 × 50 мм</strong></div>
            </div>

            <div class="business-cards__panel">
                <h2>3. Раскладка</h2>
                <div class="business-cards__size-fields">
                    <div>
                        <label for="cardsMargin" class="form-label">Поля A4, мм</label>
                        <input id="cardsMargin" type="number" class="form-control" min="0" max="30" step="1" value="10">
                    </div>
                    <div>
                        <label for="cardsGap" class="form-label">Промежуток, мм</label>
                        <input id="cardsGap" type="number" class="form-control" min="0" max="20" step="1" value="0">
                    </div>
                </div>
                <label for="cardsFit" class="form-label">Размещение изображения</label>
                <select id="cardsFit" class="form-select">
                    <option value="contain">Целиком, без обрезки</option>
                    <option value="cover">Заполнить визитку, обрезать края</option>
                </select>
                <div class="business-cards__hint">При промежутке 0 мм соседние визитки разделяет одна линия реза. Поля 0 мм начинают раскладку от верхнего левого края; остаток листа может остаться справа и снизу. Экспорт: A4, 300 dpi.</div>
            </div>
        </div>

        <div class="business-cards__preview">
            <div class="business-cards__preview-head">
                <div><strong>Предпросмотр A4</strong><span id="cardsCount" aria-live="polite">Загрузите изображение</span></div>
                <span>210 × 297 мм</span>
            </div>
            <div id="cardsCanvasWrap" class="business-cards__canvas-wrap">
                <canvas id="cardsCanvas" width="2480" height="3508" aria-label="Предпросмотр листа A4 с визитками"></canvas>
            </div>
            <div id="cardsLayoutError" class="business-cards__error" role="alert" hidden></div>
            <p class="business-cards__print-note">При печати выберите формат A4, масштаб 100% и отключите поля браузера.</p>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script src="{{ asset('js/admin-business-cards.js') }}?v=20261027-3" defer></script>
@endpush
