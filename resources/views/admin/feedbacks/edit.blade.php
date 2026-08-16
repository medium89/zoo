@extends('admin.index')

@section('content')
<div class="container">
    <h1>Редактировать сообщение обратной связи</h1>
    <form action="{{ route('admin.feedbacks.update', $feedback->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="name">Имя:</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $feedback->name) }}" required>
        </div>
        <div class="form-group">
            <label for="phone">Телефон:</label>
            <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $feedback->phone) }}" required>
        </div>
        <div class="form-group">
            <label for="message">Сообщение:</label>
            <textarea class="form-control" id="message" name="message" rows="5" required>{{ old('message', $feedback->message) }}</textarea>
        </div>
        <div class="form-group">
            <label for="status">Статус:</label>
            <select class="form-control" id="status" name="status" required>
                <option value="new" {{ old('status', $feedback->status) == 'new' ? 'selected' : '' }}>Новое</option>
                <option value="in_progress" {{ old('status', $feedback->status) == 'in_progress' ? 'selected' : '' }}>В процессе</option>
                <option value="completed" {{ old('status', $feedback->status) == 'completed' ? 'selected' : '' }}>Завершено</option>
                <option value="cancelled" {{ old('status', $feedback->status) == 'cancelled' ? 'selected' : '' }}>Отменено</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Обновить</button>
    </form>
</div>
@endsection

@section('scripts')
    @include('admin.partials.wysiwyg-scripts')
@endsection
