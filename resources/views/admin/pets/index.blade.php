@extends('admin.index')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Питомцы</h1>
    <a href="{{ route('admin.pets.create') }}" class="btn btn-primary">Добавить питомца</a>
    
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row">
@forelse($pets as $pet)
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            @php($cover = optional($pet->photos->first())->path)
            @if($cover)
                <img src="{{ asset('storage/'.$cover) }}" class="card-img-top" alt="" style="height: 180px; object-fit: cover;">
            @else
                <div class="card-img-top d-flex align-items-center justify-content-center bg-light" style="height: 180px;">Без фото</div>
            @endif
            <div class="card-body d-flex flex-column">
                <h5 class="card-title">{{ $pet->name }}</h5>
                <div class="text-muted mb-2">Вид услуги: {{ $pet->service_type }}</div>
                <div class="mt-auto d-flex gap-2">
                    <a href="{{ route('admin.pets.show', $pet) }}" class="btn btn-sm btn-primary">Просмотр</a>
                    <a href="{{ route('admin.pets.edit', $pet) }}" class="btn btn-sm btn-warning">Редактировать</a>
                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#delModal{{ $pet->id }}">Удалить</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="delModal{{ $pet->id }}" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Удаление питомца</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">Вы уверены, что хотите удалить "{{ $pet->name }}"?</div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
            <form action="{{ route('admin.pets.destroy', $pet) }}" method="POST">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Удалить</button>
            </form>
          </div>
        </div>
      </div>
    </div>
@empty
    <div class="col-12"><div class="alert alert-info">Питомцев пока нет.</div></div>
@endforelse
</div>

{{ $pets->links() }}
@endsection
