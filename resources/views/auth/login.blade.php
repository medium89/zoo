@extends('layouts.app')

@section('content')

            <div class="card" style="         
            align-items: center;
            justify-content: center;
            max-width: 500px;
            width: 100%;
            min-width: 100%;
            margin: 100px auto 0 auto;
            border: none;">
                <div class="card-body" style="
                    width: 100%;
                    max-width: 500px;
                    box-shadow: 0 0 10px 0 rgba(0, 0, 0, 0.1);
                ">
                    <h2 style="text-align: center; padding: 20px 15px; font-size: 20px; font-weight: bold;">Авторизация</h2>
                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="row mb-3">
                            <label for="email" class="col-md-4 col-form-label text-md-end">Email</label>

                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password" class="col-md-4 col-form-label text-md-end">Пароль</label>

                            <div class="col-md-6">
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">

                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>


                        <div class="row mb-0">
                            <div class="col-md-8 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    Войти
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
@endsection
