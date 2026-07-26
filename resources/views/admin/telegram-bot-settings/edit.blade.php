@extends('admin.index')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Telegram-бот</h1>
        <a href="{{ route('admin.settings') }}" class="btn btn-outline-secondary">Настройки сайта</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card" style="max-width:760px;">
        <div class="card-header">Напоминания о записях</div>
        <div class="card-body">
            <form action="{{ route('admin.telegram-bot-settings.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" role="switch" name="tomorrow_notifications_enabled" id="tomorrowNotificationsEnabled" value="1" {{ old('tomorrow_notifications_enabled', $settings->tomorrow_notifications_enabled) ? 'checked' : '' }}>
                    <label class="form-check-label" for="tomorrowNotificationsEnabled">Ежедневно присылать записи на завтра</label>
                </div>

                <div class="mb-3" style="max-width:220px;">
                    <label for="tomorrowNotificationTime" class="form-label">Время отправки</label>
                    <input type="time" class="form-control @error('tomorrow_notification_time') is-invalid @enderror" name="tomorrow_notification_time" id="tomorrowNotificationTime" value="{{ old('tomorrow_notification_time', substr($settings->tomorrow_notification_time, 0, 5)) }}" required>
                    @error('tomorrow_notification_time')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <p class="text-muted small mb-4">Часовой пояс: Барнаул (Asia/Barnaul). Уведомление придёт в Telegram-чаты, указанные в <code>TELEGRAM_CHAT_ID</code> и <code>TELEGRAM_CHAT_ID_2</code>. Даже если записей нет, бот сообщит об этом.</p>

                <button type="submit" class="btn btn-primary">Сохранить</button>
            </form>
        </div>
    </div>
</div>
@endsection
