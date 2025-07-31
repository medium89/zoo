import './bootstrap';
import tinymce from 'tinymce';
import 'tinymce/themes/silver';
import 'tinymce/icons/default';
import 'tinymce/plugins/link';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/code';
import 'tinymce/plugins/emoticons';
import 'tinymce/plugins/emoticons/js/emojis';
// ВНИМАНИЕ: Не рекомендуется импортировать из public (см. ниже)
// import '../../public/js/main.js'; // ❌

window.tinymce = tinymce;

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
    license_key: 'gpl', // для использования Open Source
  });
});