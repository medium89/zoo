<section id="contacts">
    <div class="contacts-container container">
        @if($socials->where('active', true)->count() > 0)
        <div class="contacts-header">
            <h2 class="white">Контакты</h2>
            <div class="foot foot-white"></div>
        </div>
        <div class="contacts-content">
            <div class="contacts-content__item contacts-form">
                <div class="contacts-content__item-wrapper">
                    <div class="contacts-content__item-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="contacts-content__item-title">
                        <h3>Оставить заявку</h3>
                    </div>
                    <div class="contacts-content__item-description">
                        @php($recaptchaKey = config('services.recaptcha.site_key'))
                        <form class="contact-form" action="{{ route('feedback.store') }}" method="POST">
                            @csrf
                            @if(session('success'))
                                <div class="alert alert-success">{{ session('success') }}</div>
                            @endif
                            @if($errors->any())
                                <div class="alert alert-danger">{{ $errors->first() }}</div>
                            @endif
                            <div class="form-group">
                                <input type="text" name="name" placeholder="Ваше имя" required>
                            </div>
                            <div class="form-group">
                                <input type="tel" name="phone" placeholder="+7(999)999-99-99" required>
                            </div>
                            <div class="form-group">
                                <textarea name="message" placeholder="Опишите необходимую услугу, животное, даты и адрес" rows="3" required></textarea>
                            </div>
                            @if($recaptchaKey)
                                <input type="hidden" name="g-recaptcha-response" value="">
                                @if($errors->has('g-recaptcha-response'))
                                    <div class="text-danger small mt-1">{{ $errors->first('g-recaptcha-response') }}</div>
                                @endif
                                <div class="text-muted small mt-2">Сайт защищён reCAPTCHA v3 (Google Privacy Policy & Terms apply).</div>
                            @else
                                <div class="alert alert-warning">reCAPTCHA не настроена. Добавьте ключи в .env</div>
                            @endif
                            <div class="form-group">
                                <button type="submit" class="submit-btn">
                                    <i class="fas fa-paper-plane"></i>
                                    Отправить заявку
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @foreach($socials->where('active', true) as $social)
                <div class="contacts-content__item messenger-item">
                    <div class="contacts-content__item-wrapper">
                        <div class="contacts-content__item-icon">
                            <i class="{{ $social->icon }}"></i>
                        </div>
                        <div class="contacts-content__item-title">
                            <h3>{{ $social->title }}</h3>
                        </div>
                        <div class="contacts-content__item-description">
                            <p><a href="{{ $social->link }}" target="_blank">{{ $social->link_text }}</a></p>
                            {!! $social->text !!}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @else
        <div class="contacts-content__empty-message">
            <h2 style="text-align: center; padding: 20px; color: #fff;">На данный момент контакты отсутствуют.</h2>
        </div>
        @endif
    </div>
</section>
@if($recaptchaKey)
    <script src="https://www.google.com/recaptcha/api.js?render={{ $recaptchaKey }}" async defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            const form = document.querySelector('.contact-form');
            if(!form) return;
            const tokenInput = form.querySelector('input[name="g-recaptcha-response"]');
            if(!tokenInput) return;

            const executeRecaptcha = function(cb){
                grecaptcha.ready(function(){
                    grecaptcha.execute('{{ $recaptchaKey }}', {action: 'feedback'}).then(function(token){
                        tokenInput.value = token;
                        if(typeof cb === 'function') cb();
                    }).catch(function(){
                        alert('Не удалось подтвердить reCAPTCHA. Попробуйте ещё раз.');
                    });
                });
            };

            // Run once on load to show badge and prefill token
            executeRecaptcha();

            form.addEventListener('submit', function(e){
                e.preventDefault();
                executeRecaptcha(function(){
                    form.submit();
                });
            });
        });
    </script>
@endif
