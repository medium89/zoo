<section id="contacts">
    <div class="contacts-container container">
        @php($recaptchaKey = config('services.recaptcha.site_key'))
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
                                <div class="form-group d-flex justify-content-center" style="margin-top:10px;">
                                    <div class="g-recaptcha" data-sitekey="{{ $recaptchaKey }}"></div>
                                </div>
                                @if($errors->has('g-recaptcha-response'))
                                    <div class="text-danger small mt-1 text-center">{{ $errors->first('g-recaptcha-response') }}</div>
                                @endif
                            @else
                                <div class="alert alert-warning">reCAPTCHA не настроена. Добавьте ключи в .env</div>
                            @endif
                            <div class="form-group consent-group">
                                <div class="consent-check">
                                    <input type="checkbox" id="contactConsentMain" name="personal_data_consent" value="1">
                                    <label for="contactConsentMain">Нажимая кнопку, я даю согласие на обработку персональных данных.</label>
                                    <button type="button" class="consent-doc-link" data-consent-open>Ознакомиться с документом</button>
                                </div>
                                @if($errors->has('personal_data_consent'))
                                    <div class="text-danger small mt-1">{{ $errors->first('personal_data_consent') }}</div>
                                @endif
                            </div>
                            <div class="form-group">
                                <button type="submit" class="submit-btn" disabled>
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
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endif
