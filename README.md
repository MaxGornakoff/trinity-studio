# Trinity Studio

Сайт студии на стеке **Laravel + Vue 3 + MySQL**.

## Что уже реализовано

- Публичная главная страница студии
- Публичное портфолио:
  - список проектов `/portfolio`
  - страница проекта `/portfolio/{slug}`
- Админка (через auth):
  - dashboard `/dashboard`
  - CRUD проектов портфолио `/admin/portfolio-projects`
- Авторизация через Laravel Breeze (Inertia + Vue 3)

## Структура данных проекта портфолио

Таблица `portfolio_projects`:

- `title`
- `slug` (unique)
- `summary`
- `content`
- `thumbnail_url`
- `project_url`
- `order_column`
- `is_featured`
- `is_published`
- `published_at`

## Быстрый старт

1. Установить зависимости:

```bash
composer install
npm install
```

2. Настроить `.env` (MySQL):

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=
ADMIN_EMAIL=you@example.com
```

3. Убедиться, что в PHP включены расширения:

- `pdo_mysql`
- `mbstring`

4. Выполнить миграции:

```bash
php artisan migrate
```

5. Запустить проект:

```bash
php artisan serve
npm run dev
```

## Полезные команды

- Сборка фронтенда: `npm run build`
- Тесты Laravel: `php artisan test`
- Создать админа: `php artisan app:create-admin --name="Max" --email="you@example.com" --password="your_password"`

## Примечание по окружению

В текущем окружении миграции и тесты не выполняются, пока не активированы PHP-расширения `pdo_mysql` и `mbstring`.
