(() => {
  if (!('IntersectionObserver' in window) || !Element.prototype.animate ||
      window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  document.addEventListener('DOMContentLoaded', () => {
    const page = document.querySelector('.v2-page');
    if (!page) return;

    const running = new Set();
    const inEditor = () => document.body.classList.contains('ve-active');
    const selectors = [
      '.v2-hero__copy',
      '.v2-about .v2-heading',
      '.v2-about__photo',
      '.v2-about__text',
      '.v2-advantages .v2-heading',
      '.v2-advantage',
      '.v2-services .v2-heading',
      '.v2-service',
      '.v2-gallery .v2-heading',
      '.v2-gallery__item',
      '.v2-reviews__heading',
      '.v2-review',
      '.v2-contact .v2-heading',
      '.v2-contact__grid > .v2-panel'
    ];
    const selector = selectors.join(',');
    const reveal = element => element.classList.add('v2-reveal-done');

    const observer = new IntersectionObserver(entries => {
      const visible = entries.filter(entry => entry.isIntersecting);
      visible.forEach((entry, index) => {
        const element = entry.target;
        observer.unobserve(element);
        if (inEditor() || document.hidden || element.contains(document.activeElement)) {
          reveal(element);
          return;
        }

        // The paper cards already have a small rotation. Keep it intact.
        const sticker = element.classList.contains('v2-service');
        const keyframes = sticker
          ? [{opacity: 0}, {opacity: 1}]
          : [
              {opacity: 0, transform: 'translate3d(0, 16px, 0)'},
              {opacity: 1, transform: 'translate3d(0, 0, 0)'}
            ];
        const animation = element.animate(keyframes, {
          duration: sticker ? 540 : 620,
          delay: Math.min(index, 3) * 65,
          easing: 'cubic-bezier(.22, 1, .36, 1)',
          fill: 'both'
        });
        running.add(animation);
        animation.addEventListener('finish', () => {
          reveal(element);
          running.delete(animation);
          animation.cancel();
        }, {once: true});
        animation.addEventListener('cancel', () => running.delete(animation), {once: true});
      });
    }, {threshold: 0.08, rootMargin: '0px 0px -28px 0px'});

    const observed = new WeakSet();
    const observe = element => {
      if (!element || observed.has(element)) return;
      observed.add(element);
      observer.observe(element);
    };
    const start = () => {
      page.querySelectorAll(selector).forEach(observe);
    };
    if (document.documentElement.classList.contains('site-preloader-enabled')) {
      const preloader = new MutationObserver(() => {
        if (document.documentElement.classList.contains('site-preloader-enabled')) return;
        preloader.disconnect();
        start();
      });
      preloader.observe(document.documentElement, {attributes: true, attributeFilter: ['class']});
    } else {
      start();
    }

    // Photos added by "Загрузить ещё" use the same entrance as initial photos.
    const gallery = document.getElementById('v2-gallery-grid');
    if (gallery) {
      new MutationObserver(records => {
        records.forEach(record => record.addedNodes.forEach(node => {
          if (node.nodeType !== Node.ELEMENT_NODE) return;
          if (node.matches('.v2-gallery__item')) observe(node);
          node.querySelectorAll?.('.v2-gallery__item').forEach(observe);
        }));
      }).observe(gallery, {childList: true});
    }

    // Editing controls must remain still and fully visible.
    new MutationObserver(() => {
      if (!inEditor()) return;
      page.querySelectorAll(selector).forEach(reveal);
      running.forEach(animation => animation.cancel());
    }).observe(document.body, {attributes: true, attributeFilter: ['class']});
    window.__v2RevealInitialized = true;
  });
})();