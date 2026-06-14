# WB API Loader

Laravel 8 приложение для загрузки данных с тестового API и сохранения в MySQL.

## Стек
- PHP 8.1
- Laravel 8
- MySQL 8

## Сущности
Загружаются 4 типа данных в соответствующие таблицы:
- `orders` — заказы (`/api/orders`)
- `sales` — продажи (`/api/sales`)
- `stocks` — склады (`/api/stocks`, только за текущий день)
- `incomes` — доходы (`/api/incomes`)

## Установка

```bash
git clone <repo-url>
cd wb-api-loader
composer install
cp .env.example .env
php artisan key:generate
```

Настройте подключение к БД в `.env`, затем выполните миграции:

```bash
php artisan migrate
```

## Загрузка данных

Каждая сущность загружается отдельной командой:

```bash
php artisan fetch:orders --dateFrom=2025-01-01 --dateTo=2025-06-01
php artisan fetch:sales --dateFrom=2025-01-01 --dateTo=2025-06-01
php artisan fetch:stocks
php artisan fetch:incomes --dateFrom=2025-01-01 --dateTo=2025-06-01
```

Либо одной командой для всего сразу:

```bash
php artisan fetch:all
```

Параметры `--dateFrom` и `--dateTo` опциональны:
- Если не указаны, `dateFrom` по умолчанию `2025-01-01`, `dateTo` — текущая дата.
- Для `stocks` `dateFrom` по умолчанию — текущая дата (эндпоинт отдаёт остатки только за сегодня).

## Команды

| Команда | Эндпоинт | Параметры |
|---|---|---|
| `fetch:orders` | `/api/orders` | `--dateFrom`, `--dateTo` |
| `fetch:sales` | `/api/sales` | `--dateFrom`, `--dateTo` |
| `fetch:stocks` | `/api/stocks` | `--dateFrom` |
| `fetch:incomes` | `/api/incomes` | `--dateFrom`, `--dateTo` |
| `fetch:all` | все 4 эндпоинта | те же, что у отдельных команд |

## Конфигурация

Переменные окружения (`.env`):

| Переменная | Описание |
|---|---|
| `WB_API_URL` | Базовый URL API |
| `WB_API_KEY` | Ключ авторизации |
