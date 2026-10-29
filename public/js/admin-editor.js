(() => {
  'use strict';

  const selector = 'textarea[data-rich-editor]';
  const buttons = [
    ['undo', '↶', 'Отменить'], ['redo', '↷', 'Повторить'],
    ['bold', 'Ж', 'Жирный'], ['italic', 'К', 'Курсив'], ['underline', 'Ч', 'Подчёркнутый'],
    ['insertUnorderedList', '• Список', 'Маркированный список'],
    ['insertOrderedList', '1. Список', 'Нумерованный список'],
    ['justifyLeft', '≡', 'По левому краю'], ['justifyCenter', '≡', 'По центру'],
    ['link', 'Ссылка', 'Добавить ссылку'], ['unlink', 'Убрать ссылку', 'Удалить ссылку'],
    ['removeFormat', 'Очистить', 'Очистить форматирование'],
  ];

  function makeButton(action, label, title) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'admin-editor__button';
    button.dataset.action = action;
    button.textContent = label;
    button.title = title;
    button.setAttribute('aria-label', title);
    return button;
  }

  function initialSelection(surface) {
    const selection = window.getSelection();
    if (!selection || !selection.rangeCount || !surface.contains(selection.anchorNode)) {
      surface.focus();
      return;
    }
    surface.focus();
  }

  function init(textarea) {
    if (textarea.dataset.richEditorReady === '1') return;
    textarea.dataset.richEditorReady = '1';

    const wrapper = document.createElement('div');
    wrapper.className = 'admin-editor';
    const toolbar = document.createElement('div');
    toolbar.className = 'admin-editor__toolbar';
    toolbar.setAttribute('role', 'toolbar');
    toolbar.setAttribute('aria-label', 'Форматирование текста');

    const format = document.createElement('select');
    format.className = 'admin-editor__format';
    format.setAttribute('aria-label', 'Формат абзаца');
    [['p', 'Абзац'], ['h2', 'Заголовок 2'], ['h3', 'Заголовок 3'], ['blockquote', 'Цитата']]
      .forEach(([value, label]) => {
        const option = document.createElement('option');
        option.value = value;
        option.textContent = label;
        format.append(option);
      });
    toolbar.append(format);

    buttons.forEach(([action, label, title]) => toolbar.append(makeButton(action, label, title)));
    if (textarea.dataset.editorUploadUrl) {
      toolbar.append(makeButton('image', 'Изображение', 'Загрузить изображение'));
    }
    toolbar.append(makeButton('table', 'Таблица', 'Вставить таблицу 2 × 2'));
    toolbar.append(makeButton('source', '</>', 'Редактировать HTML'));
    wrapper.append(toolbar);

    const surface = document.createElement('div');
    surface.className = 'admin-editor__surface';
    surface.contentEditable = 'true';
    surface.setAttribute('role', 'textbox');
    surface.setAttribute('aria-multiline', 'true');
    surface.setAttribute('aria-label', textarea.closest('.mb-3, .col-12')?.querySelector('label')?.textContent?.trim() || 'Текст');
    surface.style.minHeight = (parseInt(textarea.dataset.editorHeight, 10) || (textarea.name === 'content' ? 460 : 220)) + 'px';
    surface.innerHTML = textarea.value;
    wrapper.append(surface);

    const source = document.createElement('textarea');
    source.className = 'admin-editor__source';
    source.setAttribute('aria-label', 'HTML-код');
    source.style.minHeight = surface.style.minHeight;
    source.hidden = true;
    wrapper.append(source);

    const status = document.createElement('div');
    status.className = 'admin-editor__status';
    status.setAttribute('role', 'status');
    wrapper.append(status);

    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = 'image/jpeg,image/png,image/webp,image/gif';
    fileInput.hidden = true;
    wrapper.append(fileInput);

    textarea.classList.add('admin-editor__original');
    textarea.after(wrapper);
    let sourceMode = false;
    let savedRange = null;
    const sync = () => {
      textarea.value = sourceMode ? source.value : surface.innerHTML;
      textarea.dispatchEvent(new Event('input', { bubbles: true }));
    };
    const saveSelection = () => {
      const selection = window.getSelection();
      if (selection?.rangeCount && surface.contains(selection.anchorNode)) {
        savedRange = selection.getRangeAt(0).cloneRange();
      }
    };
    const restoreSelection = () => {
      surface.focus();
      if (savedRange && surface.contains(savedRange.commonAncestorContainer)) {
        const selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(savedRange);
      }
    };
    const command = (name, value = null) => {
      initialSelection(surface);
      document.execCommand(name, false, value);
      sync();
      saveSelection();
    };

    surface.addEventListener('input', sync);
    surface.addEventListener('keyup', saveSelection);
    surface.addEventListener('mouseup', saveSelection);
    surface.addEventListener('paste', event => {
      event.preventDefault();
      const plain = event.clipboardData?.getData('text/plain') || '';
      document.execCommand('insertText', false, plain);
      sync();
    });
    source.addEventListener('input', sync);
    textarea.form?.addEventListener('submit', sync);
    format.addEventListener('change', () => {
      command('formatBlock', format.value);
      format.value = 'p';
    });
    toolbar.addEventListener('mousedown', event => {
      if (event.target.closest('button')) event.preventDefault();
    });
    toolbar.addEventListener('click', event => {
      const button = event.target.closest('button[data-action]');
      if (!button) return;
      const action = button.dataset.action;
      if (action === 'source') {
        if (sourceMode) {
          surface.innerHTML = source.value;
          source.hidden = true;
          surface.hidden = false;
          toolbar.classList.remove('admin-editor__toolbar--source');
          sourceMode = false;
          surface.focus();
        } else {
          source.value = surface.innerHTML;
          surface.hidden = true;
          source.hidden = false;
          toolbar.classList.add('admin-editor__toolbar--source');
          sourceMode = true;
          source.focus();
        }
        button.setAttribute('aria-pressed', String(sourceMode));
        sync();
        return;
      }
      if (sourceMode) return;
      if (action === 'image') {
        saveSelection();
        fileInput.click();
        return;
      }
      if (action === 'link') {
        saveSelection();
        const raw = window.prompt('Адрес ссылки (https://, http://, mailto: или /):');
        if (!raw) return;
        const url = raw.trim();
        if (!/^(https?:\/\/|mailto:|\/(?!\/))/i.test(url)) {
          status.textContent = 'Используйте ссылку с https://, http://, mailto: или /';
          return;
        }
        restoreSelection();
        command('createLink', url);
        status.textContent = '';
        return;
      }
      if (action === 'table') {
        restoreSelection();
        command('insertHTML', '<table><tbody><tr><td>Ячейка</td><td>Ячейка</td></tr><tr><td>Ячейка</td><td>Ячейка</td></tr></tbody></table><p><br></p>');
        return;
      }
      command(action);
    });
    if (textarea.dataset.editorUploadUrl) {
      surface.addEventListener('dragover', event => {
        if (event.dataTransfer?.types.includes('Files')) {
          event.preventDefault();
          surface.classList.add('is-image-drag');
        }
      });
      surface.addEventListener('dragleave', () => surface.classList.remove('is-image-drag'));
      surface.addEventListener('drop', event => {
        surface.classList.remove('is-image-drag');
        const file = Array.from(event.dataTransfer?.files || []).find(item => item.type.startsWith('image/'));
        if (!file) return;
        event.preventDefault();
        saveSelection();
        const transfer = new DataTransfer();
        transfer.items.add(file);
        fileInput.files = transfer.files;
        fileInput.dispatchEvent(new Event('change'));
      });
    }
    fileInput.addEventListener('change', async () => {
      const file = fileInput.files?.[0];
      if (!file) return;
      status.textContent = 'Загружаем изображение…';
      const data = new FormData();
      data.append('file', file);
      const csrf = document.querySelector('meta[name="csrf-token"]')?.content
        || textarea.form?.querySelector('input[name="_token"]')?.value || '';
      try {
        const response = await fetch(textarea.dataset.editorUploadUrl, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
          credentials: 'same-origin',
          body: data
        });
        const result = await response.json();
        if (!response.ok || !result.url) throw new Error(result.message || 'Ошибка загрузки');
        restoreSelection();
        command('insertImage', result.url);
        status.textContent = 'Изображение добавлено';
      } catch (error) {
        status.textContent = 'Не удалось загрузить изображение: ' + error.message;
      } finally {
        fileInput.value = '';
      }
    });
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
