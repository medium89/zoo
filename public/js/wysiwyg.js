document.addEventListener('DOMContentLoaded', () => {
  if (!window.tinymce) {
    console.error('TinyMCE not found on page');
    return;
  }

  const editors = document.querySelectorAll('textarea.wysiwyg, textarea.js-wysiwyg, textarea.wysiwyg-excerpt');
  editors.forEach((el) => {
    if (el.dataset.editorCustom === '1' || el.dataset.tinymceAttached) return;
    const height = el.dataset.editorHeight
      ? parseInt(el.dataset.editorHeight, 10)
      : (el.classList.contains('wysiwyg-excerpt') ? 200 : 320);

    tinymce.init({
      target: el,
      menubar: false,
      branding: false,
      height,
      plugins: 'autoresize link lists table code',
      toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright | bullist numlist outdent indent | link table | removeformat code',
      autoresize_bottom_margin: 16,
      promotion: false,
      content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; font-size: 14px; }',
      setup(editor) {
        editor.on('init', () => {
          el.dataset.tinymceAttached = '1';
        });
      },
    });
  });
});
