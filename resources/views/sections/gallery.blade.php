<section id="gallery">
    <div class="gallery-container container">
        @if($galleries->count() > 0)
            <div class="gallery-header">
                <h2>Фотоальбом</h2>
                <div class="foot"></div>
            </div>
            <div class="gallery-content" id="gallery-content">
                @php($initialItems = $galleries)
                @include('partials.gallery_items', ['items' => $initialItems])
            </div>
            @if(!empty($hasMoreGalleries) && $hasMoreGalleries)
                <div class="text-center" style="margin-top:20px;">
                    <button id="gallery-load-more" class="btn btn-primary" data-offset="{{ $galleries->count() }}" data-limit="8">загрузить еще</button>
                </div>
            @endif
        @else
            <div class="gallery-content__empty-message">
                <h2 style="text-align: center; padding: 20px;">На данный момент фотоальбом отсутствует.</h2>
            </div>
        @endif
    </div>
</section>
<style>
.lightbox-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.85);display:none;align-items:center;justify-content:center;z-index:1000}
.lightbox-backdrop.active{display:flex}
.lightbox-backdrop img{max-width:90vw;max-height:90vh;box-shadow:0 0 20px rgba(0,0,0,.5)}
.lightbox-close{position:absolute;top:20px;right:30px;color:#fff;font-size:28px;cursor:pointer}
.lightbox-nav{position:absolute;top:50%;width:100%;display:flex;justify-content:space-between;color:#fff;font-size:40px;user-select:none}
.lightbox-nav span{cursor:pointer;padding:10px 20px}
</style>
<div id="lightbox" class="lightbox-backdrop" aria-hidden="true">
    <span class="lightbox-close" aria-label="Закрыть">×</span>
    <div class="lightbox-nav"><span class="js-prev">‹</span><span class="js-next">›</span></div>
    <img src="" alt="Просмотр изображения">
    <div class="foot foot-white"></div>
    <div class="foot foot-white" style="position:absolute; bottom:20px; left:20px;"></div>
    <div class="foot foot-white" style="position:absolute; bottom:20px; right:20px;"></div>
    <div class="foot foot-white" style="position:absolute; top:20px; left:20px;"></div>
    <div class="foot foot-white" style="position:absolute; top:20px; right:70px;"></div>
    <div class="foot foot-white" style="position:absolute; top:20px; right:20px;"></div>
    <div class="foot foot-white" style="position:absolute; bottom:20px; left:70px;"></div>
    <div class="foot foot-white" style="position:absolute; bottom:20px; right:70px;"></div>
</div>
<script>
(function(){
    const container = document.getElementById('gallery-content');
    const box = document.getElementById('lightbox');
    const img = box.querySelector('img');
    const closeBtn = box.querySelector('.lightbox-close');
    const prevBtn = box.querySelector('.js-prev');
    const nextBtn = box.querySelector('.js-next');
    let idx = 0;

    function thumbs(){ return Array.from(document.querySelectorAll('.js-gallery-thumb')); }
    function openByIndex(i){ const list = thumbs(); if(!list.length) return; idx = ((i%list.length)+list.length)%list.length; img.src = list[idx].dataset.full; box.classList.add('active'); box.setAttribute('aria-hidden','false'); }
    function close(){ box.classList.remove('active'); box.setAttribute('aria-hidden','true'); img.src = ''; }
    function prev(){ openByIndex(idx - 1); }
    function next(){ openByIndex(idx + 1); }

    container.addEventListener('click', (e)=>{
        const t = e.target.closest('.js-gallery-thumb');
        if (!t) return;
        const list = thumbs();
        const i = list.indexOf(t);
        if (i >= 0) openByIndex(i);
    });
    closeBtn.addEventListener('click', close);
    box.addEventListener('click', (e)=>{ if(e.target === box) close(); });
    document.addEventListener('keydown', (e)=>{
        if(!box.classList.contains('active')) return;
        if(e.key === 'Escape') close();
        if(e.key === 'ArrowLeft') prev();
        if(e.key === 'ArrowRight') next();
    });
    prevBtn.addEventListener('click', (e)=>{ e.stopPropagation(); prev(); });
    nextBtn.addEventListener('click', (e)=>{ e.stopPropagation(); next(); });

    const btn = document.getElementById('gallery-load-more');
    if (btn && container) {
        let offset = parseInt(btn.dataset.offset || container.querySelectorAll('.js-gallery-thumb').length, 10) || 0;
        const limit = parseInt(btn.dataset.limit || 6, 10);
        let loading = false;
        const seenIds = new Set(Array.from(container.querySelectorAll('.gallery-content__item')).map(el => el.dataset.id));
        btn.addEventListener('click', async ()=>{
            if (loading) return;
            loading = true;
            btn.disabled = true;
            btn.textContent = 'Загрузка...';
            try {
                const url = `/gallery/more?offset=${offset}&limit=${limit}`;
                const res = await fetch(url, {headers: {'X-Requested-With':'XMLHttpRequest'}});
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const data = await res.json();
                if (data && data.html) {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(data.html, 'text/html');
                    const items = Array.from(doc.body.children).filter(el => el.classList.contains('gallery-content__item'));
                    if (!items.length) { btn.style.display = 'none'; return; }
                    items.forEach(el=>{
                        const id = el.dataset.id;
                        if (id && seenIds.has(id)) return;
                        if (id) seenIds.add(id);
                        container.appendChild(el);
                    });
                    offset += data.count || items.length;
                    if (!data.hasMore || !(data.count||items.length)) { btn.style.display = 'none'; }
                } else {
                    btn.style.display = 'none';
                }
            } catch(e) {
                console.error(e);
                btn.textContent = 'Ошибка, попробовать еще';
            } finally {
                loading = false;
                btn.disabled = false;
                btn.textContent = 'загрузить еще';
            }
        });
    }
})();
</script>
