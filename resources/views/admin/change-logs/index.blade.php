@extends('admin.index')
@section('content')
<div class="d-flex justify-content-between mb-4">
  <h1 class="h3">Логи изменений</h1>
  <a class="btn btn-outline-primary" href="{{ url('/?edit=1') }}">Визуальный редактор</a>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
<div class="table-responsive bg-white rounded-3 shadow-sm">
<table class="table table-hover align-middle mb-0">
<thead><tr><th>Когда</th><th>Кто</th><th>Действие</th><th>Изменений</th><th>Состояние</th><th></th></tr></thead>
<tbody>
@forelse($logs as $log)
<tr>
  <td class="text-nowrap">{{ $log->created_at->format('d.m.Y H:i:s') }}</td>
  <td>{{ $log->user?->name ?? 'Система' }}</td>
  <td><strong>{{ $log->route ?: $log->method }}</strong><br><small class="text-muted">{{ $log->summary }}</small></td>
  <td>{{ count($log->changes ?? []) }}@if($log->media_backups) · файлов: {{ count($log->media_backups) }}@endif</td>
  <td>@if($log->reverted_at)<span class="badge text-bg-secondary">Отменено</span>@else<span class="badge text-bg-success">Действует</span>@endif</td>
  <td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.logs.show', $log) }}">Подробнее</a></td>
</tr>
@empty
<tr><td colspan="6" class="text-center py-5 text-muted">Изменений пока нет.</td></tr>
@endforelse
</tbody></table></div>
<div class="mt-3">{{ $logs->links() }}</div>
@endsection
