(() => {
  'use strict';

  const selector = '[data-admin-live-preview]';
  const fileUrls = new WeakMap();
  const defaultDoodles = {
    'передержка': 'paw.svg', 'выгул собак': 'paw-trail.svg',
    'уход за кошками': 'paw.svg', 'уход за грызунами': 'carrot.svg',
    'уход за попугаями': 'heart.svg', 'уход за растениями': 'leaf.svg',
    'уход за рыбками': 'bubbles.svg', 'приём лекарств': 'cross.svg'
  };
  const servicePalette = [
    ['#f0ddff','#dec2f4','#cca8e9','#89d5b0','18%','-18px','-33deg','-.65deg'],
    ['#def5e7','#bae7ce','#9ed5b7','#b49ae5','77%','-21px','33deg','.8deg'],
    ['#ffe0e7','#f9c0cf','#eaa9b9','#8ccaf2','51%','-26px','-36deg','-.9deg'],
    ['#fff0c4','#ffdc94','#dfc079','#ed9fb7','16%','-17px','30deg','.5deg'],
    ['#e0f1ff','#bfe1f7','#a2cce8','#f3b081','81%','-22px','-32deg','.45deg'],
    ['#e7e3ff','#c9c3f5','#b1a8de','#edce77','45%','-27px','36deg','-.6deg'],
    ['#ffe6d4','#ffc9a8','#e8b491','#88cfc3','23%','-18px','-31deg','.7deg'],
    ['#dcf6f1','#afe5da','#98d4c7','#b69adb','73%','-21px','34deg','-.8deg']
  ];
  const placeholder = '/images/image-placeholder.svg';
  const field = (form, name) => form.elements.namedItem(name);
  const positionIndex = (panel, form) => {
    let others = [];
    try { others = JSON.parse(panel.dataset.previewOrders || '[]'); } catch (_) {}
    const self = Number(panel.dataset.previewId) || Number.MAX_SAFE_INTEGER;
    const order = Number(value(form, 'order')) || 0;
    return others.filter(item => Number(item.id) !== self && (
      Number(item.order) < order || (Number(item.order) === order && Number(item.id) < self)
    )).length + 1;
  };
  const value = (form, name) => {
    const input = field(form, name);
    return input && 'value' in input ? String(input.value).trim() : '';
  };
  const clean = source => {
    const documentFromHtml = new DOMParser().parseFromString('<div>' + (source || '') + '</div>', 'text/html');
    const allowed = new Set(['P','BR','STRONG','B','EM','I','U','UL','OL','LI','H2','H3','BLOCKQUOTE','A','SPAN','IMG']);
    const copy = node => {
      if (node.nodeType === Node.TEXT_NODE) return document.createTextNode(node.textContent);
      if (node.nodeType !== Node.ELEMENT_NODE) return document.createDocumentFragment();
      const safe = allowed.has(node.tagName) ? document.createElement(node.tagName.toLowerCase()) : document.createDocumentFragment();
      if (node.tagName === 'A' && safe instanceof HTMLAnchorElement) {
        const href = node.getAttribute('href') || '';
        if (/^(https?:\/\/|mailto:|#|\/(?!\/))/i.test(href)) safe.href = href;
      }
      if (node.tagName === 'IMG' && safe instanceof HTMLImageElement) {
        const src = node.getAttribute('src') || '';
        if (/^(https?:\/\/|blob:|\/(?!\/))/i.test(src)) safe.src = src;
        safe.alt = node.getAttribute('alt') || '';
      }
      node.childNodes.forEach(child => safe.append(copy(child)));
      return safe;
    };
    const output = document.createElement('div');
    documentFromHtml.body.firstElementChild?.childNodes.forEach(node => output.append(copy(node)));
    return output.innerHTML;
  };
  const plain = html => new DOMParser().parseFromString(html || '', 'text/html').body.textContent.trim();
  const rich = (form, name) => clean(value(form, name));
  const setRich = (element, html, fallback) => {
    element.innerHTML = html || '';
    if (!element.textContent.trim()) element.textContent = fallback;
  };
  const urlFor = (input, file) => {
    if (fileUrls.has(file)) return fileUrls.get(file);
    const url = URL.createObjectURL(file);
    fileUrls.set(file, url);
    return url;
  };
  const image = (form, name) => {
    const input = field(form, name);
    if (!(input instanceof HTMLInputElement)) return '';
    const selected = input.files?.[0];
    if (selected) return urlFor(input, selected);
    const removeName = input.dataset.removeField;
    if (removeName) {
      const remove = field(form, removeName);
      if (remove instanceof HTMLInputElement && (remove.checked || (remove.type === 'hidden' && remove.value === '1'))) return '';
    }
    return input.dataset.currentSrc || '';
  };
  const setImage = (img, source) => { img.src = source || placeholder; };
  const node = (parent, selector) => parent.querySelector(selector);

  const templates = {
    slider: '<div class="v2-hero admin-preview-hero" data-hero-mode="slider"><div class="v2-hero__shade"></div><div class="v2-hero__inner"><div class="v2-hero__copy"><h1 data-preview-title></h1><span class="v2-button v2-button--pink">Оставить заявку</span></div></div></div><div class="admin-preview-secondary" data-preview-secondary hidden><img data-preview-secondary-image alt=""><span>Картинка-подложка (архивная версия)</span></div>',
    hero_image: '<div class="v2-hero admin-preview-hero" data-hero-mode="image"><div class="v2-hero__shade"></div><div class="v2-hero__inner"><div class="v2-hero__copy"><h1 data-preview-title></h1><span class="v2-button v2-button--pink">Оставить заявку</span></div></div></div>',
    about: '<div class="admin-preview-about"><div class="v2-about__grid"><div class="v2-about__photo"><img data-preview-image alt="Фото для раздела «Обо мне»"></div><div class="v2-about__text" data-preview-text></div></div></div>',
    advantage: '<div class="admin-preview-advantage"><div class="v2-advantages__grid"><article class="v2-advantage"><div class="v2-advantage__icon"><img data-preview-image alt=""></div><div class="v2-advantage__body"><h3 data-preview-title></h3><div class="v2-richtext v2-advantage__copy" data-preview-text></div><span class="v2-more-link v2-advantage__more">Читать полностью →</span></div></article></div></div><details class="admin-preview-details"><summary>Полный текст преимущества</summary><div data-preview-full></div></details>',
    service: '<div class="admin-preview-services"><div class="v2-services__grid"><article class="v2-service" data-sticker data-fold-side="left"><svg class="v2-service__paper" aria-hidden="true" focusable="false"><defs><linearGradient class="v2-service__paper-fill" id="admin-preview-paper" x1="0" y1="0" x2="0" y2="1"><stop offset="0"/><stop offset="1"/></linearGradient><linearGradient class="v2-service__fold-fill" id="admin-preview-fold" x1="0" y1="0" x2="1" y2="1"><stop offset="0"/><stop offset=".52"/><stop offset="1"/></linearGradient><filter id="admin-preview-shadow" x="-30%" y="-30%" width="160%" height="170%"><feDropShadow dx="0" dy="11" stdDeviation="11" flood-opacity=".19"/></filter></defs><path data-silhouette fill="url(#admin-preview-paper)" filter="url(#admin-preview-shadow)"/><path data-flap fill="url(#admin-preview-fold)"/><path data-seam fill="none" stroke="#fff" stroke-width="7.5" stroke-linejoin="round" stroke-linecap="round"/></svg><svg class="v2-service__tape" viewBox="0 0 32 96" preserveAspectRatio="none" aria-hidden="true"><path class="v2-service__tape-paper" d="M5 8 8 5 12 7 16 4 20 7 24 5 27 8V88l-3 3-4-2-4 3-4-3-4 2-3-3Z"/></svg><div class="v2-service__icon"><img data-preview-image alt=""></div><div class="v2-service__body"><h3 data-preview-title></h3><div class="v2-richtext" data-preview-text></div><div class="v2-service__tags" data-preview-tags></div></div><img class="v2-service__doodle" data-preview-doodle alt=""></article></div><div class="admin-preview-secondary" data-preview-secondary hidden><img data-preview-secondary-image alt=""><span>Изображение архивной версии сайта</span></div><p class="admin-preview-caption">Автоматический цвет зависит от положения услуги среди остальных карточек.</p></div>',
    gallery: '<div class="admin-preview-gallery"><div class="v2-gallery__grid" data-preview-gallery></div><div class="admin-preview-empty" data-preview-empty>Добавьте фотографии, чтобы увидеть их в альбоме.</div></div>',
    social: '<div class="admin-preview-social"><div class="v2-panel v2-socials"><h3>Или напишите напрямую</h3><a class="v2-social" tabindex="-1"><span class="v2-social__icon"><i data-preview-icon aria-hidden="true"></i></span><span class="v2-social__details"><strong data-preview-title></strong><span class="v2-social__description" data-preview-text></span></span><span class="v2-social__arrow" aria-hidden="true">→</span></a></div></div>'
  };

  function render(panel, form) {
    const kind = panel.dataset.adminLivePreview;
    const canvas = node(panel, '[data-admin-live-preview-canvas]');
    const title = node(canvas, '[data-preview-title]');
    const text = node(canvas, '[data-preview-text]');
    if (kind === 'hero_image') {
      const source = image(form, 'hero_image') || '/images/v2/hero.webp';
      node(canvas, '.admin-preview-hero').style.backgroundImage = 'url("' + source.replace(/"/g, '%22') + '")';
      title.textContent = value(form, 'hero_text') || 'Оставить заявку можно в разделе «Контакты»';
    } else if (kind === 'slider') {
      const source = image(form, 'image');
      const hero = node(canvas, '.admin-preview-hero');
      hero.style.backgroundImage = source ? 'url("' + source.replace(/"/g, '%22') + '")' : 'none';
      const copy = node(hero, '.v2-hero__copy');
      const position = value(form, 'position');
      copy.style.textAlign = position === 'right' ? 'right' : position === 'center' ? 'center' : 'left';
      copy.style.marginLeft = position === 'right' ? 'auto' : '';
      copy.style.marginRight = position === 'center' ? 'auto' : '';
      title.textContent = plain(value(form, 'text')) || 'Текст слайда';
      const bg = image(form, 'text_bg');
      const secondary = node(canvas, '[data-preview-secondary]');
      secondary.hidden = !bg;
      if (bg) setImage(node(secondary, 'img'), bg);
    } else if (kind === 'about') {
      setImage(node(canvas, '[data-preview-image]'), image(form, 'image'));
      setRich(text, rich(form, 'text'), 'Текст раздела «Обо мне»');
    } else if (kind === 'advantage') {
      setImage(node(canvas, '[data-preview-image]'), image(form, 'image'));
      title.textContent = value(form, 'title') || 'Заголовок преимущества';
      const full = rich(form, 'text');
      const short = rich(form, 'short_text') || clean(plain(full).split(/\s+/u).slice(0, 26).join(' '));
      setRich(text, short, 'Краткое содержание преимущества');
      setRich(node(canvas, '[data-preview-full]'), full, 'Полный текст пока не указан');
      node(canvas, '.v2-advantage').style.backgroundColor =
        ['#fff','#fff1f5','#fff','#fff9f1','#f8f1ff'][(positionIndex(panel, form) - 1) % 5];
    } else if (kind === 'service') {
      const card = node(canvas, '.v2-service');
      const serviceTitle = value(form, 'title');
      title.textContent = serviceTitle || 'Новая услуга';
      setRich(text, rich(form, 'text'), 'Описание услуги');
      setImage(node(canvas, '[data-preview-image]'), image(form, 'sticker_image'));
      const doodle = image(form, 'sticker_doodle');
      const doodleName = defaultDoodles[serviceTitle.toLocaleLowerCase('ru')] || 'heart.svg';
      setImage(node(canvas, '[data-preview-doodle]'), doodle || '/images/v2/services/' + doodleName);
      const color = field(form, 'sticker_color');
      const tape = field(form, 'tape_color');
      const customColor = field(form, 'custom_sticker_color')?.checked;
      const customTape = field(form, 'custom_tape_color')?.checked;
      const palette = servicePalette[(positionIndex(panel, form) - 1) % servicePalette.length];
      card.toggleAttribute('data-custom-sticker-color', Boolean(customColor));
      if (customColor) {
        card.style.setProperty('--sticker-base', color.value);
        ['--paper-light','--paper-dark','--paper-border'].forEach(name => card.style.removeProperty(name));
      } else {
        card.style.removeProperty('--sticker-base');
        ['--paper-light','--paper-dark','--paper-border'].forEach((name, index) => card.style.setProperty(name, palette[index]));
      }
      const tapeElement = node(card, '.v2-service__tape');
      ['--tape-color','--tape-x','--tape-y','--tape-rotate'].forEach((name, index) => tapeElement.style.setProperty(name, palette[index + 3]));
      card.style.setProperty('--sticker-tilt', palette[7]);
      if (customTape) card.style.setProperty('--tape-override', tape.value);
      else card.style.removeProperty('--tape-override');
      const tags = value(form, 'tags_text').split(/\r\n|\r|\n/u).map(item => item.trim()).filter(Boolean);
      const list = node(canvas, '[data-preview-tags]');
      list.replaceChildren(...tags.map(tag => {
        const chip = document.createElement('span');
        chip.className = 'v2-service__tag';
        chip.textContent = tag;
        return chip;
      }));
      const old = image(form, 'image');
      const secondary = node(canvas, '[data-preview-secondary]');
      secondary.hidden = !old;
      if (old) setImage(node(secondary, 'img'), old);
    } else if (kind === 'gallery') {
      const files = [...(field(form, 'images[]')?.files || [])].filter(file => file.type.startsWith('image/'));
      const grid = node(canvas, '[data-preview-gallery]');
      const processed = [...form.querySelectorAll('#previews img.card-img-top')];
      grid.replaceChildren(...files.map((file, index) => {
        const item = document.createElement('div');
        item.className = 'v2-gallery__item';
        const img = document.createElement('img');
        img.src = processed[index]?.src || urlFor(field(form, 'images[]'), file);
        img.alt = file.name;
        item.append(img);
        return item;
      }));
      node(canvas, '[data-preview-empty]').hidden = files.length > 0;
    } else if (kind === 'social') {
      const name = value(form, 'title');
      const link = value(form, 'link');
      title.textContent = name || 'Название контакта';
      setRich(text, rich(form, 'text'), 'Описание контакта');
      const channel = (name + ' ' + link).toLocaleLowerCase('ru');
      const brand = channel.includes('whatsapp') || channel.includes('wa.me') ? 'whatsapp'
        : channel.includes('telegram') || channel.includes('t.me') ? 'telegram'
        : channel.includes('vk.com') || channel.includes('вконтакте') ? 'vk'
        : channel.includes('ok.ru') || channel.includes('одноклассники') ? 'ok' : 'other';
      const card = node(canvas, '.v2-social');
      card.className = 'v2-social v2-social--' + brand;
      const icon = node(canvas, '[data-preview-icon]');
      const classes = {whatsapp:'fa-brands fa-whatsapp',telegram:'fa-brands fa-telegram',vk:'fa-brands fa-vk',ok:'fa-brands fa-odnoklassniki'};
      icon.className = classes[brand] || value(form, 'icon') || 'fa-solid fa-link';
      card.title = link ? 'Ссылка: ' + link : 'Укажите ссылку';
    }
    const active = field(form, 'active');
    const disabled = active instanceof HTMLInputElement && active.type === 'checkbox' ? !active.checked : active instanceof HTMLSelectElement && active.value === '0';
    node(panel, '.admin-live-preview__head span').textContent = disabled
      ? 'Сейчас выключено — после включения будет выглядеть так'
      : 'Обновляется при изменении полей';
  }

  function initSocialPicker(form) {
    const button = form.querySelector('#selectIconButton');
    const picker = form.querySelector('#iconPickerModal');
    const list = form.querySelector('#iconList');
    const input = form.querySelector('#icon');
    const search = form.querySelector('#iconSearchInput');
    if (!button || !picker || !list || !input || button.dataset.previewPickerReady === '1') return;
    button.dataset.previewPickerReady = '1';
    const icons = [
      'fab fa-whatsapp','fab fa-telegram','fab fa-vk','fab fa-instagram',
      'fab fa-facebook-f','fab fa-twitter','fab fa-youtube','fab fa-linkedin-in',
      'fab fa-pinterest','fab fa-github','fab fa-discord','fab fa-viber',
      'fas fa-phone','fas fa-envelope','fas fa-map-marker-alt','fas fa-globe',
      'fas fa-link','fas fa-share-alt','fas fa-comments','fas fa-heart','fab fa-odnoklassniki'
    ];
    const renderChoices = () => {
      const query = (search?.value || '').toLowerCase();
      list.replaceChildren(...icons.filter(icon => icon.includes(query)).map(iconClass => {
        const choice = document.createElement('button');
        choice.type = 'button';
        choice.className = 'border rounded m-1 p-2';
        choice.title = iconClass;
        choice.setAttribute('aria-label', iconClass);
        const glyph = document.createElement('i');
        glyph.className = iconClass + ' fa-2x';
        choice.append(glyph);
        choice.addEventListener('click', () => {
          input.value = iconClass;
          input.dispatchEvent(new Event('input', {bubbles:true}));
          form.querySelector('#selectedIconPreview').replaceChildren(glyph.cloneNode());
          picker.style.display = 'none';
        });
        return choice;
      }));
    };
    button.addEventListener('click', () => {
      search.value = '';
      renderChoices();
      picker.style.display = 'block';
    });
    form.querySelector('#closeIconPickerModal')?.addEventListener('click', () => { picker.style.display = 'none'; });
    search?.addEventListener('input', renderChoices);
    renderChoices();
  }

  function init(panel) {
    if (panel.dataset.livePreviewReady === '1') return;
    const form = panel.closest('.admin-live-preview-layout')?.querySelector('form');
    const canvas = node(panel, '[data-admin-live-preview-canvas]');
    if (!form || !canvas || !templates[panel.dataset.adminLivePreview]) return;
    panel.dataset.livePreviewReady = '1';
    canvas.innerHTML = templates[panel.dataset.adminLivePreview];
    if (panel.dataset.adminLivePreview === 'social') initSocialPicker(form);
    if (panel.dataset.adminLivePreview === 'service') {
      const sticker = node(canvas, '.v2-service');
      window.ZoolandStickerFold?.(sticker);
    }
    let frame = 0;
    const schedule = () => {
      if (frame) return;
      frame = requestAnimationFrame(() => { frame = 0; render(panel, form); });
    };
    form.addEventListener('input', event => {
      const colorFlag = event.target.name === 'sticker_color' ? 'custom_sticker_color'
        : event.target.name === 'tape_color' ? 'custom_tape_color' : '';
      if (colorFlag && field(form, colorFlag)) field(form, colorFlag).checked = true;
      schedule();
    });
    form.addEventListener('change', schedule);
    form.addEventListener('click', schedule);
    new MutationObserver(schedule).observe(form, {childList:true,subtree:true,attributes:true,attributeFilter:['src']});
    schedule();
  }

  function start() {
    document.querySelectorAll(selector).forEach(init);
    new MutationObserver(records => {
      records.forEach(record => record.addedNodes.forEach(node => {
        if (!(node instanceof Element)) return;
        if (node.matches(selector)) init(node);
        node.querySelectorAll(selector).forEach(init);
      }));
    }).observe(document.body, {childList:true,subtree:true});
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
  else start();
})();