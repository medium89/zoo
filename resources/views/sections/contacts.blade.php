<section id="contacts">
    <div class="contacts-container container">
        @if($socials->where('active', true)->count() > 0)
        <div class="contacts-header">
            <h2 class="white">Контакты</h2>
            <div class="foot foot-white"></div>
        </div>
        <div class="contacts-content">
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
                            <div class="form-group">
                                <input type="text" name="name" placeholder="Ваше имя" required>
                            </div>
                            <div class="form-group">
                                <input type="tel" name="phone" placeholder="+7(999)999-99-99" required>
                            </div>
                            <div class="form-group">
                                <textarea name="message" placeholder="Опишите необходимую услугу, животное, даты и адрес" rows="3" required></textarea>
                            </div>
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
        </div>
        @else
        <div class="contacts-content__empty-message">
            <h2 style="text-align: center; padding: 20px; color: #fff;">На данный момент контакты отсутствуют.</h2>
        </div>
        @endif
    </div>
</section>