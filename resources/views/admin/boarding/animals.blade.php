@extends('admin.index')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Животные передержки</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.boarding.archive') }}" class="btn btn-outline-secondary">Архив</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <p class="text-muted mb-3">
                Список пополняется автоматически при добавлении заявок на передержку и используется для подсказок в поле «Кличка».
            </p>
            @if($animals->count())
                <div class="boarding-animals-grid">
                    @foreach($animals as $animal)
                        @php($last = $animal->boardings->first())
                        <article class="boarding-animal-card">
                            <div class="boarding-animal-card__top">
                                <div class="boarding-animal-card__identity">
                                    @if($animal->photos->first())
                                        <img src="{{ Storage::url($animal->photos->first()->path) }}" alt="{{ $animal->name }}" class="boarding-animal-card__photo">
                                    @else
                                        <span class="boarding-animal-card__photo boarding-animal-card__photo--empty"><i class="fa fa-paw"></i></span>
                                    @endif
                                    <div>
                                        <a href="{{ route('admin.animals.show', $animal) }}" class="boarding-animal-card__name">{{ $animal->name }}</a>
                                        <p>{{ $animal->species ?: 'Вид не указан' }}@if($animal->dog_size) · {{ $animal->dog_size === 'small' ? 'мелкая собака' : 'средняя/крупная собака' }}@endif</p>
                                    </div>
                                </div>
                                <span class="boarding-animal-card__id">#{{ $animal->id }}</span>
                            </div>

                            <div class="boarding-animal-card__facts">
                                <div>
                                    <span>Хозяин</span>
                                    @if($animal->client)
                                        <a href="{{ route('admin.clients.show', $animal->client) }}">{{ $animal->client->name }}</a>
                                    @else
                                        <strong>Не указан</strong>
                                    @endif
                                </div>
                                <div>
                                    <span>Заявок</span>
                                    <strong>{{ $animal->boardings_count }}</strong>
                                </div>
                                <div>
                                    <span>Последняя запись</span>
                                    <strong>
                                        @if($last)
                                            {{ $last->start_date->locale('ru')->translatedFormat('j F') }} — {{ $last->end_date->locale('ru')->translatedFormat('j F') }}
                                        @else
                                            —
                                        @endif
                                    </strong>
                                </div>
                            </div>

                            @if($animal->description)
                                <div class="boarding-animal-card__description">
                                    <span>Описание</span>
                                    <p>{{ $animal->description }}</p>
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            @else
                <div class="text-muted">Животных пока нет. Добавьте заявку на передержку, чтобы они появились в списке.</div>
            @endif
        </div>
    </div>
</div>

<style>
    .boarding-animals-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:16px; }
    .boarding-animal-card { display:flex; flex-direction:column; min-width:0; padding:18px; border:1px solid #e0e6eb; border-radius:18px; background:#fff; box-shadow:0 8px 22px rgba(37, 52, 66, .055); }
    .boarding-animal-card__top { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
    .boarding-animal-card__identity { display:flex; min-width:0; align-items:center; gap:12px; }
    .boarding-animal-card__photo { display:grid; flex:0 0 52px; width:52px; height:52px; place-items:center; border:1px solid #dbe2e8; border-radius:14px; object-fit:cover; color:#7a89a0; background:#f4f7f9; }
    .boarding-animal-card__name { display:block; overflow:hidden; color:#263541; font-size:1.06rem; font-weight:800; line-height:1.25; text-decoration:none; text-overflow:ellipsis; white-space:nowrap; }
    .boarding-animal-card__name:hover { color:#0d6efd; }
    .boarding-animal-card__identity p { margin:4px 0 0; color:#788697; font-size:.78rem; line-height:1.35; }
    .boarding-animal-card__id { flex:0 0 auto; padding:4px 8px; border:1px solid #dbe2e8; border-radius:999px; color:#68788a; background:#f7f9fa; font-size:.72rem; font-weight:800; }
    .boarding-animal-card__facts { display:grid; grid-template-columns:1.05fr .65fr 1.4fr; gap:10px; margin-top:17px; padding-top:14px; border-top:1px solid #edf1f3; }
    .boarding-animal-card__facts div { min-width:0; }
    .boarding-animal-card__facts span, .boarding-animal-card__description span { display:block; margin-bottom:4px; color:#8996a4; font-size:.68rem; font-weight:800; letter-spacing:.05em; text-transform:uppercase; }
    .boarding-animal-card__facts strong, .boarding-animal-card__facts a { display:block; overflow:hidden; color:#435363; font-size:.8rem; font-weight:700; text-decoration:none; text-overflow:ellipsis; white-space:nowrap; }
    .boarding-animal-card__facts a { color:#286491; }
    .boarding-animal-card__description { margin-top:15px; padding-top:13px; border-top:1px solid #edf1f3; }
    .boarding-animal-card__description p { margin:0; color:#667586; font-size:.82rem; line-height:1.5; }
    @media (max-width:575.98px) { .boarding-animals-grid { grid-template-columns:1fr; }.boarding-animal-card { padding:16px; }.boarding-animal-card__facts { grid-template-columns:1fr 1fr; }.boarding-animal-card__facts div:last-child { grid-column:1 / -1; } }
</style>
@endsection
