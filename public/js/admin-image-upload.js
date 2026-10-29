(() => {
  'use strict';

  const imageNames = new Set(['image', 'text_bg', 'sticker_doodle', 'cover', 'hero_image', 'images[]', 'photos[]', 'photos_upload[]', 'photo']);
  const selector = 'input[type="file"]';

  function updatePercent(input) {
    if (!input.matches('.js-scale, .js-quality')) return;
    const marker = input.matches('.js-scale') ? '.js-scale-val' : '.js-quality-val';
    const output = input.closest('.col-md-6')?.querySelector(marker) || input.parentElement?.querySelector(marker);
    if (output) output.textContent = input.value;
  }
  document.addEventListener('input', event => {
    if (event.target instanceof HTMLInputElement) updatePercent(event.target);
  });

  function labelFor(input, field) {
    if (input.dataset.uploadLabel) return input.dataset.uploadLabel;
    const labels = Array.from(field?.querySelectorAll('label') || []);
    const label = labels.find(item => item.htmlFor && item.htmlFor === input.id)
      || input.closest('label') || labels[0];
    return label?.textContent.trim().replace(/\s+/g, ' ') || 'Изображение';
  }

  function init(input) {
    if (input.dataset.imageUploadReady === '1' || input.closest('.admin-editor') || input.hidden) return;
    if (!imageNames.has(input.name) && !input.accept.includes('image/')) return;
    input.dataset.imageUploadReady = '1';
    if (!input.accept) input.accept = 'image/*';

    const field = input.closest('.mb-3, .form-group, .map-form-field, .col-12, .col-lg-6, .client-create-fields__wide') || input.parentElement;
    const form = input.closest('form');
    const label = labelFor(input, field);
    const zone = document.createElement('div');
    zone.className = 'admin-upload';
    zone.dataset.uploadName = input.name;
    const drop = document.createElement('div');
    drop.className = 'admin-upload__drop';
    drop.tabIndex = 0;
    drop.setAttribute('role', 'button');
    drop.setAttribute('aria-label', 'Выбрать или перетащить: ' + label);
    drop.innerHTML = '<span class="admin-upload__icon" aria-hidden="true"><i class="fa fa-cloud-arrow-up"></i></span><strong></strong><small></small>';
    drop.querySelector('strong').textContent = label;
    drop.querySelector('small').textContent = 'Перетащите сюда или нажмите, чтобы выбрать' + (input.multiple ? ' несколько изображений' : ' изображение');
    const previews = document.createElement('div');
    previews.className = 'admin-upload__previews';
    const message = document.createElement('div');
    message.className = 'admin-upload__message';
    message.setAttribute('role', 'status');
    zone.append(drop, previews, message);
    input.after(zone);
    zone.prepend(input);
    input.classList.add('admin-upload__input');

    const legacy = Array.from(field?.querySelectorAll('[data-upload-old-preview]') || []);
    legacy.forEach(node => { node.hidden = true; });
    const clearButton = Array.from(field?.querySelectorAll('.js-clear-file') || [])
      .find(button => button.dataset.target === '#' + input.id);
    if (clearButton) clearButton.style.display = 'none';
    if (input.dataset.removeField) {
      const checkbox = Array.from(form?.elements || [])
        .find(element => element.name === input.dataset.removeField && element.type === 'checkbox');
      const wrapper = checkbox?.closest('.form-check');
      if (wrapper) wrapper.style.display = 'none';
    }

    const current = [];
    if (input.dataset.currentSrc) {
      current.push({ src: input.dataset.currentSrc, removeField: input.dataset.removeField || null });
    }
    Array.from(form?.querySelectorAll('[data-upload-current-for]') || [])
      .filter(source => source.dataset.uploadCurrentFor === input.name)
      .forEach(source => {
        source.hidden = true;
        source.querySelectorAll('[data-upload-current-src]').forEach(item => {
          current.push({
            src: item.dataset.uploadCurrentSrc,
            deleteUrl: item.dataset.uploadDeleteUrl || null,
            rawValue: item.dataset.uploadRawValue || null
          });
        });
      });

    const thumb = (src, title, onRemove) => {
      const card = document.createElement('div');
      card.className = 'admin-upload__thumb';
      const image = document.createElement('img');
      image.src = src;
      image.alt = title;
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'admin-upload__remove';
      button.title = 'Удалить изображение';
      button.setAttribute('aria-label', 'Удалить изображение');
      button.innerHTML = '<i class="fa fa-xmark" aria-hidden="true"></i>';
      button.addEventListener('click', event => { event.preventDefault(); event.stopPropagation(); onRemove(card); });
      card.append(image, button);
      return card;
    };

    const removeField = fieldName => {
      if (!form || !fieldName) return;
      const existing = Array.from(form.elements).find(el => el.name === fieldName);
      if (existing && existing.type === 'checkbox') existing.checked = true;
      else if (!existing) {
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = fieldName;
        hidden.value = '1';
        hidden.dataset.uploadRemoveFlag = '1';
        form.append(hidden);
      }
    };
    const renderSelected = () => {
      previews.querySelectorAll('[data-selected]').forEach(node => node.remove());
      Array.from(input.files || []).forEach((file, index) => {
        const url = URL.createObjectURL(file);
        const card = thumb(url, file.name, () => {
          const transfer = new DataTransfer();
          Array.from(input.files || []).forEach((candidate, position) => {
            if (position !== index) transfer.items.add(candidate);
          });
          input.files = transfer.files;
          input.dispatchEvent(new Event('change', { bubbles: true }));
        });
        card.dataset.selected = '1';
        card.title = file.name;
        previews.append(card);
        card.querySelector('img').addEventListener('load', () => URL.revokeObjectURL(url), { once: true });
      });
    };
    const renderCurrent = () => {
      current.forEach(item => {
        if (!item.src) return;
        const card = thumb(item.src, 'Загруженное изображение', async () => {
          if (item.removeField) {
            removeField(item.removeField);
            card.remove();
            message.textContent = 'Изображение будет удалено после сохранения.';
          } else if (item.rawValue) {
            const raw = form?.querySelector('[name="photos_raw"]');
            if (raw) raw.value = raw.value.split(/\r\n|\r|\n/).filter(line => line.trim() !== item.rawValue).join('\n');
            card.remove();
            message.textContent = 'Фото будет удалено после сохранения.';
          } else if (item.deleteUrl) {
            const button = card.querySelector('button');
            button.disabled = true;
            try {
              const response = await fetch(item.deleteUrl, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                  'X-Requested-With': 'XMLHttpRequest'
                }
              });
              if (!response.ok) throw new Error();
              card.remove();
              message.textContent = 'Фото удалено.';
            } catch (_) {
              button.disabled = false;
              message.textContent = 'Не удалось удалить фото. Попробуйте ещё раз.';
            }
          }
        });
        card.dataset.current = '1';
        previews.append(card);
      });
    };

    input.addEventListener('admin-image-upload:set-current', event => {
      previews.querySelectorAll('[data-current]').forEach(node => node.remove());
      current.splice(0, current.length, ...Array.from(event.detail || []));
      renderCurrent();
      renderSelected();
    });
    input.addEventListener('change', renderSelected);
    input.addEventListener('invalid', () => zone.classList.add('is-invalid'));
    drop.addEventListener('click', event => { event.preventDefault(); input.click(); });
    drop.addEventListener('keydown', event => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        input.click();
      }
    });
    ['dragenter', 'dragover'].forEach(name => zone.addEventListener(name, event => {
      event.preventDefault();
      zone.classList.add('is-dragging');
    }));
    ['dragleave', 'drop'].forEach(name => zone.addEventListener(name, event => {
      event.preventDefault();
      zone.classList.remove('is-dragging');
    }));
    zone.addEventListener('drop', event => {
      const files = Array.from(event.dataTransfer?.files || []).filter(file => file.type.startsWith('image/'));
      if (!files.length) {
        message.textContent = 'Выберите файлы изображений.';
        return;
      }
      const transfer = new DataTransfer();
      const selected = input.multiple ? [...Array.from(input.files || []), ...files] : files.slice(0, 1);
      selected.forEach(file => transfer.items.add(file));
      input.files = transfer.files;
      zone.classList.remove('is-invalid');
      message.textContent = '';
      input.dispatchEvent(new Event('change', { bubbles: true }));
    });
    renderCurrent();
    renderSelected();
  }

  function start() {
    document.querySelectorAll(selector).forEach(init);
    const observer = new MutationObserver(records => {
      for (const record of records) {
        for (const node of record.addedNodes) {
          if (!(node instanceof Element)) continue;
          if (node.matches(selector)) init(node);
          node.querySelectorAll(selector).forEach(init);
        }
      }
    });
    observer.observe(document.body, { childList: true, subtree: true });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
  else start();
})();
