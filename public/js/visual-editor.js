(() => {
  'use strict';
  const root = document.getElementById('visualEditor');
  if (!root) return;
  const $ = selector => document.querySelector(selector);
  const groups = [...document.querySelectorAll('[data-ve-group]')];
  const originals = new Map();
  const pendingFields = new Map();
  const pendingImages = new Map();
  const pendingNav = new Map();
  const history = [];
  const toolbarSave = $('#veSave');
  const toolbarUndo = $('#veCancel');
  const dock = $('#veDock');
  const modal = $('#veModal');
  const imageModal = $('#veImageModal');
  const navModal = $('#veNavModal');
  document.body.append(modal, imageModal);
  const placePopover = (dialog, anchor) => {
    if (!dialog.open || !anchor?.isConnected) return;
    const point = anchor.getBoundingClientRect();
    const box = dialog.getBoundingClientRect();
    const gap = 9;
    const pad = 12;
    let left = point.left;
    if (left + box.width > innerWidth - pad) left = point.right - box.width;
    left = Math.max(pad, Math.min(left, innerWidth - box.width - pad));
    let top = point.bottom + gap;
    if (top + box.height > innerHeight - pad) top = point.top - box.height - gap;
    top = Math.max(pad, Math.min(top, innerHeight - box.height - pad));
    dialog.style.left = Math.round(left) + 'px';
    dialog.style.top = Math.round(top) + 'px';
  };
  const csrf = $('meta[name="csrf-token"]')?.content || '';
  let enabled = false;
  let editing = null;
  let editingImage = null;
  let drag = null;
  let busy = false;

  const items = group => [...group.querySelectorAll(':scope > [data-ve-item]')];
  const ids = group => items(group).map(item => item.dataset.veKey || item.dataset.veId);
  const same = (a, b) => a.length === b.length && a.every((value, i) => value === b[i]);
  const message = value => { $('#veHint').textContent = value; };
  const dirtyGroups = () => groups.filter(group => !same(ids(group), originals.get(group) || []));
  const hasPending = () => pendingFields.size || pendingImages.size || pendingNav.size || dirtyGroups().length;
  const sync = () => {
    const changed = Boolean(hasPending());
    toolbarSave.disabled = !changed || busy;
    toolbarUndo.disabled = !history.length || busy;
    dock.hidden = !changed && !history.length;
    $('#veDockSave').disabled = !changed || busy;
    $('#veDockUndo').disabled = !history.length || busy;
    $('#veDockStatus').textContent = changed ? 'Есть несохранённые изменения' : 'Изменения отменены';
  };
  const remember = group => originals.set(group, ids(group));
  const api = async (url, options = {}) => {
    const response = await fetch(url, {credentials:'same-origin', headers:{Accept:'application/json','X-CSRF-TOKEN':csrf,...(options.body && !(options.body instanceof FormData) ? {'Content-Type':'application/json'} : {})},...options});
    const data = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Не удалось сохранить изменение');
    return data;
  };
  const paramsOf = target => {
    const owner = target.closest('[data-ve-type]');
    if (!owner) return null;
    return {type:owner.dataset.veType,id:owner.dataset.veId || '',field:target.dataset.veField,key:owner.dataset.veKey || ''};
  };
  const svgPencil = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 17.5V20h2.5L18.7 7.8l-2.5-2.5L4 17.5Zm14-13.9 2.4 2.4-1.3 1.3-2.4-2.4L18 3.6Z" fill="currentColor"/></svg>';
  const addControls = () => {
    groups.forEach(group => {
      items(group).forEach(item => {
        if (item.querySelector(':scope > .ve-drag-handle')) return;
        const handle = document.createElement('span');
        handle.className = 've-drag-handle';
        handle.setAttribute('role','button');
        handle.tabIndex = 0;
        handle.title = 'Перетащить для смены порядка';
        handle.setAttribute('aria-label','Переместить элемент. Стрелки меняют порядок');
        handle.textContent = '⠿';
        item.append(handle);
      });
      if (!originals.has(group)) remember(group);
    });
    document.querySelectorAll('[data-ve-field]:not([hidden])').forEach(target => {
      if (target.matches('button, input') || target.closest('[data-ve-new]')) return;
      const navItem = target.dataset.veField === 'label' ? target.closest('.ve-nav-item') : null;
      const holder = navItem || target;
      if (holder.querySelector(':scope > .ve-pencil')) return;
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 've-pencil';
      button.innerHTML = svgPencil;
      button.setAttribute('aria-label','Редактировать текст');
      button.title = 'Редактировать';
      if (navItem) navItem.insertBefore(button, navItem.querySelector(':scope > .ve-nav-link-edit'));
      else target.append(button);
    });
    document.querySelectorAll('[data-ve-image-type]').forEach(slot => {
      if (slot.querySelector(':scope > .ve-image-action')) return;
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 've-image-action';
      button.innerHTML = svgPencil;
      button.setAttribute('aria-label','Заменить изображение');
      button.title = 'Заменить изображение';
      slot.append(button);
    });
  };
  const cleanContent = target => {
    const copy = target.cloneNode(true);
    copy.querySelectorAll('.ve-pencil').forEach(button => button.remove());
    return copy.innerHTML;
  };
  const fallbackColor = (target, field) => {
    const card = target.closest('.v2-service');
    if (field === 'sticker_color') return getComputedStyle(card).getPropertyValue('--paper-dark').trim() || '#dec2f4';
    if (field === 'tape_color') return getComputedStyle(card.querySelector('.v2-service__tape')).color || '#89d5b0';
    return getComputedStyle($('#v2-advantages')).backgroundColor || '#7845ab';
  };
  const hexColor = color => {
    if (/^#[0-9a-f]{6}$/i.test(color)) return color;
    const match = color.match(/\d+/g);
    return match?.length >= 3 ? '#' + match.slice(0,3).map(value => Number(value).toString(16).padStart(2,'0')).join('') : '#7845ab';
  };
  const displayOf = (target, mode, params) => {
    if (mode === 'color') {
      if (params.field === 'advantages_bg_color') return target.closest('#v2-advantages')?.style.getPropertyValue('--advantages-bg') || '';
      const card = target.closest('.v2-service');
      return card?.style.getPropertyValue(params.field === 'sticker_color' ? '--sticker-base' : '--tape-override') || '';
    }
    if (params.field === 'href') return target.closest('.ve-nav-item').querySelector('a').getAttribute('href');
    return mode === 'rich' ? cleanContent(target) : target.firstChild?.textContent || '';
  };
  const applyDisplay = (target, value, mode, params) => {
    if (mode === 'color') {
      if (params.field === 'advantages_bg_color') {
        const section = $('#v2-advantages');
        if (value) section.style.setProperty('--advantages-bg', value); else section.style.removeProperty('--advantages-bg');
      } else {
        const card = target.closest('.v2-service');
        const property = params.field === 'sticker_color' ? '--sticker-base' : '--tape-override';
        if (value) card.style.setProperty(property, value); else card.style.removeProperty(property);
        if (params.field === 'sticker_color') card.toggleAttribute('data-custom-sticker-color', Boolean(value));
      }
      return;
    }
    if (params.field === 'href') {
      target.closest('.ve-nav-item').querySelector('a').setAttribute('href', value);
      return;
    }
    if (mode === 'rich') target.innerHTML = value;
    else target.textContent = value;
    addControls();
  };
  const fieldValue = () => {
    if (editing.mode === 'color') return $('#veColorDefault')?.checked ? '' : $('#veColorInput').value;
    const source = $('#veModalBody .admin-editor__source:not([hidden])');
    if (source) return source.value;
    const surface = $('#veModalBody .admin-editor__surface');
    return surface ? surface.innerHTML : $('#veModalBody .ve-modal__input')?.value || '';
  };
  const openField = async (target, anchor = target) => {
    if (!enabled || editing || !target?.dataset.veField) return;
    const params = paramsOf(target);
    if (!params) return;
    editing = {target, anchor, params, mode:'plain', version:null};
    modal.classList.remove('ve-popover--rich');
    $('#veModalBody').replaceChildren();
    $('#veModalStatus').textContent = 'Загружаем поле…';
    modal.show();
    placePopover(modal, anchor);
    try {
      const data = await api(root.dataset.fieldUrl + '?' + new URLSearchParams(params));
      if (!editing || editing.target !== target) return;
      editing.mode = data.mode;
      editing.version = data.version;
      const stored = pendingFields.get(target)?.value ?? data.value ?? '';
      const value = stored === '' && data.mode !== 'color'
        ? displayOf(target, data.mode, params).trim()
        : stored;
      modal.classList.toggle('ve-popover--rich', data.mode === 'rich');
      const labels = {home_label:'Заголовок раздела',short_text:'Краткий текст',sticker_color:'Цвет стикера',tape_color:'Цвет липучки',advantages_bg_color:'Фон преимуществ',href:'Адрес ссылки',label:'Название пункта меню'};
      $('#veModalTitle').textContent = labels[params.field] || 'Редактировать текст';
      if (data.mode === 'color') {
        const input = document.createElement('input');
        input.type = 'color';
        input.id = 'veColorInput';
        input.className = 've-modal__color';
        input.value = hexColor(value || fallbackColor(target, params.field));
        const row = document.createElement('div');
        row.className = 've-modal__color-row';
        row.append(input);
        const label = document.createElement('label');
        label.textContent = 'Автоматический цвет';
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.id = 'veColorDefault';
        checkbox.checked = !value;
        label.prepend(checkbox);
        row.append(label);
        input.addEventListener('input', () => { checkbox.checked = false; });
        $('#veModalBody').append(row);
      } else {
        const input = data.mode === 'rich' || value.length > 100 || (params.field === 'hero_text' && value.length > 35) ? document.createElement('textarea') : document.createElement('input');
        if (data.mode === 'rich') input.dataset.richEditor = '';
        input.className = 've-modal__input';
        input.value = value;
        input.setAttribute('aria-label', $('#veModalTitle').textContent);
        if (input.tagName === 'TEXTAREA') input.rows = data.mode === 'rich' ? 6 : 3;
        $('#veModalBody').append(input);
        if (data.mode !== 'rich') input.focus();
      }
      $('#veModalStatus').textContent = '';
      requestAnimationFrame(() => placePopover(modal, anchor));
    } catch (error) {
      $('#veModalStatus').textContent = error.message;
      requestAnimationFrame(() => placePopover(modal, anchor));
    }
  };
  const closeField = () => {
    editing = null;
    modal.close();
    $('#veModalBody').replaceChildren();
    $('#veModalStatus').textContent = '';
  };
  const applyField = () => {
    if (!editing || !$('#veModalBody').children.length) return false;
    const {target,params,mode,version} = editing;
    const value = fieldValue().trim();
    if (!value && !['sticker_color','tape_color','advantages_bg_color','hero_text','short_text','name'].includes(params.field)) {
      $('#veModalStatus').textContent = 'Поле не может быть пустым';
      return false;
    }
    const previous = pendingFields.get(target);
    history.push({kind:'field',target,params,mode,before:displayOf(target,mode,params),previous});
    pendingFields.set(target,{params,mode,version,value});
    applyDisplay(target,value,mode,params);
    closeField();
    message('Сохраняем правку…');
    sync();
    return true;
  };
  const imageDisplay = slot => slot.matches('.v2-hero') ? slot.style.backgroundImage : (slot.querySelector('img')?.getAttribute('src') || '');
  const applyImage = (slot, url) => {
    if (slot.matches('.v2-hero')) slot.style.backgroundImage = url;
    else {
      const image = slot.querySelector('img');
      if (image) image.src = url;
      if (slot.matches('.v2-gallery__item')) slot.dataset.full = url;
    }
  };
  const openImage = (slot, anchor = slot) => {
    if (!enabled || editingImage) return;
    editingImage = {slot,anchor,file:null,previewUrl:null};
    $('#veImageFile').value = '';
    $('#veImageStatus').textContent = '';
    const current = imageDisplay(slot);
    const preview = $('#veImagePreview');
    preview.hidden = !current || slot.matches('.v2-hero');
    if (!preview.hidden) preview.src = current;
    imageModal.show();
    requestAnimationFrame(() => placePopover(imageModal, anchor));
  };
  const closeImage = () => {
    if (editingImage?.previewUrl) URL.revokeObjectURL(editingImage.previewUrl);
    editingImage = null;
    imageModal.close();
  };
  const chooseImage = file => {
    if (!editingImage || !file) return;
    if (!file.type.startsWith('image/') || file.size > 12 * 1024 * 1024) {
      $('#veImageStatus').textContent = 'Выберите изображение до 12 МБ';
      return;
    }
    if (editingImage.previewUrl) URL.revokeObjectURL(editingImage.previewUrl);
    editingImage.file = file;
    editingImage.previewUrl = URL.createObjectURL(file);
    const preview = $('#veImagePreview');
    preview.src = editingImage.previewUrl;
    preview.hidden = false;
    $('#veImageStatus').textContent = file.name;
    requestAnimationFrame(() => placePopover(imageModal, editingImage?.anchor));
  };
  const applyImageStage = () => {
    if (!editingImage?.file) { $('#veImageStatus').textContent = 'Сначала выберите изображение'; return false; }
    const {slot,file} = editingImage;
    const previous = pendingImages.get(slot);
    const old = imageDisplay(slot);
    const previewUrl = URL.createObjectURL(file);
    history.push({kind:'image',slot,before:old,previous});
    pendingImages.set(slot,{file,previewUrl,params:{type:slot.dataset.veImageType,id:slot.dataset.veImageId || '',field:slot.dataset.veImageField}});
    applyImage(slot,slot.matches('.v2-hero') ? 'url("' + previewUrl + '")' : previewUrl);
    closeImage();
    message('Сохраняем изображение…');
    sync();
    return true;
  };
  const createNav = () => {
    const label = $('#veNavLabel').value.trim();
    const href = $('#veNavHref').value.trim();
    if (!label || !/^(#[A-Za-z0-9_-]+|\/(?!\/)[^\s]*|https?:\/\/[^\s]+)$/i.test(href)) {
      $('#veNavStatus').textContent = 'Укажите название и адрес вида #секция, /путь или https://...';
      return;
    }
    const group = $('#navMenu');
    const row = document.createElement('div');
    row.className = 've-nav-item';
    row.dataset.veItem = 'nav_links';
    row.dataset.veType = 'nav_link';
    row.dataset.veId = 'new_' + Date.now();
    row.dataset.veNew = '1';
    const link = document.createElement('a');
    link.href = href;
    const labelNode = document.createElement('span');
    labelNode.dataset.veField = 'label';
    labelNode.textContent = label;
    link.append(labelNode);
    row.append(link);
    const edit = document.createElement('button');
    edit.type = 'button';
    edit.className = 've-nav-link-edit';
    edit.dataset.veField = 'href';
    edit.title = 'Изменить ссылку';
    edit.setAttribute('aria-label','Изменить ссылку пункта ' + label);
    edit.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    row.append(edit);
    group.insertBefore(row,group.querySelector('[data-ve-add-nav]'));
    pendingNav.set(row,{label,href});
    history.push({kind:'create-nav',row});
    addControls();
    navModal.close();
    message('Пункт меню добавлен в черновик. Нажмите «Сохранить».');
    sync();
  };
  const restoreOrder = (group, before) => {
    const add = group.querySelector(':scope > [data-ve-add-nav]');
    before.forEach(item => add ? group.insertBefore(item, add) : group.append(item));
  };
  const undoLast = () => {
    const step = history.pop();
    if (!step) return;
    if (step.kind === 'field') {
      if (step.previous) pendingFields.set(step.target,step.previous); else pendingFields.delete(step.target);
      applyDisplay(step.target,step.before,step.mode,step.params);
    } else if (step.kind === 'image') {
      const current = pendingImages.get(step.slot);
      if (current?.previewUrl && current !== step.previous) URL.revokeObjectURL(current.previewUrl);
      if (step.previous) pendingImages.set(step.slot,step.previous); else pendingImages.delete(step.slot);
      applyImage(step.slot,step.before);
    } else if (step.kind === 'order') {
      restoreOrder(step.group,step.before);
    } else if (step.kind === 'create-nav') {
      pendingNav.delete(step.row);
      step.row.remove();
    }
    message('Последнее изменение отменено.');
    sync();
  };
  const toggleMode = value => {
    if (!value) while (history.length) undoLast();
    enabled = value;
    document.body.classList.toggle('ve-active',enabled);
    $('#veToggle').classList.toggle('is-active',enabled);
    $('#veToggle').setAttribute('aria-checked',String(enabled));
    toolbarSave.hidden = !enabled;
    toolbarUndo.hidden = !enabled;
    $('#veSlidesButton').hidden = !enabled;
    $('#veMenuButton').hidden = !enabled;
    if (!enabled) $('#veSlides').hidden = true;
    if (enabled) addControls();
    message(enabled ? 'Нажмите на карандаш или перетащите элемент за ручку' : 'Включите режим, чтобы править страницу');
    sync();
  };
  const moveItem = (item,next) => {
    if (!item || next === item) return;
    const group = item.parentElement;
    if (next) group.insertBefore(item,next);
    else {
      const add = group.querySelector(':scope > [data-ve-add-nav]');
      if (add) group.insertBefore(item,add); else group.append(item);
    }
    sync();
  };
  const stopDrag = event => {
    if (!drag || (event?.pointerId != null && event.pointerId !== drag.id)) return;
    const {item,group,before,ghost} = drag;
    item.classList.remove('ve-dragging');
    ghost.remove();
    drag = null;
    if (!same(before.map(node => node.dataset.veKey || node.dataset.veId),ids(group))) {
      history.push({kind:'order',group,before});
      message('Порядок изменён. Сохраните его в плавающей панели.');
      sync();
    }
  };
  const saveAll = async () => {
    if (!hasPending() || busy) return;
    busy = true;
    sync();
    message('Сохраняем изменения…');
    try {
      for (const [target,entry] of pendingFields) {
        const data = await api(root.dataset.saveUrl,{method:'PATCH',body:JSON.stringify({...entry.params,value:entry.value,version:entry.version})});
        entry.version = data.version;
        if (entry.mode !== 'color' && entry.params.field !== 'href') applyDisplay(target,data.value,entry.mode,entry.params);
      }
      for (const [slot,entry] of pendingImages) {
        if (entry.persistedUrl) continue;
        const form = new FormData();
        form.append('type',entry.params.type);
        form.append('id',entry.params.id);
        form.append('field',entry.params.field);
        form.append('image',entry.file);
        const data = await api(root.dataset.imageUrl,{method:'POST',body:form});
        entry.persistedUrl = data.url;
        applyImage(slot,slot.matches('.v2-hero') ? 'url("' + data.url + '")' : data.url);
      }
      for (const [row,entry] of pendingNav) {
        const data = await api(root.dataset.navCreateUrl,{method:'POST',body:JSON.stringify(entry)});
        row.dataset.veId = data.id;
        row.removeAttribute('data-ve-new');
      }
      const changed = dirtyGroups();
      if (changed.length) {
        const orders = {};
        changed.forEach(group => { orders[group.dataset.veGroup] = ids(group); });
        await api(root.dataset.reorderUrl,{method:'POST',body:JSON.stringify({orders})});
      }
      groups.forEach(remember);
      pendingImages.forEach(entry => { if (entry.previewUrl) URL.revokeObjectURL(entry.previewUrl); });
      pendingImages.clear();
      pendingFields.clear();
      pendingNav.clear();
      addControls();
      history.length = 0;
      message('Изменения сохранены и записаны в логи.');
    } catch (error) {
      message(error.message + ' Если часть правок уже сохранилась, обновите страницу перед повтором.');
    } finally { busy = false; sync(); }
  };
  document.addEventListener('pointerdown', event => {
    if (editing && !modal.contains(event.target) && !editing.anchor?.contains(event.target)) closeField();
    if (editingImage && !imageModal.contains(event.target) && !editingImage.anchor?.contains(event.target)) closeImage();
  }, true);
  document.addEventListener('pointerdown', event => {
    const handle = event.target.closest('.ve-drag-handle');
    if (!enabled || !handle || drag) return;
    const item = handle.closest('[data-ve-item]');
    event.preventDefault();
    const ghost = document.createElement('div');
    ghost.className = 've-drag-ghost';
    ghost.textContent = item.querySelector('h3,strong,[data-ve-field]')?.textContent?.trim().slice(0,70) || 'Элемент';
    document.body.append(ghost);
    item.classList.add('ve-dragging');
    drag = {item,group:item.parentElement,before:items(item.parentElement),ghost,id:event.pointerId};
    ghost.style.left = event.clientX + 'px';
    ghost.style.top = event.clientY + 'px';
    handle.setPointerCapture?.(event.pointerId);
  });
  document.addEventListener('pointermove', event => {
    if (!drag || event.pointerId !== drag.id) return;
    event.preventDefault();
    drag.ghost.style.left = event.clientX + 'px';
    drag.ghost.style.top = event.clientY + 'px';
    const over = document.elementFromPoint(event.clientX,event.clientY)?.closest('[data-ve-item]');
    if (!over || over === drag.item || over.parentElement !== drag.group) return;
    const rect = over.getBoundingClientRect();
    const vertical = rect.height > rect.width || drag.group.scrollWidth <= drag.group.clientWidth + 8;
    const before = vertical ? event.clientY < rect.top + rect.height/2 : event.clientX < rect.left + rect.width/2;
    moveItem(drag.item,before ? over : over.nextElementSibling);
  });
  document.addEventListener('pointerup',stopDrag);
  document.addEventListener('pointercancel',stopDrag);
  document.addEventListener('keydown', event => {
    if (!enabled || !event.target.matches('.ve-drag-handle')) return;
    if (!['ArrowUp','ArrowLeft','ArrowDown','ArrowRight'].includes(event.key)) return;
    event.preventDefault();
    const item = event.target.closest('[data-ve-item]');
    const group = item.parentElement;
    const before = items(group);
    const index = before.indexOf(item);
    const offset = ['ArrowUp','ArrowLeft'].includes(event.key) ? -1 : 1;
    const target = before[index+offset];
    if (!target) return;
    moveItem(item,offset < 0 ? target : target.nextElementSibling);
    history.push({kind:'order',group,before});
    item.querySelector(':scope > .ve-drag-handle')?.focus();
    sync();
  });
  document.addEventListener('click', event => {
    if (!enabled) return;
    const pencil = event.target.closest('.ve-pencil');
    if (pencil) {
      event.preventDefault(); event.stopPropagation();
      openField(pencil.closest('[data-ve-field]') || pencil.closest('.ve-nav-item')?.querySelector('[data-ve-field="label"]'), pencil);
      return;
    }
    const imageButton = event.target.closest('.ve-image-action');
    if (imageButton) {
      event.preventDefault(); event.stopPropagation();
      openImage(imageButton.closest('[data-ve-image-type]'), imageButton);
      return;
    }
    const colorOrLink = event.target.closest('.ve-color-button, .ve-nav-link-edit');
    if (colorOrLink) {
      event.preventDefault(); event.stopPropagation();
      openField(colorOrLink, colorOrLink);
      return;
    }
    const full = event.target.closest('[data-ve-edit-full]');
    if (full) {
      event.preventDefault(); event.stopPropagation();
      openField(full.parentElement.querySelector('[data-ve-field="text"]'), full);
      return;
    }
    if (event.target.closest('[data-ve-add-nav]')) {
      event.preventDefault(); event.stopPropagation();
      $('#veNavLabel').value = '';
      $('#veNavHref').value = '';
      $('#veNavStatus').textContent = '';
      navModal.showModal();
      return;
    }
    const add = event.target.closest('.ve-add[href]');
    if (add && hasPending()) {
      event.preventDefault();
      message('Сначала сохраните или отмените изменения перед переходом в форму добавления.');
      return;
    }
    if (event.target.closest('[data-ve-item="galleries"], [data-ve-item="socials"], .ve-nav-item a')) {
      event.preventDefault(); event.stopPropagation();
    }
  },true);
  $('#veToggle').addEventListener('click',() => toggleMode(!enabled));
  $('#veSlidesButton').addEventListener('click',() => { $('#veSlides').hidden = !$('#veSlides').hidden; });
  $('#veMenuButton').addEventListener('click',() => {
    if (matchMedia('(max-width: 992px)').matches) $('#navToggle')?.click();
    $('#navMenu')?.scrollIntoView({behavior:'smooth',block:'nearest'});
  });
  toolbarSave.addEventListener('click',saveAll);
  $('#veDockSave').addEventListener('click',saveAll);
  toolbarUndo.addEventListener('click',undoLast);
  $('#veDockUndo').addEventListener('click',undoLast);
  $('#veFieldSave').addEventListener('click',async () => { if (applyField()) await saveAll(); });
  $('#veFieldCancel').addEventListener('click',closeField);
  $('#veClose').addEventListener('click',closeField);
  modal.addEventListener('cancel',event => {event.preventDefault();closeField();});
  $('#veImageFile').addEventListener('change',event => chooseImage(event.target.files?.[0]));
  $('#veImageDrop').addEventListener('dragover',event => {event.preventDefault();event.currentTarget.classList.add('is-dragover');});
  $('#veImageDrop').addEventListener('dragleave',event => event.currentTarget.classList.remove('is-dragover'));
  $('#veImageDrop').addEventListener('drop',event => {
    event.preventDefault();event.currentTarget.classList.remove('is-dragover');
    chooseImage([...event.dataTransfer.files].find(file => file.type.startsWith('image/')));
  });
  $('#veImageApply').addEventListener('click',async () => { if (applyImageStage()) await saveAll(); });
  $('#veImagePreview').addEventListener('load',() => placePopover(imageModal, editingImage?.anchor));
  window.addEventListener('resize',() => {
    placePopover(modal, editing?.anchor);
    placePopover(imageModal, editingImage?.anchor);
  });
  imageModal.querySelectorAll('[data-ve-image-close]').forEach(button => button.addEventListener('click',closeImage));
  imageModal.addEventListener('cancel',event => {event.preventDefault();closeImage();});
  $('#veNavCreate').addEventListener('click',createNav);
  navModal.querySelectorAll('[data-ve-nav-close]').forEach(button => button.addEventListener('click',() => navModal.close()));
  navModal.addEventListener('cancel',event => {event.preventDefault();navModal.close();});
  if ($('#v2-gallery-grid')) new MutationObserver(addControls).observe($('#v2-gallery-grid'),{childList:true});
  addControls();
  toggleMode(root.dataset.startActive === '1');
})();
