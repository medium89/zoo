document.addEventListener('DOMContentLoaded', () => {
  if (!window.ClassicEditor) {
    console.error('CKEditor not found on page');
    return;
  }

  const editors = document.querySelectorAll('textarea.wysiwyg, textarea.js-wysiwyg, textarea.wysiwyg-excerpt');
  editors.forEach((el) => {
    if (el.dataset.ckeditorAttached || el.dataset.editorCustom === '1') return;
    const minHeight = el.dataset.editorHeight ? parseInt(el.dataset.editorHeight, 10) : (el.classList.contains('wysiwyg-excerpt') ? 200 : 320);
    ClassicEditor.create(el, {
      toolbar: [
        'undo', 'redo', '|',
        'heading', '|',
        'bold', 'italic', '|',
        'link', '|',
        'bulletedList', 'numberedList', '|',
        'blockQuote', 'insertTable'
      ],
      heading: {
        options: [
          { model: 'paragraph', title: 'Обычный', class: 'ck-heading_paragraph' },
          { model: 'heading2', view: 'h2', title: 'Заголовок 2', class: 'ck-heading_heading2' },
          { model: 'heading3', view: 'h3', title: 'Заголовок 3', class: 'ck-heading_heading3' }
        ]
      }
    }).then((editor) => {
      el.dataset.ckeditorAttached = '1';
      el.classList.add('ck-initialized');
      el.closest('.ck-editor')?.classList.add('w-100');
      editor.ui.view.editable.element.style.minHeight = `${minHeight}px`;
    }).catch((error) => {
      console.error('CKEditor init error', error);
    });
  });
});
