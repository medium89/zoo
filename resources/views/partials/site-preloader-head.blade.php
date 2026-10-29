<script>document.documentElement.classList.add('site-preloader-enabled');</script>
<style>
.site-preloader{display:none}
.site-preloader-enabled .site-preloader{
  position:fixed;inset:0;z-index:20000;display:grid;place-items:center;
  background:#fff;opacity:1;pointer-events:auto;
  animation:site-preloader-failsafe 0s linear 4s forwards;
}
.site-preloader__trail{position:relative;width:128px;height:292px}
.site-preloader__step{position:absolute;width:52px;height:52px;color:#8150af}
.site-preloader__step:nth-child(2){color:#9162bd}
.site-preloader__step:nth-child(3){color:#7644a5}
.site-preloader__step:nth-child(4){color:#8b58b8}
.site-preloader__step:nth-child(1){left:17%;top:76%;transform:rotate(-12deg)}
.site-preloader__step:nth-child(2){left:47%;top:53%;transform:rotate(12deg)}
.site-preloader__step:nth-child(3){left:17%;top:30%;transform:rotate(-12deg)}
.site-preloader__step:nth-child(4){left:47%;top:7%;transform:rotate(12deg)}
.site-preloader__paw{display:block;width:100%;height:100%;opacity:0;animation:site-paw-step 1.55s cubic-bezier(.22,1,.36,1) both}
.site-preloader__step:nth-child(2) .site-preloader__paw{animation-delay:.13s}
.site-preloader__step:nth-child(3) .site-preloader__paw{animation-delay:.26s}
.site-preloader__step:nth-child(4) .site-preloader__paw{animation-delay:.39s}
.site-preloader.is-leaving{opacity:0;visibility:hidden;pointer-events:none;transition:opacity .34s ease,visibility .34s}
@keyframes site-paw-step{
  0%,6%{opacity:0;transform:translateY(12px) scale(.76)}
  18%,68%{opacity:1;transform:translateY(0) scale(1)}
  84%,100%{opacity:0;transform:translateY(-7px) scale(.93)}
}
@keyframes site-preloader-failsafe{to{visibility:hidden;pointer-events:none}}
@media(max-width:480px){.site-preloader__trail{width:112px;height:260px}.site-preloader__step{width:44px;height:44px}}
@media(prefers-reduced-motion:reduce){.site-preloader-enabled .site-preloader{display:none!important}}
</style>
