@extends('admin.index')
@section('content')
<h3>Добавить контакт</h3>
@if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<form action="{{ route('admin.socials.store') }}" method="POST">
    @csrf
    <div class="mb-3">
        <label for="icon" class="form-label">Иконка (визуальный выбор)</label>
        <input type="hidden" id="icon" name="icon" required>
        <button type="button" class="btn btn-secondary" id="selectIconButton">Выбрать иконку</button>
        <div id="selectedIconPreview" class="mt-2">
            <span class="text-muted">Иконка не выбрана</span>
        </div>
        <small class="text-muted">Выберите иконку Font Awesome.</small>
    </div>

    <div id="iconPickerModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color: rgba(0,0,0,0.5); z-index:1000; overflow-y: auto;">
        <div style="background-color:#fff; margin: 5% auto; padding:20px; border-radius:8px; width:90%; max-width:800px; position:relative;">
            <h4>Выберите иконку</h4>
            <input type="text" id="iconSearchInput" class="form-control mb-3" placeholder="Поиск иконок...">
            <div id="iconList" style="display:flex; flex-wrap:wrap; max-height:400px; overflow-y:auto; border:1px solid #eee; padding:10px;">
                <!-- Иконки будут загружены сюда -->
            </div>
            <button type="button" class="btn btn-danger mt-3" id="closeIconPickerModal">Закрыть</button>
        </div>
    </div>
    <div class="mb-3">
        <label for="title" class="form-label">Заголовок</label>
        <input type="text" class="form-control" id="title" name="title" required>
    </div>
    <div class="mb-3">
        <label for="link" class="form-label">Ссылка</label>
        <input type="text" class="form-control" id="link" name="link" required>
    </div>
    <div class="mb-3">
        <label for="text" class="form-label">Текст</label>
        <textarea class="form-control wysiwyg" id="text" name="text" rows="4"></textarea>
    </div>
    <div class="mb-3">
        <label for="order" class="form-label">Порядок</label>
        <input type="number" class="form-control" id="order" name="order" value="0">
    </div>
    <button type="submit" class="btn btn-success">Сохранить</button>
    <a href="{{ route('admin.socials.index') }}" class="btn btn-secondary">Назад</a>
</form>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectIconButton = document.getElementById('selectIconButton');
        const iconPickerModal = document.getElementById('iconPickerModal');
        const closeIconPickerModal = document.getElementById('closeIconPickerModal');
        const iconSearchInput = document.getElementById('iconSearchInput');
        const iconList = document.getElementById('iconList');
        const hiddenIconInput = document.getElementById('icon');
        const selectedIconPreview = document.getElementById('selectedIconPreview');

        const icons = [
            'fab fa-whatsapp', 'fab fa-telegram', 'fab fa-vk', 'fab fa-instagram',
            'fab fa-facebook-f', 'fab fa-twitter', 'fab fa-youtube', 'fab fa-linkedin-in',
            'fab fa-pinterest', 'fab fa-github', 'fab fa-discord', 'fab fa-viber',
            'fas fa-phone', 'fas fa-envelope', 'fas fa-map-marker-alt', 'fas fa-globe',
            'fas fa-link', 'fas fa-share-alt', 'fas fa-comments', 'fas fa-heart'
        ];

        function renderIcons(filter = '') {
            iconList.innerHTML = '';
            const filteredIcons = icons.filter(icon => icon.toLowerCase().includes(filter.toLowerCase()));

            filteredIcons.forEach(iconClass => {
                const iconElement = document.createElement('i');
                iconElement.className = `${iconClass} fa-2x m-2 p-2 border rounded`;
                iconElement.style.cursor = 'pointer';
                iconElement.title = iconClass;
                iconElement.addEventListener('click', () => {
                    hiddenIconInput.value = iconClass;
                    selectedIconPreview.innerHTML = `<i class="${iconClass} fa-2x"></i>`;
                    iconPickerModal.style.display = 'none';
                });
                iconList.appendChild(iconElement);
            });
        }

        renderIcons();

        selectIconButton.addEventListener('click', () => {
            iconPickerModal.style.display = 'block';
            iconSearchInput.value = '';
            renderIcons();
        });

        closeIconPickerModal.addEventListener('click', () => {
            iconPickerModal.style.display = 'none';
        });

    iconSearchInput.addEventListener('keyup', (e) => {
        renderIcons(e.target.value);
    });
});
</script>
<!-- Подключение TinyMCE WYSIWYG редактора -->
<script src="https://cdn.tiny.cloud/1/ilf8e4vsikngopxe08xuqeely1o5rigddts9einhhrfen31e/tinymce/6/tinymce.min.js" referrerpolicy="origin" crossorigin="anonymous"></script>
<script>
  tinymce.init({
    selector: 'textarea.wysiwyg',
    menubar: false,
    plugins: 'link lists code emoticons',
    toolbar: 'undo redo | bold italic underline | bullist numlist | link | emoticons | code',
    language: 'ru',
    height: 300,
    entity_encoding: 'raw',
    emoticons_database: 'emoji'  // 🔧 обязателен!
  });
</script>
@endsection