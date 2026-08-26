@extends('admin.index')

@section('content')
<div class="linked-data-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div><h1 class="mb-1">Связанные данные</h1><p class="text-muted mb-0">Клиенты и их питомцы — всё в одном месте.</p></div>
        <div class="d-flex flex-wrap gap-2"><a class="btn btn-outline-primary" href="{{ route('admin.client-map.index') }}"><i class="fa fa-diagram-project me-1"></i>Карта клиентов</a><button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#linkedClientModal"><i class="fa fa-plus me-1"></i>Клиент</button></div>
    </div>

    @if(session('success'))<div class="alert alert-success border-0 shadow-sm mb-4">{{ session('success') }}</div>@endif

    <div class="linked-data-grid">
        @forelse($clients as $client)
            <article class="linked-client-card">
                <div class="linked-client-card__head"><div><div class="linked-client-card__eyebrow"><i class="fa fa-user"></i> Клиент</div><h2>{{ $client->name }}</h2><div class="linked-client-card__contacts">{{ $client->phone ?: 'Телефон не указан' }}@if($client->address) · {{ $client->address }}@endif</div></div><button class="btn btn-sm btn-primary linked-add-animal" type="button" data-client-id="{{ $client->id }}" data-client-name="{{ $client->name }}"><i class="fa fa-plus me-1"></i>Питомец</button></div>
                <div class="linked-client-card__animals">
                    @forelse($client->animals as $animal)
                        @php($photo = $animal->photos->first()?->path)
                        <div class="linked-animal-row">
                            @if($photo)<img src="{{ Storage::url($photo) }}" alt="">@else<div class="linked-animal-row__empty">🐾</div>@endif
                            <div><strong>{{ $animal->name }}</strong><span>{{ $animal->category?->name ?: 'Категория не указана' }}</span></div>
                        </div>
                    @empty
                        <div class="linked-empty"><i class="fa fa-paw"></i> Пока нет питомцев</div>
                    @endforelse
                </div>
            </article>
        @empty
            <div class="linked-empty linked-empty--large"><i class="fa fa-users"></i><strong>Клиентов пока нет</strong><span>Добавьте первого клиента, затем сразу привяжите к нему питомцев.</span></div>
        @endforelse
    </div>

    @if($unlinkedAnimals->isNotEmpty())
        <section class="linked-unlinked mt-4"><div class="d-flex justify-content-between align-items-center gap-2 mb-2"><div><h2 class="h5 mb-1">Питомцы без хозяина</h2><p class="text-muted mb-0">Их можно привязать через карту или добавить хозяина в разделе клиентов.</p></div><span class="badge text-bg-warning">{{ $unlinkedAnimals->count() }}</span></div><div class="linked-unlinked__list">@foreach($unlinkedAnimals as $animal)<span><i class="fa fa-paw"></i> {{ $animal->name }}</span>@endforeach</div></section>
    @endif
</div>

<div class="modal fade" id="linkedClientModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered linked-dialog"><form action="{{ route('admin.linked-data.clients.store') }}" method="post" class="modal-content linked-modal">@csrf<div class="modal-header"><div><small><i class="fa fa-user-plus"></i> Связанные данные</small><h5 class="modal-title">Новый клиент</h5></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="linked-field"><label for="linkedClientName">Имя или ФИО <b>*</b></label><input class="form-control" id="linkedClientName" name="name" required placeholder="Например, Анастасия Иванова"></div><div class="linked-field"><label for="linkedClientPhone">Телефон</label><input class="form-control" id="linkedClientPhone" name="phone" placeholder="+7 999 123-45-67"></div><div class="linked-field"><label for="linkedClientAddress">Адрес</label><input class="form-control" id="linkedClientAddress" name="address" placeholder="Улица, дом, квартира"></div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button><button class="btn btn-primary"><i class="fa fa-plus me-1"></i>Создать клиента</button></div></form></div></div>
<div class="modal fade" id="linkedAnimalModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered linked-dialog"><form action="{{ route('admin.linked-data.animals.store') }}" method="post" enctype="multipart/form-data" class="modal-content linked-modal">@csrf<input type="hidden" name="client_id" id="linkedAnimalClientId"><div class="modal-header"><div><small><i class="fa fa-paw"></i> Новый питомец для <span id="linkedAnimalClientName"></span></small><h5 class="modal-title">Добавить питомца</h5></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="linked-field"><label for="linkedAnimalName">Кличка <b>*</b></label><input class="form-control" id="linkedAnimalName" name="name" required placeholder="Например, Дейзи"></div><div class="linked-field"><label for="linkedAnimalCategory">Категория</label><select class="form-select" id="linkedAnimalCategory" name="category_id"><option value="">Не указана</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></div><div class="linked-field"><label for="linkedAnimalPhoto">Фото</label><input class="form-control" id="linkedAnimalPhoto" type="file" name="photo" accept="image/*"></div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Отмена</button><button class="btn btn-primary"><i class="fa fa-plus me-1"></i>Добавить питомца</button></div></form></div></div>
@endsection

@push('styles')
<style>
.linked-data-page{max-width:1320px;margin:0 auto}.linked-data-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(330px,1fr));gap:18px}.linked-client-card{overflow:hidden;border:1px solid #dce5ee;border-radius:16px;background:#fff;box-shadow:0 9px 24px rgba(43,61,80,.08)}.linked-client-card__head{display:flex;justify-content:space-between;gap:14px;padding:18px;border-bottom:1px solid #eaf0f5;background:#f7fbff}.linked-client-card__eyebrow,.linked-modal small{display:block;margin-bottom:4px;color:#4a82ba;font-size:.72rem;font-weight:800;letter-spacing:.04em;text-transform:uppercase}.linked-client-card h2{margin:0;color:#304255;font-size:1.08rem;font-weight:800}.linked-client-card__contacts{margin-top:6px;color:#718195;font-size:.82rem;line-height:1.4}.linked-client-card__animals{display:grid;gap:8px;padding:12px}.linked-animal-row{display:flex;align-items:center;gap:10px;padding:9px;border-radius:10px;background:#fffaf1}.linked-animal-row img,.linked-animal-row__empty{width:42px;height:42px;flex:0 0 42px;border-radius:9px;object-fit:cover}.linked-animal-row__empty{display:grid;place-items:center;background:#fff0d3;font-size:20px}.linked-animal-row strong,.linked-animal-row span{display:block}.linked-animal-row strong{color:#3b4d60;font-size:.9rem}.linked-animal-row span{margin-top:2px;color:#8190a0;font-size:.76rem}.linked-empty{display:flex;align-items:center;justify-content:center;gap:8px;min-height:62px;color:#8a99a8;font-size:.85rem}.linked-empty--large{min-height:180px;flex-direction:column;border:1px dashed #cddae6;border-radius:16px;background:#fff}.linked-empty--large i{font-size:30px;color:#8bb2d5}.linked-empty--large strong{color:#4b6177}.linked-empty--large span{max-width:300px;text-align:center}.linked-unlinked{padding:18px;border:1px solid #f1d9a9;border-radius:14px;background:#fffaf1}.linked-unlinked__list{display:flex;flex-wrap:wrap;gap:8px}.linked-unlinked__list span{padding:6px 9px;border-radius:999px;background:#fff0d3;color:#96631d;font-size:.82rem;font-weight:700}.linked-dialog{max-width:460px}.linked-modal{overflow:hidden;border:0;border-radius:16px;box-shadow:0 24px 64px rgba(28,45,64,.2)}.linked-modal .modal-header{align-items:flex-start;padding:21px 24px 17px;border-bottom:1px solid #edf1f5}.linked-modal .modal-title{color:#2d3e50;font-weight:800}.linked-modal .modal-body{padding:20px 24px 24px}.linked-modal .modal-footer{gap:9px;padding:15px 24px 20px;border-top:1px solid #edf1f5}.linked-field{margin-top:16px}.linked-field:first-child{margin-top:0}.linked-field label{display:block;margin-bottom:7px;color:#435467;font-size:.82rem;font-weight:750}.linked-field label b{color:#d9534f}.linked-field .form-control,.linked-field .form-select{min-height:44px;border-color:#d9e3ec;border-radius:9px;box-shadow:none}.linked-field .form-control:focus,.linked-field .form-select:focus{border-color:#76a9dd;box-shadow:0 0 0 3px rgba(49,120,198,.12)}@media(max-width:575px){.linked-data-grid{grid-template-columns:1fr}.linked-client-card__head{align-items:flex-start;flex-direction:column}.linked-client-card__head .btn{width:100%}.linked-dialog{margin:12px}.linked-modal .modal-header,.linked-modal .modal-body{padding-right:19px;padding-left:19px}.linked-modal .modal-footer{padding-right:19px;padding-left:19px}.linked-modal .modal-footer .btn{flex:1;padding-right:8px;padding-left:8px}}
</style>
@endpush

@push('scripts')
<script>
document.querySelectorAll('.linked-add-animal').forEach(button => button.addEventListener('click', () => {
    document.getElementById('linkedAnimalClientId').value = button.dataset.clientId;
    document.getElementById('linkedAnimalClientName').textContent = button.dataset.clientName;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('linkedAnimalModal')).show();
}));
</script>
@endpush
