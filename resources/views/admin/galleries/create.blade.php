@extends('admin.index')
@section('content')
<h3>Добавить фото</h3>
@if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<form action="{{ route('admin.galleries.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="mb-3">
        <label for="images" class="form-label">Фотографии</label>
        <input type="file" class="form-control" id="images" name="images[]" multiple required>
        <small class="text-muted">Можно выбрать несколько файлов</small>
    </div>
    <!-- Индивидуальные бегунки будут в карточках предпросмотра ниже -->

    <div class="mb-3">
        <label class="form-label">Предпросмотр и оценка размера</label>
        <div id="previews" class="row g-3"></div>
        <small class="text-muted d-block mt-2">Настройки применяются отдельно к каждому фото (бегунки над превью).</small>
    </div>
    <button type="submit" class="btn btn-success" id="submitBtn">Загрузить</button>
</form>
@section('scripts')
<script>
    (function(){
        const input = document.getElementById('images');
        const previews = document.getElementById('previews');
        const MIN_THUMB_WIDTH = 443;

        function fmtBytes(bytes){
            if(bytes === 0) return '0 B';
            const k = 1024, dm = 1, sizes = ['B','KB','MB','GB'];
            const i = Math.floor(Math.log(bytes)/Math.log(k));
            return parseFloat((bytes/Math.pow(k,i)).toFixed(dm))+' '+sizes[i];
        }

        function clearPreviews(){ previews.innerHTML = ''; }

        async function buildPreview(file, idx){
            const col = document.createElement('div');
            col.className = 'col-md-4';
            const card = document.createElement('div');
            card.className = 'card';
            const body = document.createElement('div');
            body.className = 'card-body';
            const title = document.createElement('div');
            title.className = 'card-title small';
            title.textContent = file.name;
            const orig = document.createElement('div');
            const res = document.createElement('div');
            const img = document.createElement('img');
            img.className = 'card-img-top';
            img.style.objectFit = 'cover';
            img.style.height = '150px';

            const type = file.type.toLowerCase();
            const isImage = type.startsWith('image/');
            const isRaster = /(jpeg|jpg|png|gif|webp)/i.test(type);

            orig.className = 'small text-muted';
            orig.textContent = 'Оригинал: ' + fmtBytes(file.size);

            if(!isImage){
                body.append(title, document.createTextNode('Неизвестный тип файла'));
                card.append(img, body); col.append(card); previews.append(col); return;
            }

            // Per-image controls
            const header = document.createElement('div');
            header.className = 'card-header p-2';
            const scaleWrap = document.createElement('div');
            const qualityWrap = document.createElement('div');
            const scaleLabel = document.createElement('label');
            scaleLabel.className = 'form-label small';
            scaleLabel.textContent = 'Размер (в %)';
            const scaleInput = document.createElement('input');
            scaleInput.type = 'range';
            scaleInput.className = 'form-range';
            scaleInput.name = 'scales[]';
            scaleInput.min = '10';
            scaleInput.max = '100';
            scaleInput.step = '5';
            scaleInput.value = '100';
            const scaleNote = document.createElement('small');
            scaleNote.className = 'text-muted d-block';
            const scaleWarn = document.createElement('small');
            scaleWarn.className = 'text-danger d-block';
            scaleWarn.style.display = 'none';
            const qualityLabel = document.createElement('label');
            qualityLabel.className = 'form-label small';
            qualityLabel.textContent = 'Качество JPEG (в %)';
            const qualityInput = document.createElement('input');
            qualityInput.type = 'range';
            qualityInput.className = 'form-range';
            qualityInput.name = 'qualities[]';
            qualityInput.min = '40';
            qualityInput.max = '100';
            qualityInput.step = '5';
            qualityInput.value = '85';
            const qualityNote = document.createElement('small');
            qualityNote.className = 'text-muted d-block';
            qualityNote.textContent = 'Для PNG/WEBP подбирается эквивалентный уровень сжатия';

            const url = URL.createObjectURL(file);
            img.src = url;

            if (!isRaster){
                res.className = 'small';
                res.textContent = 'Будет загружен как есть (SVG и пр.)';
                body.append(title, orig, res);
                card.append(img, body); col.append(card); previews.append(col);
                return;
            }

            const bitmap = await createImageBitmap(file).catch(()=>null);
            if(!bitmap){
                res.textContent = 'Предпросмотр недоступен';
                body.append(title, orig, res);
                card.append(img, body); col.append(card); previews.append(col);
                return;
            }

            const requiredMin = Math.ceil((MIN_THUMB_WIDTH / bitmap.width) * 100);
            const newMin = Math.max(10, Math.min(100, requiredMin));
            scaleInput.min = String(newMin);
            if (parseInt(scaleInput.value,10) < newMin) scaleInput.value = String(newMin);
            scaleNote.textContent = `Минимальная ширина превью — ${MIN_THUMB_WIDTH}px. Минимально допустимый масштаб: ${newMin}%`;
            if (bitmap.width < MIN_THUMB_WIDTH) {
                scaleWarn.style.display = '';
                scaleWarn.textContent = 'Внимание: исходная ширина меньше 443px — увеличить без потери нельзя.';
            }

            function renderPreview(){
                const s = parseInt(scaleInput.value, 10)/100;
                const q = Math.max(0.4, Math.min(1, parseInt(qualityInput.value,10)/100));
                const canvas = document.createElement('canvas');
                canvas.width = Math.max(1, Math.round(bitmap.width * s));
                canvas.height = Math.max(1, Math.round(bitmap.height * s));
                const ctx = canvas.getContext('2d');
                ctx.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
                const mime = type.includes('png') ? 'image/png' : (type.includes('webp') ? 'image/webp' : 'image/jpeg');
                canvas.toBlob((blob)=>{
                    if (!blob) return;
                    const u = URL.createObjectURL(blob);
                    img.src = u;
                    res.className = 'small';
                    res.textContent = `Итог: ${canvas.width}×${canvas.height}, ${fmtBytes(blob.size)}`;
                }, mime, q);
            }

            renderPreview();
            scaleInput.addEventListener('input', ()=>{
                if (parseInt(scaleInput.value,10) < parseInt(scaleInput.min,10)) scaleInput.value = scaleInput.min;
                renderPreview();
            });
            qualityInput.addEventListener('input', renderPreview);

            scaleWrap.append(scaleLabel, scaleInput, scaleNote, scaleWarn);
            qualityWrap.append(qualityLabel, qualityInput, qualityNote);
            header.append(scaleWrap, qualityWrap);
            body.append(title, orig, res);
            card.append(header, img, body); col.append(card); previews.append(col);
        }

        async function rebuildPreviews(){
            clearPreviews();
            const files = Array.from(input.files || []);
            for(const [i, f] of files.slice(0, 12).entries()) { // ограничим предпросмотр первыми 12
                await buildPreview(f, i);
            }
        }

        input.addEventListener('change', rebuildPreviews);
    })();
</script>
@endsection
@endsection 
