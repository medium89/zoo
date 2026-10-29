<fieldset class="mb-3 border rounded p-3">
    <legend class="float-none w-auto px-2 fs-6">Цвета стикера V2</legend>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="sticker_color">Фон стикера</label>
            <div class="d-flex align-items-center gap-2">
                <input class="form-control form-control-color" type="color" id="sticker_color" name="sticker_color" value="{{ old('sticker_color', $service->sticker_color ?? '#dec2f4') }}">
                <label class="form-check-label"><input class="form-check-input me-1" type="checkbox" name="custom_sticker_color" value="1" @checked(old('custom_sticker_color', filled($service->sticker_color ?? null)))> Свой цвет</label>
            </div>
            <small class="text-muted">Снимите галочку, чтобы вернуть автоматический цвет.</small>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="tape_color">Липучка</label>
            <div class="d-flex align-items-center gap-2">
                <input class="form-control form-control-color" type="color" id="tape_color" name="tape_color" value="{{ old('tape_color', $service->tape_color ?? '#89d5b0') }}">
                <label class="form-check-label"><input class="form-check-input me-1" type="checkbox" name="custom_tape_color" value="1" @checked(old('custom_tape_color', filled($service->tape_color ?? null)))> Свой цвет</label>
            </div>
            <small class="text-muted">Цвет меняется отдельно от фона стикера.</small>
        </div>
    </div>
</fieldset>
<script>
document.addEventListener('input', event => {
    if (event.target.matches('#sticker_color, #tape_color')) {
        event.target.closest('.col-md-6').querySelector('input[type="checkbox"]').checked = true;
    }
});
</script>
