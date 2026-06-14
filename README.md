# WB API Loader

Laravel 8 приложение для загрузки данных с тестового WB API и сохранения в MySQL.

## Стек
- PHP 8.1
- Laravel 8
- MySQL 8
- Guzzle HTTP (через Laravel HTTP Client)

## Структура

В БД создаются 4 таблицы, соответствующие эндпоинтам API:

| Таблица | Эндпоинт | Описание |
|---|---|---|
| `orders` | `/api/orders` | Заказы |
| `sales` | `/api/sales` | Продажи |
| `stocks` | `/api/stocks` | Складские остатки (только за текущий день) |
| `incomes` | `/api/incomes` | Доходы |

### Поля таблицы `orders`
g_number, date, last_change_date, supplier_article, tech_size, barcode, total_price, discount_percent, warehouse_name, oblast, income_id, odid, nm_id, subject, category, brand, is_cancel, cancel_dt

### Поля таблицы `sales`
g_number, date, last_change_date, supplier_article, tech_size, barcode, total_price, discount_percent, is_supply, is_realization, promo_code_discount, warehouse_name, country_name, oblast_okrug_name, region_name, income_id, sale_id, odid, spp, for_pay, finished_price, price_with_disc, nm_id, subject, category, brand, is_storno

### Поля таблицы `stocks`
date, last_change_date, supplier_article, tech_size, barcode, quantity, is_supply, is_realization, quantity_full, warehouse_name, in_way_to_client, in_way_from_client, nm_id, subject, category, brand, sc_code, price, discount

### Поля таблицы `incomes`
income_id, number, date, last_change_date, supplier_article, tech_size, barcode, quantity, total_price, date_close, warehouse_name, nm_id

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

## Доступные команды

| Команда | Эндпоинт | Параметры |
|---|---|---|
| `fetch:orders` | `/api/orders` | `--dateFrom`, `--dateTo`, `--truncate` |
| `fetch:sales` | `/api/sales` | `--dateFrom`, `--dateTo`, `--truncate` |
| `fetch:stocks` | `/api/stocks` | `--dateFrom`, `--truncate` |
| `fetch:incomes` | `/api/incomes` | `--dateFrom`, `--dateTo`, `--truncate` |
| `fetch:all` | все 4 эндпоинта | `--dateFrom`, `--dateTo`, `--truncate` |

Опция `--truncate` очищает таблицу перед загрузкой.

## Загрузка данных

```bash
# Все типы за один запуск
php artisan fetch:all --dateFrom=2025-01-01 --dateTo=2025-06-01

# С очисткой таблиц перед загрузкой
php artisan fetch:all --dateFrom=2025-01-01 --dateTo=2025-06-01 --truncate

# Отдельные команды
php artisan fetch:orders --dateFrom=2025-01-01 --dateTo=2025-06-01
php artisan fetch:sales --dateFrom=2025-01-01 --dateTo=2025-06-01
php artisan fetch:incomes --dateFrom=2025-01-01 --dateTo=2025-06-01
php artisan fetch:stocks
```

Параметры `--dateFrom` и `--dateTo` опциональны:
- Если не указаны, `dateFrom` по умолчанию `2025-01-01`, `dateTo` — текущая дата.
- Для `stocks` `dateFrom` по умолчанию — текущая дата (эндпоинт отдаёт остатки только за сегодня).

## Конфигурация

Переменные окружения (`.env`):

| Переменная | Описание |
|---|---|
| `WB_API_URL` | Базовый URL API |
| `WB_API_KEY` | Ключ авторизации |

## Особенности реализации

- **Пагинация**: API отдаёт по 500 записей на страницу. Команды автоматически перебирают все страницы.
- **Обработка rate-limit (429)**: при ответе 429 клиент ждёт 5 секунд и повторяет запрос. До 5 попыток.
- **Обработка 5xx**: до 3 повторов с задержкой 2 секунды.
- **Задержка между запросами**: 500 мс, чтобы не упереться в лимиты API.
- **Батчевая вставка**: данные сохраняются через `Model::insert()` пачками по 500 строк.
- **Дубли**: API может возвращать одну и ту же запись несколько раз (например, при обновлении заказа). Для гарантированно чистой загрузки используйте опцию `--truncate`.

## Удалённая БД (production)

Развёрнута на бесплатном хостинге. Подключение:

Хост: <будет добавлен после деплоя>
Порт: <будет добавлен>
База данных: <будет добавлена>
Пользователь: <будет добавлен>
Пароль: <будет добавлен>

Таблицы в удалённой БД: `orders`, `sales`, `stocks`, `incomes` (структура такая же, как описана выше).

Для подключения можно использовать любой MySQL-клиент (DBeaver, MySQL Workbench, mysql CLI):

```bash
mysql -h <host> -P <port> -u <user> -p <database>
```
