# Local Run Manual — Trinity Studio

Короткая инструкция по запуску проекта `trinity-studio` на Windows.

## Что это за проект

- Backend: `Laravel`
- Frontend: `Vue 3 + Inertia + Vite`
- Локальная БД для разработки: `SQLite`

---

## Быстрый запуск

Открой PowerShell в корне проекта:

```powershell
cd C:\Users\maxxx\Documents\githubProjects\trinity-studio
```

### 1. Запусти Laravel

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

Ожидаемый результат:

```text
INFO  Server running on [http://127.0.0.1:8000]
```

### 2. Запусти Vite (для режима разработки)

Во втором терминале:

```powershell
npm run dev -- --host 127.0.0.1 --port 5173 --strictPort
```

Ожидаемый результат:

```text
VITE ready
Local: http://localhost:5173/
```

### 3. Открой сайт в браузере

```text
http://127.0.0.1:8000
```

---

## Первый запуск с нуля

Если проект запускается впервые на этом компьютере:

```powershell
composer install
npm install
```

Если файла `.env` нет:

```powershell
Copy-Item .env.example .env
```

Создай локальную SQLite-базу:

```powershell
New-Item .\database\database.sqlite -ItemType File -Force
```

Сгенерируй ключ и накати миграции:

```powershell
php artisan key:generate
php artisan migrate
```

Проверь, что в `.env` используется именно SQLite:

```env
DB_CONNECTION=sqlite
```

---

## Если сайт не открывается

### Проблема 1: `php artisan serve` или `npm run dev` завершается с `Exit Code: 1`

Частая причина — сервер уже запущен, а порт занят.

Проверка портов:

```powershell
Get-NetTCPConnection -LocalPort 8000,5173 -State Listen
```

Остановка процессов:

```powershell
Stop-Process -Id <PID>
```

После этого повтори запуск.

### Проблема 2: старая ошибка MySQL `could not find driver`

Проект раньше падал из-за попытки подключиться к `MySQL` без `pdo_mysql`. Для локальной разработки это не нужно — используется `SQLite`.

Решение:

- в `.env` должно быть `DB_CONNECTION=sqlite`
- файл `database/database.sqlite` должен существовать
- затем выполнить:

```powershell
php artisan migrate
```

### Проблема 3: нужно просто открыть сайт, без фронтенд-разработки

Если сборка уже есть в `public/build`, сайт может открываться и только с Laravel-сервером:

```powershell
php artisan serve
```

Но для live-обновления фронтенда лучше держать включённым и `npm run dev`.

---

## Полезные команды

Очистка кешей Laravel:

```powershell
php artisan optimize:clear
```

Проверка, отвечает ли сайт:

```powershell
curl.exe -I http://127.0.0.1:8000/
```

Если всё в порядке, ответ будет содержать:

```text
HTTP/1.1 200 OK
```

---

## Коротко

Обычно достаточно двух команд:

```powershell
php artisan serve
```

```powershell
npm run dev
```

И затем открыть:

```text
http://127.0.0.1:8000
```
