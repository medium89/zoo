// ===== Слайдер для .slider-content =====
document.addEventListener('DOMContentLoaded', function() {
    const slider = document.querySelector('.slider-content');
    if (!slider) return;
    const slides = Array.from(slider.children).filter(el => !el.classList.contains('slider-arrow') && !el.classList.contains('slider-dots'));
    let current = 0;
    let intervalId;

    // Создаём стрелки
    const prevBtn = document.createElement('button');
    prevBtn.className = 'slider-arrow slider-arrow--prev';
    prevBtn.innerHTML = '<span>&#8592;</span>';
    const nextBtn = document.createElement('button');
    nextBtn.className = 'slider-arrow slider-arrow--next';
    nextBtn.innerHTML = '<span>&#8594;</span>';
    slider.appendChild(prevBtn);
    slider.appendChild(nextBtn);

    // Создаём точки
    const dots = document.createElement('div');
    dots.className = 'slider-dots';
    slides.forEach((_, i) => {
        const dot = document.createElement('span');
        dot.className = 'slider-dot' + (i === 0 ? ' active' : '');
        dot.addEventListener('click', () => goToSlide(i, true));
        dots.appendChild(dot);
    });
    slider.appendChild(dots);

    function update() {
        slides.forEach((slide, i) => {
            if (i === current) {
                slide.classList.add('active');
            } else {
                slide.classList.remove('active');
            }
        });
        Array.from(dots.children).forEach((dot, i) => {
            dot.classList.toggle('active', i === current);
        });
    }

    function goToSlide(idx, manual = false) {
        current = (idx + slides.length) % slides.length;
        update();
        if (manual) restartInterval();
    }

    prevBtn.addEventListener('click', () => goToSlide(current - 1, true));
    nextBtn.addEventListener('click', () => goToSlide(current + 1, true));

    function nextAutoSlide() {
        goToSlide(current + 1);
    }
    function startInterval() {
        intervalId = setInterval(nextAutoSlide, 5000);
    }
    function restartInterval() {
        clearInterval(intervalId);
        startInterval();
    }

    // ===== Свайп функциональность =====
    let startX = 0;
    let startY = 0;
    let endX = 0;
    let endY = 0;
    let isSwiping = false;

    // Обработчик начала касания
    function handleTouchStart(e) {
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
        isSwiping = false;
    }

    // Обработчик движения пальца
    function handleTouchMove(e) {
        if (!startX || !startY) return;

        endX = e.touches[0].clientX;
        endY = e.touches[0].clientY;

        const diffX = startX - endX;
        const diffY = startY - endY;

        // Определяем, что это свайп, а не просто касание
        if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 10) {
            isSwiping = true;
            e.preventDefault(); // Предотвращаем скролл страницы при свайпе
        }
    }

    // Обработчик окончания касания
    function handleTouchEnd(e) {
        if (!isSwiping || !startX || !endX) {
            startX = startY = endX = endY = 0;
            return;
        }

        const diffX = startX - endX;
        const minSwipeDistance = 50; // Минимальное расстояние для свайпа

        if (Math.abs(diffX) > minSwipeDistance) {
            if (diffX > 0) {
                // Свайп влево - следующий слайд
                goToSlide(current + 1, true);
            } else {
                // Свайп вправо - предыдущий слайд
                goToSlide(current - 1, true);
            }
        }

        // Сброс переменных
        startX = startY = endX = endY = 0;
        isSwiping = false;
    }

    // Добавляем обработчики touch событий
    slider.addEventListener('touchstart', handleTouchStart, { passive: false });
    slider.addEventListener('touchmove', handleTouchMove, { passive: false });
    slider.addEventListener('touchend', handleTouchEnd, { passive: false });
    // ===== /Свайп функциональность =====

    // Инициализация
    update();
    startInterval();
});
// ===== /Слайдер для .slider-content =====

// ===== Прокрутка вверх при клике на логотип =====
document.addEventListener('DOMContentLoaded', function() {
    const logo = document.querySelector('.logo');
    if (!logo) return;

    logo.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Плавная прокрутка в самый верх страницы
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
});
// ===== /Прокрутка вверх при клике на логотип =====

// ===== Кнопка "Наверх" =====
document.addEventListener('DOMContentLoaded', function() {
    const toTopButton = document.querySelector('.to-top');
    if (!toTopButton) return;

    // Функция для проверки позиции прокрутки
    function checkScrollPosition() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const windowHeight = window.innerHeight;
        const documentHeight = document.documentElement.scrollHeight;
        
        // Показываем кнопку, когда пользователь прокрутил половину экрана
        if (scrollTop > windowHeight / 2) {
            toTopButton.style.display = 'block';
            toTopButton.style.opacity = '1';
        } else {
            toTopButton.style.opacity = '0';
            // Скрываем кнопку после завершения анимации
            setTimeout(() => {
                if (toTopButton.style.opacity === '0') {
                    toTopButton.style.display = 'none';
                }
            }, 300);
        }
    }

    // Функция для плавной прокрутки наверх
    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    // Добавляем обработчики событий
    window.addEventListener('scroll', checkScrollPosition);
    toTopButton.addEventListener('click', function(e) {
        e.preventDefault();
        scrollToTop();
    });

    // Инициализация - проверяем начальное состояние
    checkScrollPosition();
});
// ===== /Кнопка "Наверх" =====

// ===== Мобильное меню =====
document.addEventListener('DOMContentLoaded', function() {
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');
    const body = document.body;
    
    // Создаем оверлей для затемнения фона
    const overlay = document.createElement('div');
    overlay.className = 'nav-overlay';
    body.appendChild(overlay);
    
    // Функция для открытия/закрытия меню
    function toggleMenu() {
        navToggle.classList.toggle('active');
        navMenu.classList.toggle('active');
        overlay.classList.toggle('active');
        body.style.overflow = navMenu.classList.contains('active') ? 'hidden' : '';
    }
    
    // Обработчик клика по кнопке-гамбургеру
    navToggle.addEventListener('click', function(e) {
        e.preventDefault();
        toggleMenu();
    });
    
    // Закрытие меню при клике на оверлей
    overlay.addEventListener('click', function() {
        if (navMenu.classList.contains('active')) {
            toggleMenu();
        }
    });
    
    // Закрытие меню при нажатии Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && navMenu.classList.contains('active')) {
            toggleMenu();
        }
    });
    
    // Закрытие меню при клике на ссылку (для мобильных устройств)
    const navLinks = navMenu.querySelectorAll('a[href^="#"]');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                toggleMenu();
            }
        });
    });
});
// ===== /Мобильное меню =====

// ===== Плавная прокрутка для навигации =====
document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.nav-menu a[href^="#"]');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            const targetSection = document.querySelector(targetId);
            
            if (targetSection) {
                // Получаем позицию секции с учетом фиксированного header
                const headerHeight = document.querySelector('header').offsetHeight;
                let targetPosition = targetSection.offsetTop - headerHeight;
                if (targetId === "#index") targetPosition = 0;
                
                // Плавная прокрутка к секции
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
});
// ===== /Плавная прокрутка для навигации =====

// ===== Обработка формы контактов =====
document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.querySelector('.contact-form');
    if (!contactForm) return;

    const phoneInput = contactForm.querySelector('input[name="phone"]');
    if (phoneInput) {
        const maskPhone = () => {
            let digits = phoneInput.value.replace(/\D/g, '');
            if (digits.startsWith('7')) {
                digits = digits.substring(1);
            }
            digits = digits.substring(0, 10);

            if (digits.length === 0) {
                phoneInput.value = '';
                return;
            }

            let result = '+7(';
            if (digits.length > 0) result += digits.substring(0, 3);
            if (digits.length >= 3) result += ')' + digits.substring(3, 6);
            if (digits.length >= 6) result += '-' + digits.substring(6, 8);
            if (digits.length >= 8) result += '-' + digits.substring(8, 10);
            phoneInput.value = result;
        };
        phoneInput.addEventListener('input', maskPhone);
        phoneInput.addEventListener('focus', maskPhone);
    }

    contactForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const name = formData.get('name').trim();
        const phone = formData.get('phone').trim();
        const message = formData.get('message').trim();

        if (!name || !phone || !message) {
            showNotification('Пожалуйста, заполните обязательные поля', 'error');
            return;
        }

        const phoneRegex = /^\+7\(\d{3}\)\d{3}-\d{2}-\d{2}$/;
        if (!phoneRegex.test(phone)) {
            showNotification('Пожалуйста, введите корректный номер телефона', 'error');
            return;
        }

        const submitBtn = this.querySelector('.submit-btn');
        const originalText = submitBtn.innerHTML;

        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Отправка...';
        submitBtn.disabled = true;

        try {
            const response = await fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': formData.get('_token'),
                    'Accept': 'application/json'
                },
                body: formData
            });

            if (response.ok) {
                showNotification('Спасибо! Ваша заявка отправлена. Мы свяжемся с вами в ближайшее время.', 'success');
                this.reset();
            } else if (response.status === 422) {
                const data = await response.json();
                const errors = Object.values(data.errors).flat().join(' ');
                showNotification(errors, 'error');
            } else {
                showNotification('Произошла ошибка отправки. Попробуйте позже.', 'error');
            }
        } catch (error) {
            showNotification('Произошла ошибка отправки. Попробуйте позже.', 'error');
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });
    
    // Функция для показа уведомлений
    function showNotification(message, type) {
        // Создаем элемент уведомления
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                <span>${message}</span>
                <button class="notification-close">&times;</button>
            </div>
        `;
        
        // Добавляем стили
        notification.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            background: ${type === 'success' ? '#4CAF50' : '#f44336'};
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            transform: translate(-50%, -50%) scale(0);
            transition: transform 0.3s ease;
            max-width: 400px;
        `;
        
        // Добавляем в DOM
        document.body.appendChild(notification);
        
        // Показываем уведомление
        setTimeout(() => {
            notification.style.transform = 'translate(-50%, -50%) scale(1)';
        }, 100);
        
        // Обработчик закрытия
        const closeBtn = notification.querySelector('.notification-close');
        closeBtn.addEventListener('click', () => {
            notification.style.transform = 'translate(-50%, -50%) scale(0)';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        });
        
        // Автоматическое закрытие через 5 секунд
        setTimeout(() => {
            if (document.body.contains(notification)) {
                notification.style.transform = 'translate(-50%, -50%) scale(0)';
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            }
        }, 5000);
    }
});
// ===== /Обработка формы контактов =====

// ===== Анимация появления блоков при прокрутке =====
document.addEventListener('DOMContentLoaded', function() {
    // Элементы для анимации
    const animatedElements = document.querySelectorAll('.about-content, .advantages-content__item, .services-content__item, .gallery-content__item, .contacts-content__item, .container h2');
    
    // Создаем Intersection Observer
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
                // Отключаем наблюдение после анимации
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1, // Срабатывает когда 10% элемента видно
        rootMargin: '0px 0px -50px 0px' // Небольшой отступ снизу
    });
    
    // Начинаем наблюдение за элементами
    animatedElements.forEach(el => {
        observer.observe(el);
    });
});
// ===== /Анимация появления блоков при прокрутке =====

// ===== Social bar toggle =====
document.addEventListener('DOMContentLoaded', function() {
    const bar = document.getElementById('socialBar');
    const toggle = document.getElementById('socialBarToggle');
    const openBtn = document.getElementById('socialBarOpen');
    if (!bar || !toggle || !openBtn) return;

    toggle.addEventListener('click', function() {
        bar.classList.add('collapsed');
        openBtn.classList.add('visible');
    });

    openBtn.addEventListener('click', function() {
        bar.classList.remove('collapsed');
        openBtn.classList.remove('visible');
    });
});

// ===== Show social bar after slider =====
document.addEventListener('DOMContentLoaded', function() {
    const slider = document.getElementById('slider');
    const bar = document.getElementById('socialBar');
    const openBtn = document.getElementById('socialBarOpen');
    if (!slider || !bar || !openBtn) return;

    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                bar.classList.add('hidden');
                openBtn.classList.add('hidden');
            } else {
                bar.classList.remove('hidden');
                openBtn.classList.remove('hidden');
            }
        });
    }, { threshold: 0 });

    observer.observe(slider);
});
// ===== /Social bar toggle =====

// ===== Активные ссылки навигации при прокрутке =====
document.addEventListener('DOMContentLoaded', function() {
    const header = document.querySelector('header');
    const headerHeight = header ? header.offsetHeight : 0;
    const navLinks = document.querySelectorAll('.nav-menu a[href^="#"]');
    const sections = Array.from(navLinks)
        .map(link => document.querySelector(link.getAttribute('href')))
        .filter(Boolean);

    if (!sections.length) return;

    function activateLink(id) {
        navLinks.forEach(link => {
            link.classList.toggle('active', link.getAttribute('href') === '#' + id);
        });
    }

    function onScroll() {
        const scrollPos = window.pageYOffset || document.documentElement.scrollTop;
        let currentId = sections[0].id;

        for (const section of sections) {
            if (scrollPos + headerHeight >= section.offsetTop) {
                currentId = section.id;
            }
        }
        activateLink(currentId);
    }

    window.addEventListener('scroll', onScroll);
    onScroll();
});
// ===== /Активные ссылки навигации при прокрутке =====
