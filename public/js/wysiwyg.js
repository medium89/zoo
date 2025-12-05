document.addEventListener('DOMContentLoaded', () => {
  if (!window.ClassicEditor) {
    console.error('CKEditor not found on page');
    return;
  }

  document.querySelectorAll('textarea.wysiwyg').forEach((el) => {
    if (el.dataset.ckeditorAttached) return;
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
    }).catch((error) => {
      console.error('CKEditor init error', error);
    });
  });
});
