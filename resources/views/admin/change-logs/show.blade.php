@extends('admin.index')
@section('content')
<a href="{{ route('admin.logs.index') }}" class="btn btn-link ps-0 mb-3">← Все логи</a>
<div class="d-flex justify-content-between flex-wrap gap-3 mb-4">
  <div><h1 class="h3">Изменение №{{ $changeLog->id }}</h1><p class="text-muted">{{ $changeLog->created_at->format('d.m.Y H:i:s') }} · {{ $changeLog->user?->name ?? 'Система' }} · {{ $changeLog->route }}</p></div>
  @if(!$changeLog->reverted_at)
  <form method="POST" action="{{ route('admin.logs.revert', $changeLog) }}" onsubmit="return confirm('Вернуть прежнее состояние? Более поздние правки не будут затронуты.');">
    @csrf<button class="btn btn-warning" type="submit">Отменить это изменение</button>
  </form>
  @else<span class="badge text-bg-secondary">Отменено {{ $changeLog->reverted_at->format('d.m.Y H:i') }} · {{ $changeLog->revertedBy?->name ?? 'администратор' }}</span>@endif
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@forelse($changeLog->changes ?? [] as $change)
@if($change['kind'] === 'row')
@php
  $old = $change['before'] ?? [];
  $new = $change['after'] ?? [];
  $fields = array_unique(array_merge(array_keys($old), array_keys($new)));
  $action = $change['before'] === null ? 'Создано' : ($change['after'] === null ? 'Удалено' : 'Обновлено');
@endphp
<details class="bg-white rounded-3 shadow-sm mb-3 p-3">
<summary class="fw-semibold" style="cursor:pointer">{{ $action }}: {{ $change['table'] }} #{{ $change['id'] }}</summary>
<div class="table-responsive mt-3"><table class="table table-sm table-bordered align-middle">
<thead><tr><th>Поле</th><th>Было</th><th>Стало</th></tr></thead><tbody>
@foreach($fields as $field)
@if(($old[$field] ?? null) !== ($new[$field] ?? null))
@php
  $secret = preg_match('/password|token|secret|api_key|private_key/i', $field);
  $left = $secret ? '••••••' : (is_scalar($old[$field] ?? null) ? (string) $old[$field] : json_encode($old[$field] ?? null, JSON_UNESCAPED_UNICODE));
  $right = $secret ? '••••••' : (is_scalar($new[$field] ?? null) ? (string) $new[$field] : json_encode($new[$field] ?? null, JSON_UNESCAPED_UNICODE));
@endphp
<tr><th>{{ $field }}</th><td style="max-width:380px;overflow-wrap:anywhere;white-space:pre-wrap">{{ $left }}</td><td style="max-width:380px;overflow-wrap:anywhere;white-space:pre-wrap">{{ $right }}</td></tr>
@endif
@endforeach
</tbody></table></div></details>
@else
<div class="bg-white rounded-3 shadow-sm mb-3 p-3">Файл <strong>{{ $change['path'] }}</strong> изменён.</div>
@endif
@empty<p class="text-muted">Изменений в базе нет.</p>
@endforelse
@if($changeLog->media_backups)
<div class="bg-white rounded-3 shadow-sm p-3"><h2 class="h5">Резервные копии изображений</h2><ul class="mb-0">@foreach($changeLog->media_backups as $path => $backup)<li>{{ $path }}</li>@endforeach</ul></div>
@endif
@endsection
