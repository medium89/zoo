@extends('admin.index')

@section('content')
<div class="container-fluid">
    @php($animalName = $boarding->animal?->name ?: $boarding->name)
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="mb-1">Действия: {{ $animalName }}</h1>
            <div class="text-muted">{{ $boarding->start_date->format('d.m.Y') }} — {{ $boarding->end_date->format('d.m.Y') }}</div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-header">Новое ежедневное действие</div>
        <div class="card-body">
            <form action="{{ route('admin.boarding.tasks.store', $boarding) }}" method="POST" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-8">
                    <label class="form-label">Действие</label>
                    <input name="title" class="form-control" required maxlength="255" placeholder="Например, Утреннее кормление котов">
                </div>
                <div class="col-md-8">
                    <label class="form-label">Инструкция для бота</label>
                    <textarea name="instructions" class="form-control" rows="3" placeholder="Подробности, которые бот покажет в уведомлении"></textarea>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Время</label>
                    <input type="time" name="scheduled_time" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-success w-100" type="submit">Добавить</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Расписание</div>
        <div class="card-body">
            @forelse($boarding->tasks as $task)
                <form action="{{ route('admin.boarding.tasks.update', $task) }}" method="POST" class="border rounded p-3 mb-3">
                    @csrf
                    @method('PUT')
                    <div class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label">Действие</label>
                            <input name="title" class="form-control" value="{{ $task->title }}" required maxlength="255">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Инструкция для бота</label>
                            <textarea name="instructions" class="form-control" rows="3" placeholder="Подробности, которые бот покажет в уведомлении">{{ $task->instructions }}</textarea>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Время</label>
                            <input type="time" name="scheduled_time" class="form-control" value="{{ substr($task->scheduled_time, 0, 5) }}" required>
                        </div>
                        <div class="col-md-2">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="task-active-{{ $task->id }}" {{ $task->is_active ? 'checked' : '' }}>
                                <label class="form-check-label" for="task-active-{{ $task->id }}">Активно</label>
                            </div>
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button class="btn btn-primary flex-grow-1" type="submit">Сохранить</button>
                        </div>
                    </div>
                    @php($lastRun = $task->runs->first())
                    <div class="small text-muted mt-2">
                        @if($lastRun)
                            Последний запуск: {{ $lastRun->notification_date->format('d.m.Y') }} · {{ $lastRun->status === 'done' ? 'готово' : ($lastRun->status === 'cancelled' ? 'отменено' : 'ожидает') }}
                        @else
                            Уведомлений ещё не было.
                        @endif
                    </div>
                </form>
                <div class="mb-4 d-flex justify-content-end"><x-admin.actions-menu label="Действия с ежедневным действием">
                    <form action="{{ route('admin.boarding.tasks.destroy', $task) }}" method="POST" class="js-delete-form" data-confirm="Удалить действие «{{ $task->title }}»?">
                        @csrf
                        @method('DELETE')
                        <button class="admin-actions-menu__item admin-actions-menu__item--danger" type="submit"><i class="fa fa-trash" aria-hidden="true"></i><span>Удалить действие</span></button>
                    </form>
                </x-admin.actions-menu></div>
            @empty
                <div class="text-muted">Действий пока нет.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
