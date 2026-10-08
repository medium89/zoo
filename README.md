# Zooland

Веб-приложение для зоогостиницы: публичный сайт и административная панель для работы с клиентами, питомцами, бронированиями и услугами.

## Возможности

- Публичный сайт с услугами, галереей, отзывами, статьями, формой обратной связи и календарём занятости.
- Административная панель по адресу `/zooadmin` с разграничением доступа для администраторов.
- Ведение клиентов, питомцев, фотографий, категорий, тегов и их связей.
- Управление бронированиями и заказами услуг: состав заказа, тарифы, статусы, архив и экспорт.
- Задачи по передержкам и уведомления в Telegram.
- Управление контентом сайта: слайдер, блок «О нас», преимущества, услуги, галереи, социальные ссылки, навигация и изображения.
- Статьи с обложками, SEO-полями, комментариями и модерацией.
- Карта клиентов и питомцев, интеграция с Яндекс Картами.
- Генерация sitemap и управление настройками индексации.

## Стек

- PHP 8.1+ и Laravel 10;
- MySQL;
- Blade, Bootstrap 5, Sass и Vite;
- очереди Laravel с драйвером `database`;
- Telegram Bot API, Яндекс Карты, reCAPTCHA и AITunnel — подключаются при необходимости через переменные окружения.

## Быстрый запуск

### Требования

- PHP 8.1 или новее с расширениями, требуемыми Laravel;
- Composer;
- Node.js и npm;
- MySQL.

### Установка

```bash
git clone https://github.com/medium89/zoo.git
cd zoo

composer install
npm ci
cp .env.example .env
php artisan key:generate
```

Заполните в `.env` как минимум параметры подключения к MySQL:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zooland
DB_USERNAME=root
DB_PASSWORD=
```

После создания базы данных выполните миграции и соберите фронтенд:

```bash
php artisan migrate
npm run build
```

Для разработки в отдельных терминалах запустите:

```bash
php artisan serve
npm run dev
```

По умолчанию приложение будет доступно по адресу `http://127.0.0.1:8000`.

## Настройка интеграций

Шаблон `.env.example` содержит все доступные параметры. Чаще всего используются:

| Интеграция | Переменные |
| --- | --- |
| Telegram | `TELEGRAM_BOT_TOKEN`, `TELEGRAM_CHAT_ID`, `TELEGRAM_ALLOWED_USER_IDS`, `TELEGRAM_WEBHOOK_SECRET` |
| Яндекс Карты | `YANDEX_MAPS_API_KEY` |
| reCAPTCHA | `RECAPTCHA_SITE_KEY`, `RECAPTCHA_SECRET` |
| AITunnel | `AITUNNEL_BASE_URL`, `AITUNNEL_API_KEY`, `AITUNNEL_CHAT_MODEL`, `AITUNNEL_STT_MODEL` |

Для Telegram рекомендуется использовать `QUEUE_CONNECTION=database`. После миграций проверьте, что вебхук бота указывает на маршрут, настроенный в приложении, и задайте секрет вебхука.

## Фоновые задачи

Расписание определено в `app/Console/Kernel.php`. На сервере нужно запускать Laravel Scheduler каждую минуту:

```cron
* * * * * cd /path/to/zooland && php artisan schedule:run >> /dev/null 2>&1
```

Он обрабатывает очередь Telegram, отправляет уведомления о бронированиях и задачах, а также ежедневно архивирует завершённые передержки.

## Тесты и проверка стиля

```bash
php artisan test
./vendor/bin/pint --test
```

Перед отправкой изменений в production также соберите статические ресурсы:

```bash
npm run build
```

## Деплой по FTP

В проекте есть скрипт `scripts/deploy-git-ftp.sh` для инкрементальной загрузки через `git-ftp`.

```bash
cp .env.deploy.example .env.deploy
# заполните GIT_FTP_URL, GIT_FTP_USER и GIT_FTP_PASSWORD
./scripts/deploy-git-ftp.sh
```

Для первой загрузки задайте `GIT_FTP_INIT=1` в `.env.deploy`; для следующих — `GIT_FTP_INIT=0`. Скрипт по умолчанию выполняет `npm ci && npm run build` до отправки файлов. Никогда не добавляйте `.env` и `.env.deploy` в Git.

## Структура проекта

```text
app/                 Контроллеры, модели, сервисы, команды и задания очереди
config/              Конфигурация Laravel
database/migrations/ Схема базы данных
resources/           Blade-шаблоны, стили и JavaScript
routes/web.php       Публичные и административные маршруты
scripts/             Служебные скрипты, включая FTP-деплой
tests/               Unit- и feature-тесты
```

## Лицензия

Проект является частным. Использование и распространение кода допускаются только с разрешения владельца.
