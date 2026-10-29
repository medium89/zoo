(() => {
  'use strict';
  const widgets = new WeakMap();
  let ready = false;

  const setStatus = (form, message, state) => {
    const box = form.querySelector('.captcha-inline');
    if (!box) return;
    box.classList.toggle('captcha-inline--verified', state === 'verified');
    box.classList.toggle('captcha-inline--needs-check', state === 'error');
    box.querySelector('.captcha-inline__status').textContent = message;
  };

  const fitWidget = box => {
    const wrap = box.querySelector('.captcha-inline__widget-wrap');
    const widget = box.querySelector('.g-recaptcha');
    const scale = Math.min(1, wrap.clientWidth / 304);
    widget.style.transform = 'scale(' + scale + ')';
    wrap.style.height = Math.ceil(78 * scale) + 'px';
  };

  const renderVisible = () => {
    if (!ready || !window.grecaptcha) return;
    document.querySelectorAll('form[data-captcha-form]').forEach(form => {
      const box = form.querySelector('.captcha-inline');
      if (!box || !box.getClientRects().length) return;
      fitWidget(box);
      if (widgets.has(form)) return;
      const widget = box.querySelector('.g-recaptcha');
      try {
        const id = window.grecaptcha.render(widget, {
          sitekey: widget.dataset.sitekey,
          theme: 'light',
          size: 'normal',
          callback: () => setStatus(form, 'Проверка пройдена. Теперь можно отправить сообщение.', 'verified'),
          'expired-callback': () => setStatus(form, 'Срок проверки истёк. Отметьте галочку снова.', 'error'),
          'error-callback': () => setStatus(form, 'Не удалось выполнить проверку. Попробуйте ещё раз.', 'error')
        });
        widgets.set(form, id);
      } catch (error) {
        setStatus(form, 'Проверка не загрузилась. Обновите страницу.', 'error');
      }
    });
  };

  window.captchaInlineReady = () => {
    ready = true;
    renderVisible();
  };
  window.renderInlineCaptchas = renderVisible;
  window.resetInlineCaptcha = form => {
    const id = widgets.get(form);
    if (id !== undefined && window.grecaptcha) window.grecaptcha.reset(id);
    setStatus(form, '', '');
  };
  window.captchaInlineLoadError = () => {
    document.querySelectorAll('form[data-captcha-form]').forEach(form => {
      setStatus(form, 'Проверка не загрузилась. Обновите страницу.', 'error');
    });
  };

  document.addEventListener('submit', event => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.matches('[data-captcha-form]')) return;
    const id = widgets.get(form);
    if (id !== undefined && window.grecaptcha?.getResponse(id)) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    renderVisible();
    setStatus(form, ready ? 'Сначала отметьте «Я не робот».' : 'Проверка загружается. Подождите немного.', 'error');
    form.querySelector('.captcha-inline')?.scrollIntoView({block: 'center', behavior: 'smooth'});
  }, true);

  ['#quickContactModal', '#v2-review-modal'].forEach(selector => {
    const modal = document.querySelector(selector);
    if (modal) new MutationObserver(renderVisible).observe(modal, {attributes: true, attributeFilter: ['class', 'hidden']});
  });
  window.addEventListener('resize', renderVisible);
})();
