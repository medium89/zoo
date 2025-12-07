<header id="index">
    <div class="header-content container">
        <a href="#" class="logo">
            <img src="{{ asset('assets/img/logo.png') }}" alt="Логотип">
        </a>
        <nav class="nav">
            <div class="nav-toggle" id="navToggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <div class="nav-menu" id="navMenu">
                <a href="#index"><span>Главная</span></a>
                <a href="#about"><span>Обо мне</span></a>
                <a href="#advantages"><span>Преимущества</span></a>
                <a href="#services"><span>Услуги</span></a>
                <a href="#gallery"><span>Фотоальбом</span></a>
                <a href="#contacts"><span>Контакты</span></a>
                <a href="{{ route('articles.index') }}" class="nav-articles"><span>Статьи</span></a>
            </div>
        </nav>
    </div>
</header> 
