<div class="card-body">
    <div class="mb-3">
        <label class="form-label">Имя / ФИО</label>
        <input type="text" name="name" class="form-control" required value="{{ old('name', $client?->name) }}">
    </div>
    <div class="mb-3">
        <label class="form-label">Телефон</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone', $client?->phone) }}">
    </div>
    <div class="mb-3">
        <label class="form-label">Адрес</label>
        <input type="text" name="address" class="form-control" value="{{ old('address', $client?->address) }}" placeholder="Город, улица, дом">
        <div class="form-text">Адрес появится на карте в разделе «Клиенты».</div>
    </div>
    <div class="mb-3">
        <label class="form-label">Заметка</label>
        <textarea name="note" class="form-control" rows="4">{{ old('note', $client?->note) }}</textarea>
    </div>
    <div class="mb-3">
        @include('admin.partials.tags-editor', ['tags' => $client?->tags])
    </div>
</div>
<div class="card-footer d-flex gap-2">
    <button class="btn btn-success">Сохранить</button>
</div>
