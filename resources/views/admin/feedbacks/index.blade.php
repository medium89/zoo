@extends('admin.index')

@section('content')
<div class="container">
    <h1>Сообщения обратной связи</h1>
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="row">
        @foreach ($feedbacks as $feedback)
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Сообщение от: {{ $feedback->name }}</h5>
                    <p class="card-text"><strong>Телефон:</strong> {{ $feedback->phone ?? 'Не указан' }}</p>
                    <p class="card-text"><strong>Текст сообщения:</strong> {{ Str::limit($feedback->message, 100) }}</p>
                    <p class="card-text"><strong>Статус:</strong> {{ $feedback->status }}</p>
                    <p class="card-text"><small class="text-muted">Получено: {{ $feedback->created_at->format('d.m.Y H:i') }}</small></p>
                    <a href="{{ route('admin.feedbacks.edit', $feedback->id) }}" class="btn btn-sm btn-primary text-white"><i class="fa fa-pen"></i></a>
                    <form action="{{ route('admin.feedbacks.destroy', $feedback->id) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Вы уверены?')">Удалить</button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{ $feedbacks->links() }}
</div>
@endsection 
