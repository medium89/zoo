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
    content_style: `
      body { font-family:Helvetica,Arial,sans-serif; font-size:14px }
      .mce-content-body { font-size: inherit; }
      span[style*="font-size: 8pt"] { font-size: 8pt !important; }
      span[style*="font-size: 10pt"] { font-size: 10pt !important; }
      span[style*="font-size: 12pt"] { font-size: 12pt !important; }
      span[style*="font-size: 14pt"] { font-size: 14pt !important; }
      span[style*="font-size: 18pt"] { font-size: 18pt !important; }
      span[style*="font-size: 24pt"] { font-size: 24pt !important; }
      span[style*="font-size: 36pt"] { font-size: 36pt !important; }
    `
  });
});
