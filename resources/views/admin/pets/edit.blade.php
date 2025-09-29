@extends('admin.index')
@section('content')
<h3>Редактировать питомца</h3>
@if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<form action="{{ route('admin.pets.update', $pet) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label class="form-label">Имя</label>
        <input type="text" class="form-control" name="name" value="{{ $pet->name }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Вид услуги</label>
        @php($sel = $pet->services ?? [])
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="services[]" value="передержка" id="s1" {{ in_array('передержка',$sel) ? 'checked' : '' }}>
            <label class="form-check-label" for="s1">Передержка</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="services[]" value="выгул" id="s2" {{ in_array('выгул',$sel) ? 'checked' : '' }}>
            <label class="form-check-label" for="s2">Выгул</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="services[]" value="кормление" id="s3" {{ in_array('кормление',$sel) ? 'checked' : '' }}>
            <label class="form-check-label" for="s3">Кормление</label>
        </div>
        <small class="text-muted">Можно выбрать несколько</small>
    </div>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">ФИО владельца</label>
            <input type="text" class="form-control" name="owner_name" value="{{ $pet->owner_name }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Телефон владельца</label>
            <input type="tel" class="form-control" name="owner_phone" id="owner_phone" value="{{ $pet->owner_phone }}" placeholder="+7 999 999 9999">
        </div>
    </div>
    <div class="mb-3 mt-3">
        <label class="form-label">Тип животного</label>
        <select name="animal_type" class="form-select">
            @php($types=['кошка'=>'Кошка','собака'=>'Собака','грызун'=>'Грызун','птица'=>'Птица','прочее'=>'Прочее'])
            <option value="">— не указан —</option>
            @foreach($types as $k=>$v)
                <option value="{{ $k }}" {{ $pet->animal_type===$k?'selected':'' }}>{{ $v }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Описание</label>
        <textarea class="form-control" name="description" rows="4">{{ $pet->description }}</textarea>
    </div>

    <div class="mb-3">
        <label class="form-label">Добавить фотографии</label>
        <input type="file" class="form-control" id="images" name="images[]" multiple>
        <div id="previews" class="row g-3 mt-2"></div>
    </div>
    @if($pet->photos->count())
    <div class="mb-3">
        <label class="form-label">Текущие фотографии</label>
        <div class="d-flex flex-wrap gap-2">
            @foreach($pet->photos as $photo)
                <img src="{{ asset('storage/'.$photo->path) }}" alt="" style="width:100px;height:100px;object-fit:cover;border:1px solid #ddd;">
            @endforeach
        </div>
    </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label">Плюсы</label>
            <div class="input-group mb-2">
                <input type="text" class="form-control" id="plusInput" placeholder="например: дружелюбный">
                <button class="btn btn-success" type="button" id="plusAdd">Добавить</button>
            </div>
            <div id="plusTags" class="d-flex flex-wrap gap-2">
                @foreach(($pet->pluses ?? []) as $p)
                    <span class="badge rounded-pill" style="background:#28a745;color:#fff;padding:8px 12px;position:relative;">{{ $p }}
                        <input type="hidden" name="pluses[]" value="{{ $p }}">
                        <span class="ms-2" style="cursor:pointer;" onclick="this.parentElement.remove()">×</span>
                    </span>
                @endforeach
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Минусы</label>
            <div class="input-group mb-2">
                <input type="text" class="form-control" id="minusInput" placeholder="например: кусается">
                <button class="btn btn-danger" type="button" id="minusAdd">Добавить</button>
            </div>
            <div id="minusTags" class="d-flex flex-wrap gap-2">
                @foreach(($pet->minuses ?? []) as $m)
                    <span class="badge rounded-pill" style="background:#dc3545;color:#fff;padding:8px 12px;position:relative;">{{ $m }}
                        <input type="hidden" name="minuses[]" value="{{ $m }}">
                        <span class="ms-2" style="cursor:pointer;" onclick="this.parentElement.remove()">×</span>
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-success">Сохранить</button>
    <a href="{{ route('admin.pets.index') }}" class="btn btn-secondary">Назад</a>
</form>
@endsection

@section('scripts')
<script>
    function createTag(name, value, color){
        const w = document.createElement('span');
        w.className = 'badge rounded-pill';
        w.style.background = color; w.style.color = '#fff';
        w.style.padding = '8px 12px'; w.style.position='relative';
        w.textContent = value;
        const close = document.createElement('span');
        close.textContent = '×'; close.style.marginLeft='8px'; close.style.cursor='pointer';
        close.addEventListener('click', ()=>{ w.remove(); });
        const hidden = document.createElement('input'); hidden.type='hidden'; hidden.name=name; hidden.value=value;
        w.appendChild(hidden); w.appendChild(close);
        return w;
    }
    document.getElementById('plusAdd').addEventListener('click', ()=>{
        const inp = document.getElementById('plusInput'); const val=(inp.value||'').trim(); if(!val) return; inp.value='';
        document.getElementById('plusTags').appendChild(createTag('pluses[]', val, '#28a745'));
    });
    document.getElementById('minusAdd').addEventListener('click', ()=>{
        const inp = document.getElementById('minusInput'); const val=(inp.value||'').trim(); if(!val) return; inp.value='';
        document.getElementById('minusTags').appendChild(createTag('minuses[]', val, '#dc3545'));
    });

    (function(){
        const input = document.getElementById('images'); const previews = document.getElementById('previews');
        function fmtBytes(bytes){ if(bytes===0) return '0 B'; const k=1024, dm=1, sizes=['B','KB','MB','GB']; const i=Math.floor(Math.log(bytes)/Math.log(k)); return parseFloat((bytes/Math.pow(k,i)).toFixed(dm))+' '+sizes[i]; }
        function clearPreviews(){ previews.innerHTML=''; }
        async function buildPreview(file){
            const col=document.createElement('div'); col.className='col-md-4';
            const card=document.createElement('div'); card.className='card';
            const header=document.createElement('div'); header.className='card-header p-2';
            const body=document.createElement('div'); body.className='card-body';
            const title=document.createElement('div'); title.className='card-title small'; title.textContent=file.name;
            const orig=document.createElement('div'); orig.className='small text-muted'; orig.textContent='Оригинал: '+fmtBytes(file.size);
            const res=document.createElement('div'); const img=document.createElement('img'); img.className='card-img-top'; img.style.objectFit='cover'; img.style.height='150px';
            const scaleLabel=document.createElement('label'); scaleLabel.className='form-label small'; scaleLabel.textContent='Размер (в %)';
            const scaleInput=document.createElement('input'); scaleInput.type='range'; scaleInput.className='form-range'; scaleInput.name='scales[]'; scaleInput.min='10'; scaleInput.max='100'; scaleInput.step='5'; scaleInput.value='100';
            const qualityLabel=document.createElement('label'); qualityLabel.className='form-label small'; qualityLabel.textContent='Качество (в %)';
            const qualityInput=document.createElement('input'); qualityInput.type='range'; qualityInput.className='form-range'; qualityInput.name='qualities[]'; qualityInput.min='40'; qualityInput.max='100'; qualityInput.step='5'; qualityInput.value='85';
            header.append(scaleLabel, scaleInput, qualityLabel, qualityInput);
            const type=(file.type||'').toLowerCase(); const url=URL.createObjectURL(file); img.src=url; const isRaster=/(jpeg|jpg|png|gif|webp)/i.test(type);
            if (!isRaster){ res.className='small'; res.textContent='Будет загружен как есть'; }
            else { const bmp=await createImageBitmap(file).catch(()=>null); if(bmp){ function render(){ const s=parseInt(scaleInput.value,10)/100; const q=Math.max(0.4,Math.min(1,parseInt(qualityInput.value,10)/100)); const canvas=document.createElement('canvas'); canvas.width=Math.max(1,Math.round(bmp.width*s)); canvas.height=Math.max(1,Math.round(bmp.height*s)); const ctx=canvas.getContext('2d'); ctx.drawImage(bmp,0,0,canvas.width,canvas.height); const mime=type.includes('png')?'image/png':(type.includes('webp')?'image/webp':'image/jpeg'); canvas.toBlob((blob)=>{ if(!blob) return; const u=URL.createObjectURL(blob); img.src=u; res.className='small'; res.textContent=`Итог: ${canvas.width}×${canvas.height}, ${fmtBytes(blob.size)}`; }, mime, q);} render(); scaleInput.addEventListener('input', render); qualityInput.addEventListener('input', render);} }
            body.append(title, orig, res); card.append(header, img, body); col.append(card); previews.append(col);
        }
        async function rebuild(){ clearPreviews(); const files=Array.from(input.files||[]); for(const f of files){ await buildPreview(f); } }
        input.addEventListener('change', rebuild);
    })();
</script>
    <script>
      const phone = document.getElementById('owner_phone');
      if (phone){
        phone.addEventListener('input', ()=>{
          let v = phone.value.replace(/\D/g,'');
          if(!v.startsWith('7')) v = '7' + v;
          v = v.substring(0, 11);
          let out = '+7 ';
          if (v.length>1) out += v.substring(1,4);
          if (v.length>4) out += ' ' + v.substring(4,7);
          if (v.length>7) out += ' ' + v.substring(7,9);
          if (v.length>9) out += ' ' + v.substring(9,11);
          phone.value = out.trim();
        });
      }
    </script>
@endsection
