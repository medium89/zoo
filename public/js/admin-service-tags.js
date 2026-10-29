(() => {
  'use strict';

  const selector = '[data-service-tag-editor]';

  function init(editor) {
    if (editor.dataset.serviceTagReady === '1') return;
    editor.dataset.serviceTagReady = '1';

    const input = editor.querySelector('[data-service-tag-input]');
    const addButton = editor.querySelector('[data-service-tag-add]');
    const list = editor.querySelector('[data-service-tag-list]');
    const valueField = editor.querySelector('[data-service-tag-value]');
    const status = editor.querySelector('[data-service-tag-status]');
    if (!input || !addButton || !list || !valueField) return;

    const tags = valueField.value.split(/\r\n|\r|\n/u).map(value => value.trim()).filter(Boolean);
    let editingIndex = -1;
    let drag = null;

    const sync = () => { valueField.value = tags.join('\n'); };
    const resetInput = () => {
      input.value = '';
      editingIndex = -1;
      addButton.textContent = 'Добавить тег';
      status.textContent = '';
    };
    const iconButton = (icon, label, action) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'service-tag-editor__icon';
      button.dataset.action = action;
      button.title = label;
      button.setAttribute('aria-label', label);
      const glyph = document.createElement('i');
      glyph.className = 'fa fa-' + icon;
      glyph.setAttribute('aria-hidden', 'true');
      button.append(glyph);
      return button;
    };
    const render = () => {
      list.replaceChildren();
      tags.forEach((tag, index) => {
        const chip = document.createElement('div');
        chip.className = 'service-tag-editor__chip';
        if (index === editingIndex) chip.classList.add('is-editing');
        chip.dataset.index = String(index);

        const handle = iconButton('grip-vertical', 'Перетащить тег «' + tag + '». Стрелки перемещают тег по списку', 'drag');
        handle.classList.add('service-tag-editor__drag');
        handle.setAttribute('aria-keyshortcuts', 'ArrowUp ArrowDown ArrowLeft ArrowRight');

        const label = document.createElement('span');
        label.className = 'service-tag-editor__label';
        label.textContent = tag;

        const actions = document.createElement('span');
        actions.className = 'service-tag-editor__actions';
        actions.append(
          iconButton('pen', 'Изменить тег «' + tag + '»', 'edit'),
          iconButton('xmark', 'Удалить тег «' + tag + '»', 'remove')
        );
        chip.append(handle, label, actions);
        list.append(chip);
      });
      sync();
    };
    const saveInput = () => {
      const tag = input.value.trim().replace(/\s+/gu, ' ');
      if (!tag) {
        status.textContent = 'Введите текст тега.';
        input.focus();
        return false;
      }
      const duplicate = tags.findIndex((existing, index) =>
        index !== editingIndex && existing.toLocaleLowerCase('ru') === tag.toLocaleLowerCase('ru')
      );
      if (duplicate !== -1) {
        status.textContent = 'Такой тег уже добавлен.';
        input.focus();
        return false;
      }
      if (editingIndex === -1) tags.push(tag);
      else tags[editingIndex] = tag;
      resetInput();
      render();
      input.focus();
      return true;
    };
    const moveTag = (from, to) => {
      if (to < 0 || to >= tags.length || from === to) return;
      const [tag] = tags.splice(from, 1);
      tags.splice(to, 0, tag);
      if (editingIndex === from) editingIndex = to;
      else if (from < editingIndex && to >= editingIndex) editingIndex--;
      else if (from > editingIndex && to <= editingIndex) editingIndex++;
      render();
      status.textContent = 'Порядок изменён. Сохраните услугу.';
      list.querySelectorAll('[data-action="drag"]')[to]?.focus();
    };
    const animateReflow = (before) => {
      if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
      list.querySelectorAll('.service-tag-editor__chip').forEach(chip => {
        const old = before.get(chip);
        if (!old || chip === drag.chip) return;
        const now = chip.getBoundingClientRect();
        const dx = old.left - now.left;
        const dy = old.top - now.top;
        if (Math.abs(dx) < 1 && Math.abs(dy) < 1) return;
        chip.animate(
          [{ transform: 'translate(' + dx + 'px, ' + dy + 'px)' }, { transform: 'translate(0, 0)' }],
          { duration: 170, easing: 'ease-out' }
        );
      });
    };
    const positionGhost = event => {
      drag.ghost.style.left = event.clientX - drag.offsetX + 'px';
      drag.ghost.style.top = event.clientY - drag.offsetY + 'px';
    };
    const beginDrag = event => {
      const rect = drag.chip.getBoundingClientRect();
      drag.offsetX = event.clientX - rect.left;
      drag.offsetY = event.clientY - rect.top;
      drag.ghost = drag.chip.cloneNode(true);
      drag.ghost.classList.add('service-tag-editor__ghost');
      drag.ghost.removeAttribute('data-index');
      drag.ghost.style.width = rect.width + 'px';
      drag.ghost.style.height = rect.height + 'px';
      drag.ghost.querySelector('.service-tag-editor__actions')?.remove();
      document.body.append(drag.ghost);
      drag.chip.classList.add('is-dragging');
      drag.previousUserSelect = document.body.style.userSelect;
      document.body.style.userSelect = 'none';
      drag.active = true;
      positionGhost(event);
    };
    const closestChip = (x, y) => {
      const hovered = document.elementFromPoint(x, y)?.closest('.service-tag-editor__chip');
      if (hovered && hovered.parentElement === list && hovered !== drag.chip) return hovered;
      let closest = null;
      let distance = Infinity;
      list.querySelectorAll('.service-tag-editor__chip').forEach(chip => {
        if (chip === drag.chip) return;
        const rect = chip.getBoundingClientRect();
        const dx = x - (rect.left + rect.width / 2);
        const dy = y - (rect.top + rect.height / 2);
        const score = dx * dx + dy * dy;
        if (score < distance) { distance = score; closest = chip; }
      });
      return closest;
    };
    const onPointerMove = event => {
      if (!drag || event.pointerId !== drag.pointerId) return;
      if (!drag.active) {
        if (Math.hypot(event.clientX - drag.startX, event.clientY - drag.startY) < 5) return;
        beginDrag(event);
      }
      event.preventDefault();
      positionGhost(event);
      const target = closestChip(event.clientX, event.clientY);
      if (!target) return;
      const rect = target.getBoundingClientRect();
      const after = event.clientY > rect.bottom || (
        event.clientY >= rect.top && event.clientY <= rect.bottom &&
        event.clientX > rect.left + rect.width / 2
      );
      if (after ? drag.chip.previousElementSibling === target : drag.chip.nextElementSibling === target) return;
      const before = new Map();
      list.querySelectorAll('.service-tag-editor__chip').forEach(chip => {
        chip.getAnimations().forEach(animation => animation.cancel());
        before.set(chip, chip.getBoundingClientRect());
      });
      list.insertBefore(drag.chip, after ? target.nextSibling : target);
      animateReflow(before);
    };
    const finishDrag = event => {
      if (!drag || event.pointerId !== drag.pointerId) return;
      const wasActive = drag.active;
      if (wasActive) {
        event.preventDefault();
        const order = [...list.querySelectorAll('.service-tag-editor__chip')].map(chip => Number(chip.dataset.index));
        const oldTags = [...tags];
        tags.splice(0, tags.length, ...order.map(index => oldTags[index]));
        editingIndex = editingIndex === -1 ? -1 : order.indexOf(editingIndex);
      }
      drag.ghost?.remove();
      drag.chip.classList.remove('is-dragging');
      if (wasActive) document.body.style.userSelect = drag.previousUserSelect;
      drag = null;
      document.removeEventListener('pointermove', onPointerMove);
      document.removeEventListener('pointerup', finishDrag);
      document.removeEventListener('pointercancel', cancelDrag);
      if (wasActive) {
        render();
        status.textContent = 'Порядок изменён. Сохраните услугу.';
      }
    };
    const cancelDrag = event => {
      if (!drag || event.pointerId !== drag.pointerId) return;
      drag.ghost?.remove();
      if (drag.active) document.body.style.userSelect = drag.previousUserSelect;
      drag = null;
      document.removeEventListener('pointermove', onPointerMove);
      document.removeEventListener('pointerup', finishDrag);
      document.removeEventListener('pointercancel', cancelDrag);
      render();
    };

    addButton.addEventListener('click', saveInput);
    input.addEventListener('keydown', event => {
      if (event.key === 'Enter' && !event.isComposing) {
        event.preventDefault();
        saveInput();
      } else if (event.key === 'Escape' && editingIndex !== -1) {
        resetInput();
        render();
      }
    });
    input.addEventListener('input', () => { status.textContent = ''; });
    list.addEventListener('click', event => {
      const action = event.target.closest('[data-action]')?.dataset.action;
      const chip = event.target.closest('[data-index]');
      if (!action || !chip) return;
      const index = Number(chip.dataset.index);
      if (action === 'edit') {
        editingIndex = index;
        input.value = tags[index];
        addButton.textContent = 'Сохранить тег';
        status.textContent = '';
        render();
        input.focus();
        input.select();
      } else if (action === 'remove') {
        tags.splice(index, 1);
        if (index === editingIndex) resetInput();
        else if (index < editingIndex) editingIndex--;
        render();
      }
    });
    list.addEventListener('keydown', event => {
      if (!event.target.matches('[data-action="drag"]')) return;
      const chip = event.target.closest('[data-index]');
      const index = Number(chip.dataset.index);
      if (event.key === 'ArrowUp' || event.key === 'ArrowLeft') {
        event.preventDefault();
        moveTag(index, index - 1);
      } else if (event.key === 'ArrowDown' || event.key === 'ArrowRight') {
        event.preventDefault();
        moveTag(index, index + 1);
      }
    });
    list.addEventListener('pointerdown', event => {
      if (!event.target.closest('[data-action="drag"]') || (event.pointerType === 'mouse' && event.button !== 0)) return;
      const chip = event.target.closest('[data-index]');
      if (!chip) return;
      drag = { chip, pointerId: event.pointerId, startX: event.clientX, startY: event.clientY, active: false };
      document.addEventListener('pointermove', onPointerMove, { passive: false });
      document.addEventListener('pointerup', finishDrag);
      document.addEventListener('pointercancel', cancelDrag);
    });
    editor.closest('form')?.addEventListener('submit', () => {
      if (input.value.trim()) saveInput();
      sync();
    }, true);
    render();
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
