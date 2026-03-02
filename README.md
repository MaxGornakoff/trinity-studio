# Trinity Studio

Корпоративный сайт студии Trinity Studio на стеке **Laravel 11 + Vue 3 + Inertia + Tailwind CSS**.

## 🎯 О проекте

Сайт с публичным портфолио и закрытой админ-панелью для управления проектами. Доступ к админке имеет только владелец (через email-ограничение).

## ✅ Что уже реализовано

### Frontend (Public)
- ✅ Главная страница с дизайном по макету
  - Hero-секция с описанием студии
  - Блок услуг (6 карточек)
  - География работы (карта с точками)
  - Витрина избранных проектов
  - Диаграмма этапов взаимодействия
- ✅ Кастомная типографика (Montserrat font)
- ✅ Публичное портфолио:
  - Список проектов `/portfolio`
  - Отдельная страница проекта `/portfolio/{slug}`

### Backend & Admin
- ✅ Аутентификация (Laravel Breeze + Inertia)
- ✅ Ограничение доступа к админке по email (middleware `EnsureAdminEmail`)
- ✅ Dashboard с базовой статистикой `/dashboard`
- ✅ CRUD для проектов портфолио `/admin/portfolio-projects`
  - Создание/редактирование/удаление
  - Автоматическая генерация slug
  - Флаги: публикация, избранное, сортировка
- ✅ Artisan-команда для создания админа: `php artisan app:create-admin`

### Database
- ✅ Миграции для всех таблиц
- ✅ Модель `PortfolioProject` с полями:
  - `title`, `slug`, `summary`, `description`
  - `thumbnail_url`, `is_published`, `is_featured`, `order_column`
- ✅ SQLite для разработки (легко переключается на MySQL для production)

### DevOps
- ✅ Docker Compose для MySQL (опционально)
- ✅ Vite для сборки frontend
- ✅ `.gitignore` настроен

## 📋 План развития

### Ближайшие задачи
- [ ] Добавить 2-3 тестовых проекта в портфолио
- [ ] Доработать стилизацию страниц портфолио под финальный дизайн
- [ ] Реализовать загрузку изображений для проектов (thumbnail)
- [ ] Добавить табы/слайдеры на страницы проектов
- [ ] SEO: meta-теги, Open Graph
- [ ] Адаптивность под мобильные устройства

### Дальнейшие улучшения
- [ ] Фильтрация проектов по категориям
- [ ] Форма обратной связи на сайте
- [ ] Блог/новости студии
- [ ] Мультиязычность (RU/EN)
- [ ] Развертывание на production-сервер

## 🚀 Быстрый старт

### 1. Установить зависимости

```bash
composer install
npm install
```

### 2. Настроить `.env`

Скопируй `.env.example` в `.env`:
```bash
cp .env.example .env
```

Для локальной разработки используется **SQLite**:
```dotenv
DB_CONNECTION=sqlite
ADMIN_EMAIL=your@email.com
```

Для production можно переключиться на MySQL:
```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=secret
```

### 3. Создать базу данных

Для SQLite:
```bash
touch database/database.sqlite
```

Для MySQL (с Docker):
```bash
docker-compose up -d
```

### 4. Выполнить миграции

```bash
php artisan migrate
```

### 5. Создать учётную запись администратора

```bash
php artisan app:create-admin --name="Max" --email="your@email.com" --password="SecurePassword123"
```

⚠️ **Важно**: email должен совпадать с `ADMIN_EMAIL` в `.env`!

### 6. Запустить проект

```bash
php artisan serve
npm run dev
```

Открой http://127.0.0.1:8000

## 🔧 Полезные команды

```bash
# Сборка фронтенда для production
npm run build

# Создать нового админа
php artisan app:create-admin --name="Name" --email="email@example.com" --password="password"

# Просмотр всех роутов
php artisan route:list

# Очистить кеши
php artisan optimize:clear
```

## 🗂 Структура проекта

```
trinity-studio/
├── app/
│   ├── Console/Commands/CreateAdminUser.php    # Команда создания админа
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/PortfolioProjectController.php
│   │   │   └── PortfolioController.php
│   │   └── Middleware/EnsureAdminEmail.php     # Защита админки
│   └── Models/PortfolioProject.php
├── resources/
│   ├── js/
│   │   ├── Pages/
│   │   │   ├── Welcome.vue                     # Главная страница
│   │   │   ├── Admin/PortfolioProjects/        # CRUD админки
│   │   │   └── Portfolio/                      # Публичное портфолио
│   │   └── Layouts/AuthenticatedLayout.vue
│   ├── css/app.css                             # Montserrat fonts + Tailwind
│   └── fonts/montserrat/                       # Локальные шрифты
├── routes/
│   ├── web.php                                 # Публичные + админ роуты
│   └── auth.php                                # Аутентификация
├── database/
│   ├── migrations/
│   └── database.sqlite                         # SQLite БД (не в git)
├── docker-compose.yml                          # MySQL для production
└── README.md
```

## 🛡 Безопасность

- Доступ к админке ограничен через middleware `EnsureAdminEmail`
- Только один email (из `.env`) может войти в админку
- Публичной регистрации нет — админ создаётся через artisan-команду
- База данных не попадает в Git (`.gitignore`)

## 📝 Технологии

- **Backend**: Laravel 11, PHP 8.2+
- **Frontend**: Vue 3 (Composition API), Inertia.js
- **Стили**: Tailwind CSS + кастомные шрифты Montserrat
- **БД**: SQLite (dev) / MySQL 8.0 (production)
- **Сборка**: Vite
- **Контейнеризация**: Docker Compose (опционально)
