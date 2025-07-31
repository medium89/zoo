document.addEventListener('DOMContentLoaded', () => {
  tinymce.init({
    selector: 'textarea.wysiwyg',
    menubar: false,
    plugins: 'link lists code emoticons',
    toolbar: 'undo redo | bold italic underline | bullist numlist | link | emoticons | code',
    language: 'ru',
    height: 300,
    entity_encoding: 'raw',
    emoticons_database: 'emoji',
    license_key: 'gpl',
  });
});
