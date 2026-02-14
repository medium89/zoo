@extends('admin.index')

@section('content')
<div class="container-fluid">
    <h1 class="mb-3">Согласие на обработку персональных данных</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('admin.personal-data-consent.update') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="personal_data_consent_text" class="form-label">Текст документа</label>
            <textarea
                id="personal_data_consent_text"
                name="personal_data_consent_text"
                class="form-control wysiwyg"
                rows="12"
            >{{ old('personal_data_consent_text', $settings->personal_data_consent_text ?? '') }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary">Сохранить</button>
    </form>
</div>
@endsection

@section('scripts')
    @include('admin.partials.wysiwyg-scripts')
@endsection

