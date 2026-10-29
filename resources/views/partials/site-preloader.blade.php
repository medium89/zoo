<div class="site-preloader" id="sitePreloader" aria-hidden="true">
    <svg width="0" height="0" focusable="false" aria-hidden="true" style="position:absolute">
        <symbol id="sitePreloaderPaw" viewBox="0 0 64 64">
            <path fill="currentColor" d="M32 30c-5 0-7.3 3.2-10.4 6.8C18.5 40.4 15 43 15 47.3c0 4.6 3.5 8 8 8 3.7 0 5.5-2.3 9-2.3s5.3 2.3 9 2.3c4.5 0 8-3.4 8-8 0-4.3-3.5-6.9-6.6-10.5C39.3 33.2 37 30 32 30Z"/>
            <ellipse fill="currentColor" cx="12.5" cy="27" rx="5" ry="7.5" transform="rotate(-18 12.5 27)"/>
            <ellipse fill="currentColor" cx="24" cy="17" rx="5.2" ry="8" transform="rotate(-8 24 17)"/>
            <ellipse fill="currentColor" cx="40" cy="17" rx="5.2" ry="8" transform="rotate(8 40 17)"/>
            <ellipse fill="currentColor" cx="51.5" cy="27" rx="5" ry="7.5" transform="rotate(18 51.5 27)"/>
        </symbol>
    </svg>
    <div class="site-preloader__trail">
        <span class="site-preloader__step"><svg class="site-preloader__paw" viewBox="0 0 64 64"><use href="#sitePreloaderPaw"/></svg></span>
        <span class="site-preloader__step"><svg class="site-preloader__paw" viewBox="0 0 64 64"><use href="#sitePreloaderPaw"/></svg></span>
        <span class="site-preloader__step"><svg class="site-preloader__paw" viewBox="0 0 64 64"><use href="#sitePreloaderPaw"/></svg></span>
        <span class="site-preloader__step"><svg class="site-preloader__paw" viewBox="0 0 64 64"><use href="#sitePreloaderPaw"/></svg></span>
    </div>
</div>
<script>
(() => {
    const screen = document.getElementById('sitePreloader');
    if (!screen) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        screen.remove();
        document.documentElement.classList.remove('site-preloader-enabled');
        return;
    }
    let domReady = document.readyState !== 'loading';
    let sequenceDone = false;
    let leaving = false;
    const finish = () => {
        if (!domReady || !sequenceDone || leaving) return;
        leaving = true;
        screen.classList.add('is-leaving');
        setTimeout(() => {
            screen.remove();
            document.documentElement.classList.remove('site-preloader-enabled');
        }, 400);
    };
    screen.querySelector('.site-preloader__step:last-child .site-preloader__paw')
        .addEventListener('animationend', event => {
            if (event.animationName !== 'site-paw-step') return;
            sequenceDone = true;
            finish();
        }, {once:true});
    if (!domReady) document.addEventListener('DOMContentLoaded', () => {
        domReady = true;
        finish();
    }, {once:true});
    setTimeout(() => {
        domReady = true;
        sequenceDone = true;
        finish();
    }, 4000);
})();
</script>
