document.addEventListener('DOMContentLoaded', () => {
  tinymce.init({
    selector: 'textarea.wysiwyg',
    menubar: false,
    plugins: 'link lists code emoticons',
    toolbar: 'undo redo | bold italic underline | fontsizeselect | bullist numlist | link | emoticons | code',
    fontsize_formats: '8pt 10pt 12pt 14pt 18pt 24pt 36pt',
    language: 'ru',
    height: 300,
    entity_encoding: 'raw',
    emoticons_database: 'emoji',
    license_key: 'gpl',
  });
});
