document.addEventListener('DOMContentLoaded', () => {
  const contactBadge = document.getElementById('v2QuickContactBadge');
  const contactGallery = document.getElementById('v2-gallery');
  if (contactBadge) {
    let badgeClosed = false;
    let gallerySeen = !contactGallery;
    let timePassed = false;
    let pulseInterval = null;
    let pulseTimeout = null;

    const pulseBadge = () => {
      if (badgeClosed || contactBadge.hidden || document.hidden) return;
      contactBadge.classList.remove('is-dimmed', 'is-pulsing');
      void contactBadge.offsetWidth;
      contactBadge.classList.add('is-pulsing');
      clearTimeout(pulseTimeout);
      pulseTimeout = setTimeout(() => {
        contactBadge.classList.remove('is-pulsing');
        if (!contactBadge.hidden) contactBadge.classList.add('is-dimmed');
      }, 3200);
    };
    const showBadge = () => {
      if (badgeClosed || !gallerySeen || !timePassed || !contactBadge.hidden) return;
      contactBadge.hidden = false;
      requestAnimationFrame(() => {
        contactBadge.classList.add('is-visible');
        pulseBadge();
      });
      pulseInterval = setInterval(pulseBadge, 30000);
    };
    setTimeout(() => { timePassed = true; showBadge(); }, 30000);

    if (contactGallery && 'IntersectionObserver' in window) {
      const observer = new IntersectionObserver((entries) => {
        if (!entries.some(entry => entry.isIntersecting)) return;
        gallerySeen = true;
        observer.disconnect();
        showBadge();
      }, {threshold: 0.15});
      observer.observe(contactGallery);
    } else if (contactGallery) {
      const onScroll = () => {
        if (contactGallery.getBoundingClientRect().top >= window.innerHeight * 0.85) return;
        gallerySeen = true;
        window.removeEventListener('scroll', onScroll);
        showBadge();
      };
      window.addEventListener('scroll', onScroll, {passive: true});
      onScroll();
    }

    contactBadge.querySelector('.v2-quick-contact__close')?.addEventListener('click', () => {
      badgeClosed = true;
      contactBadge.hidden = true;
      clearInterval(pulseInterval);
      clearTimeout(pulseTimeout);
    });
    contactBadge.querySelector('.v2-quick-contact__link')?.addEventListener('click', () => {
      badgeClosed = true;
      contactBadge.hidden = true;
      clearInterval(pulseInterval);
      clearTimeout(pulseTimeout);
    });
  }

  const hero = document.querySelector('.v2-hero[data-hero-mode="slider"]');
  const heroSlides = hero ? [...hero.querySelectorAll('.v2-hero__slide')] : [];
  if (heroSlides.length > 1) {
    let heroIndex = 0;
    const heroHeading = hero.querySelector('h1');
    const defaultHeading = 'Оставить заявку можно в разделе «Контакты»';
    const showHeroSlide = (next) => {
      heroSlides[heroIndex].classList.remove('is-active');
      heroIndex = (next + heroSlides.length) % heroSlides.length;
      heroSlides[heroIndex].classList.add('is-active');
      heroHeading.textContent = heroSlides[heroIndex].dataset.heroText || defaultHeading;
    };
    let heroTimer = setInterval(() => showHeroSlide(heroIndex + 1), 6500);
    const moveHeroSlide = (direction) => {
      showHeroSlide(heroIndex + direction);
      clearInterval(heroTimer);
      heroTimer = setInterval(() => showHeroSlide(heroIndex + 1), 6500);
    };
    hero.querySelector('[data-hero-prev]')?.addEventListener('click', () => moveHeroSlide(-1));
    hero.querySelector('[data-hero-next]')?.addEventListener('click', () => moveHeroSlide(1));
    document.addEventListener('visibilitychange', () => {
      clearInterval(heroTimer);
      if (!document.hidden) heroTimer = setInterval(() => showHeroSlide(heroIndex + 1), 6500);
    });
  }
  function getFoldPaths(w, h) {
    const n = x => Number(x.toFixed(2));
    const d = 7; // внутренняя граница карточки
    const r = Math.min(52, w * .095);
    const s = Math.min(144, Math.max(97, w * .255)); // сторона загиба
    const A = { x: d + .08*s, y: h - d - .75*s }; // начало сгиба на левой стороне
    const B = { x: d + s, y: h - d };             // конец сгиба снизу
    const p = (...a) => a.map(n).join(' ');
    const crease = `M ${p(A.x,A.y)} C ${p(d+.26*s,A.y+.07*s)} ${p(d+.34*s,A.y-.12*s)} ${p(d+.46*s,A.y+.05*s)} C ${p(d+.68*s,A.y+.25*s)} ${p(B.x-.24*s,B.y-.09*s)} ${p(B.x,B.y)}`;
    const freeEdge = `C ${p(d+.70*s,B.y-.04*s)} ${p(d+.27*s,A.y+.30*s)} ${p(A.x,A.y)}`;
    // Внешний контур стикера: от нижней точки сгиба по внешнему краю
    // обратно в левую точку сгиба и затем по СВОБОДНОЙ кромке изнанки.
    const perimeter = `M ${p(B.x,B.y)} H ${n(w-d-r)} Q ${p(w-d,B.y)} ${p(w-d,h-d-r)} V ${n(d+r)} Q ${p(w-d,d)} ${p(w-d-r,d)} H ${n(d+r)} Q ${p(d,d)} ${p(d,d+r)} V ${n(A.y-24)} C ${p(d,A.y-12)} ${p(d+3,A.y-5)} ${p(A.x,A.y)}`;
    const silhouette = `${perimeter} ${freeEdge} Z`;
    // Физическая изнанка между белой линией сгиба и свободным краем.
    const flap = `${crease} ${freeEdge} Z`;
    // Белый кант - единый путь, без обводки по свободной кромке изнанки.
    const seam = `${perimeter} ${crease.replace(/^M [^C]+/, '')}`;
    return { silhouette, flap, seam };
  }
  const foldObserver = new ResizeObserver(entries => {
    for (const entry of entries) {
      const card = entry.target;
      // CSS rotates the entire card; offset dimensions match its unrotated SVG box.
      const w = card.offsetWidth;
      const h = card.offsetHeight;
      const svg = card.querySelector('.v2-service__paper');
      svg.setAttribute('viewBox', '0 0 ' + w + ' ' + h);
      const {silhouette, flap, seam} = getFoldPaths(w, h);
      svg.querySelector('[data-silhouette]').setAttribute('d', silhouette);
      svg.querySelector('[data-flap]').setAttribute('d', flap);
      svg.querySelector('[data-seam]').setAttribute('d', seam);
    }
  });
  document.querySelectorAll('.v2-service[data-sticker]').forEach(card => foldObserver.observe(card));

  const menuButton = document.querySelector('.v2-menu-toggle');
  const menu = document.getElementById('v2-nav');
  menuButton?.addEventListener('click', () => {
    const open = menu.classList.toggle('is-open');
    menuButton.setAttribute('aria-expanded', String(open));
    menuButton.setAttribute('aria-label', open ? 'Закрыть меню' : 'Открыть меню');
  });
  menu?.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
    menu.classList.remove('is-open');
    menuButton?.setAttribute('aria-expanded', 'false');
  }));

  const gallery = document.getElementById('v2-gallery-grid');
  const lightbox = document.getElementById('v2-lightbox');
  if (gallery && lightbox) {
    const image = lightbox.querySelector('img');
    let activeIndex = 0;
    let previousFocus = null;
    const items = () => [...gallery.querySelectorAll('.v2-gallery__item')];
    const show = index => {
      const all = items();
      if (!all.length) return;
      activeIndex = (index + all.length) % all.length;
      image.src = all[activeIndex].dataset.full;
      image.alt = all[activeIndex].querySelector('img')?.alt || 'Фото питомца';
      lightbox.hidden = false;
      document.body.style.overflow = 'hidden';
      lightbox.querySelector('.v2-lightbox__close').focus();
    };
    const close = () => {
      lightbox.hidden = true;
      image.removeAttribute('src');
      document.body.style.overflow = '';
      previousFocus?.focus();
    };
    gallery.addEventListener('click', event => {
      const button = event.target.closest('.v2-gallery__item');
      if (!button) return;
      previousFocus = button;
      show(items().indexOf(button));
    });
    lightbox.querySelector('.v2-lightbox__close').addEventListener('click', close);
    lightbox.querySelector('.v2-lightbox__prev').addEventListener('click', () => show(activeIndex - 1));
    lightbox.querySelector('.v2-lightbox__next').addEventListener('click', () => show(activeIndex + 1));
    lightbox.addEventListener('click', event => { if (event.target === lightbox) close(); });
    document.addEventListener('keydown', event => {
      if (lightbox.hidden) return;
      if (event.key === 'Escape') close();
      if (event.key === 'ArrowLeft') show(activeIndex - 1);
      if (event.key === 'ArrowRight') show(activeIndex + 1);
    });

    const more = document.getElementById('v2-gallery-more');
    more?.addEventListener('click', async () => {
      if (more.disabled) return;
      more.disabled = true;
      more.textContent = 'Загрузка…';
      try {
        const offset = Number(more.dataset.offset || 0);
        const response = await fetch('/gallery/more?offset=' + offset + '&limit=8', {headers: {'X-Requested-With': 'XMLHttpRequest'}});
        if (!response.ok) throw new Error('HTTP ' + response.status);
        const data = await response.json();
        const parsed = new DOMParser().parseFromString(data.html || '', 'text/html');
        parsed.querySelectorAll('.gallery-content__item').forEach(item => {
          if (gallery.querySelector('[data-id="' + item.dataset.id + '"]')) return;
          const source = item.querySelector('img');
          if (!source) return;
          const button = document.createElement('button');
          button.type = 'button';
          button.className = 'v2-gallery__item';
          button.dataset.id = item.dataset.id;
          button.dataset.veItem = 'galleries';
          button.dataset.veId = item.dataset.id;
          button.dataset.veImageType = 'gallery';
          button.dataset.veImageId = item.dataset.id;
          button.dataset.veImageField = 'image';
          button.dataset.full = source.dataset.full;
          button.setAttribute('aria-label', 'Открыть ' + source.alt);
          const img = document.createElement('img');
          img.src = source.src;
          img.alt = source.alt;
          img.loading = 'lazy';
          button.appendChild(img);
          gallery.appendChild(button);
        });
        more.dataset.offset = String(offset + Number(data.count || 0));
        if (!data.hasMore) more.remove();
      } catch (error) {
        more.textContent = 'Ошибка. Попробовать ещё';
        console.error(error);
        return;
      } finally {
        more.disabled = false;
        if (more.isConnected && more.textContent !== 'Ошибка. Попробовать ещё') more.textContent = 'Загрузить ещё';
      }
    });
  }

  const reviews = document.getElementById('v2-reviews-track');
  if (reviews) {
    const scroll = direction => {
      const card = reviews.querySelector('.v2-review');
      if (!card) return;
      reviews.scrollBy({left: direction * (card.getBoundingClientRect().width + 18), behavior: 'smooth'});
    };
    document.getElementById('v2-reviews-prev')?.addEventListener('click', () => scroll(-1));
    document.getElementById('v2-reviews-next')?.addEventListener('click', () => scroll(1));
  }

  const detailModal = document.getElementById('v2-detail-modal');
  const reviewModal = document.getElementById('v2-review-modal');
  let returnFocus = null;
  const openModal = (modal, trigger) => {
    if (!modal) return;
    returnFocus = trigger || document.activeElement;
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
    modal.querySelector('.v2-modal__close')?.focus();
  };
  const closeModal = modal => {
    if (!modal) return;
    modal.hidden = true;
    document.body.style.overflow = '';
    returnFocus?.focus?.();
  };
  document.querySelectorAll('[data-close-modal]').forEach(button => {
    button.addEventListener('click', () => closeModal(button.closest('.v2-modal')));
  });
  document.addEventListener('keydown', event => {
    const active = [detailModal, reviewModal].find(modal => modal && !modal.hidden);
    if (!active) return;
    if (event.key === 'Escape') closeModal(active);
    if (event.key === 'Tab') {
      const focusable = [...active.querySelectorAll('button:not([disabled]),input:not([disabled]),textarea:not([disabled]),a[href]')].filter(el => el.getClientRects().length);
      if (!focusable.length) return;
      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
      else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    }
  });

  document.querySelectorAll('.v2-advantage__more').forEach(button => {
    button.addEventListener('click', () => {
      const card = button.closest('.v2-advantage');
      detailModal.querySelector('#v2-detail-title').textContent = card.querySelector('h3').textContent;
      detailModal.querySelector('#v2-detail-content').innerHTML = card.querySelector('.v2-advantage__full').innerHTML;
      openModal(detailModal, button);
    });
  });
  document.querySelectorAll('.v2-review__more').forEach(button => {
    button.addEventListener('click', () => {
      const card = button.closest('.v2-review');
      detailModal.querySelector('#v2-detail-title').textContent = 'Отзыв: ' + card.querySelector('strong').textContent;
      const content = detailModal.querySelector('#v2-detail-content');
      content.textContent = card.querySelector('.v2-review__text').textContent;
      openModal(detailModal, button);
    });
  });
  document.querySelectorAll('[data-open-review]').forEach(button => {
    button.addEventListener('click', () => openModal(reviewModal, button));
  });
  if (reviewModal?.dataset.reopen === '1') openModal(reviewModal);
  if (document.querySelector('.v2-review-success')) {
    document.getElementById('v2-reviews')?.scrollIntoView({block: 'start'});
  }
});
