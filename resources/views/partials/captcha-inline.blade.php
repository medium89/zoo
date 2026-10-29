@if(config('services.recaptcha.site_key'))
<div class="captcha-inline">
    <div class="captcha-inline__heading">
        <span class="captcha-inline__mark" aria-hidden="true">✓</span>
        <span><strong>Подтвердите отправку</strong><small>Отметьте «Я не робот», затем отправьте сообщение.</small></span>
    </div>
    <div class="captcha-inline__widget-wrap"><div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div></div>
    <p class="captcha-inline__status" role="status" aria-live="polite"></p>
</div>
@endif
