@extends('admin.index')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>{{ $pet->name }}</h1>
    <div>
        <a href="{{ route('admin.pets.edit', $pet) }}" class="btn btn-warning">Редактировать</a>
        <a href="{{ route('admin.pets.index') }}" class="btn btn-secondary">Назад</a>
    </div>
    
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Информация</h5>
                <div class="mb-2"><strong>Вид услуги:</strong> {{ $pet->service_type }}</div>
                @if($pet->description)
                    <div class="mb-2"><strong>Описание:</strong></div>
                    <div class="mb-3">{!! nl2br(e($pet->description)) !!}</div>
                @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-1"><strong>Плюсы</strong></div>
                        <div class="d-flex flex-wrap gap-2">
                            @forelse(($pet->pluses ?? []) as $p)
                                <span class="badge rounded-pill" style="background:#28a745;color:#fff;padding:8px 12px;">{{ $p }}</span>
                            @empty
                                <span class="text-muted">Нет</span>
                            @endforelse
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-1"><strong>Минусы</strong></div>
                        <div class="d-flex flex-wrap gap-2">
                            @forelse(($pet->minuses ?? []) as $m)
                                <span class="badge rounded-pill" style="background:#dc3545;color:#fff;padding:8px 12px;">{{ $m }}</span>
                            @empty
                                <span class="text-muted">Нет</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Обложка</h5>
                @php($cover = optional($pet->photos->first())->path)
                @if($cover)
                    <img src="{{ asset('storage/'.$cover) }}" class="img-fluid" alt="">
                @else
                    <div class="bg-light d-flex align-items-center justify-content-center" style="height:180px;">Без фото</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-body">
        <h5 class="card-title">Фотографии</h5>
        @if($pet->photos->count())
            <div class="row g-3">
                @foreach($pet->photos as $photo)
                    <div class="col-md-3">
                        <img src="{{ asset('storage/'.$photo->path) }}" alt="" class="img-fluid js-photo" style="cursor:pointer;object-fit:cover;height:180px;width:100%;">
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-muted">Фотографий нет</div>
        @endif
    </div>
    
</div>

<!-- Modal for image preview -->
<div class="modal fade" id="photoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-body p-0">
        <img id="modalImg" src="" alt="" style="width:100%;height:auto;display:block;">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('click', function(e){
        const img = e.target.closest('.js-photo');
        if(!img) return;
        document.getElementById('modalImg').src = img.src;
        const m = new bootstrap.Modal(document.getElementById('photoModal'));
        m.show();
    });
</script>
@endsection
