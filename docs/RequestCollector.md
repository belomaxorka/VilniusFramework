# Request Collector

Request Collector - это коллектор для Debug Toolbar, который собирает и отображает полную информацию о HTTP-запросе.

## Возможности

Request Collector автоматически собирает следующую информацию о каждом запросе:

### 1. Базовая информация о запросе
- **HTTP метод** (GET, POST, PUT, DELETE, etc.)
- **URI** - путь запроса
- **Full URL** - полный URL с протоколом и хостом
- **Protocol** - версия HTTP протокола
- **Remote Address** - IP-адрес клиента (с учетом proxy)
- **Request Time** - время получения запроса

### 2. GET параметры
Отображает все параметры, переданные в query string.

### 3. POST данные
Показывает все данные, отправленные через POST-запрос.

### 4. Загруженные файлы
Если в запросе были загружены файлы, отображается таблица с информацией:
- Имя файла
- MIME тип
- Размер
- Статус загрузки (успех/ошибка)
- Временное имя файла

### 5. Cookies
Список всех cookies, отправленных клиентом.

### 6. HTTP Headers
Все HTTP заголовки запроса.

### 7. Server Variables
Переменные окружения и серверные параметры (с фильтрацией чувствительных данных).

## Использование

Request Collector автоматически регистрируется при инициализации Debug Toolbar и не требует дополнительной настройки.

### Приоритет

Request Collector имеет приоритет `90`, что означает, что он отображается одним из первых после Overview (приоритет 100).

### Badge

В заголовке вкладки отображается HTTP-метод запроса с цветовой кодировкой:
- 🟢 **GET** - зеленый
- 🔵 **POST** - синий
- 🟠 **PUT** - оранжевый
- 🟣 **PATCH** - фиолетовый
- 🔴 **DELETE** - красный
- ⚫ **Другие** - серый

### Header Stats

В шапке toolbar отображается краткая информация: HTTP-метод и путь запроса.

## Безопасность

Request Collector обеспечивает многоуровневую защиту чувствительных данных:

### В любом режиме (Development/Production)

Автоматически скрываются переменные, содержащие:
- `PHP_AUTH_PW` → `***HIDDEN***`
- `PHP_AUTH_USER` → `***HIDDEN***`
- `HTTP_AUTHORIZATION` → `***HIDDEN***`
- `DATABASE_URL` → `***HIDDEN***`
- `DB_PASSWORD` → `***HIDDEN***`
- `API_KEY` → `***HIDDEN***`
- `SECRET_KEY` → `***HIDDEN***`

И любые переменные, содержащие в имени: `PASSWORD`, `SECRET`, `TOKEN`, `KEY`, `AUTH`, `CREDENTIAL`

### В Production режиме

В production режиме (`APP_ENV=production`) дополнительная защита:

🔒 **Server Variables скрыты** (кроме безопасных):
- Показываются только: `REQUEST_METHOD`, `REQUEST_URI`, `REQUEST_TIME`, `SERVER_PROTOCOL`
- Все остальные переменные: `***HIDDEN (PRODUCTION MODE)***`
- Визуальная индикация: красный badge "🔒 PRODUCTION MODE"
- Предупреждающее сообщение перед таблицей

Подробнее: [Request Collector Security](RequestCollectorSecurity.md)

## Определение IP-адреса

Collector корректно определяет реальный IP-адрес клиента даже при использовании прокси-серверов или балансировщиков нагрузки, проверяя следующие заголовки в порядке приоритета:
1. `HTTP_CLIENT_IP`
2. `HTTP_X_FORWARDED_FOR`
3. `HTTP_X_FORWARDED`
4. `HTTP_X_CLUSTER_CLIENT_IP`
5. `HTTP_FORWARDED_FOR`
6. `HTTP_FORWARDED`
7. `REMOTE_ADDR`

## Определение протокола (HTTP/HTTPS)

Collector корректно определяет HTTPS соединения, проверяя:
- Переменную `$_SERVER['HTTPS']`
- Порт (443 = HTTPS)
- Заголовок `HTTP_X_FORWARDED_PROTO`

## Пример вывода

```
🌐 Request Information

📋 Basic Info
Method: [GET]
URI: /user/john
Full URL: http://localhost/user/john
Protocol: HTTP/1.1
Remote Address: 127.0.0.1
Request Time: 2025-09-30 10:30:45

📋 GET Parameters (2)
┌────────┬─────────┐
│ Key    │ Value   │
├────────┼─────────┤
│ page   │ 1       │
│ limit  │ 10      │
└────────┴─────────┘

📋 POST Parameters
No POST data

📋 Cookies (1)
┌────────────┬─────────────┐
│ Key        │ Value       │
├────────────┼─────────────┤
│ session_id │ abc123xyz   │
└────────────┴─────────────┘
```

## Интеграция

Request Collector интегрирован в `Core\DebugToolbar` и автоматически загружается при включении Debug режима.

## Производительность

Request Collector имеет минимальное влияние на производительность, так как:
- Собирает данные только в Debug режиме
- Использует уже существующие супергlobals (`$_GET`, `$_POST`, `$_SERVER`, etc.)
- Не выполняет тяжелых операций или запросов к БД

## Расширение функциональности

Чтобы добавить дополнительные данные в Request Collector:

```php
use Core\DebugToolbar;

$collector = DebugToolbar::getCollector('request');
if ($collector) {
    // Можно расширить функциональность
}
```

## API

### Публичные методы

- `getName()` - возвращает `'request'`
- `getTitle()` - возвращает `'Request'`
- `getIcon()` - возвращает `'🌐'`
- `getBadge()` - возвращает HTTP-метод
- `getPriority()` - возвращает `90`
- `collect()` - собирает данные о запросе
- `render()` - генерирует HTML для отображения
- `getHeaderStats()` - возвращает данные для header toolbar

## Совместимость

Request Collector совместим с:
- PHP 8.0+
- Любыми веб-серверами (Apache, Nginx, etc.)
- FastCGI, PHP-FPM, встроенный сервер PHP

## См. также

- [Debug Toolbar](DebugToolbar.md)
- [Debug Quick Start](DebugQuickStart.md)
- [Custom Collectors](DebugToolbarCollectors.md)
- [Request Collector Security](RequestCollectorSecurity.md) - подробности о защите данных

