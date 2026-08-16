<div class="card-body">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Кличка</label>
            <input type="text" name="name" class="form-control" required value="{{ old('name', $animal?->name) }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Хозяин</label>
            <select name="client_id" class="form-select">
                <option value="">Без хозяина</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" @selected((string)old('client_id', $animal?->client_id) === (string)$client->id)>
                        {{ $client->name }}{{ $client->phone ? ' · '.$client->phone : '' }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Категория животного</label>
            <select name="category_id" class="form-select">
                <option value="">Не указана</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) old('category_id', $animal?->category_id) === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Размер собаки</label>
            <select name="dog_size" class="form-select">
                <option value="">Не относится к собаке / не указан</option>
                <option value="small" @selected(old('dog_size', $animal?->dog_size) === 'small')>Мелкая</option>
                <option value="large" @selected(old('dog_size', $animal?->dog_size) === 'large')>Средняя или крупная</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Краткое описание</label>
            <input type="text" name="description" class="form-control" value="{{ old('description', $animal?->description) }}">
        </div>
        <div class="col-12">
            <label class="form-label">Заметки</label>
            <textarea name="note" class="form-control" rows="4">{{ old('note', $animal?->note) }}</textarea>
        </div>
        <div class="col-12">
            @include('admin.partials.tags-editor', ['tags' => $animal?->tags])
        </div>
        <div class="col-12">
            <label class="form-label">Фото питомца</label>
            <input type="file" name="photos[]" class="form-control" accept="image/*" multiple>
            <div class="form-text">Можно загрузить несколько фото.</div>
        </div>
    </div>
</div>
<div class="card-footer d-flex gap-2">
    <button class="btn btn-success">Сохранить</button>
</div>
