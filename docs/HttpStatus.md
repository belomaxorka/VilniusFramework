# HttpStatus - Централизованное управление HTTP статус-кодами

## Обзор

`HttpStatus` - это утилитарный класс, который предоставляет централизованное хранилище всех HTTP статус-кодов и их описаний.

### 🎯 Зачем нужен?

До введения `HttpStatus`, информация о статус-кодах дублировалась в трёх местах:
- ❌ `ErrorRenderer::getErrorTitle()` (~12 кодов)
- ❌ `ResponseCollector::getStatusText()` (~60 кодов)
- ❌ `abort()` функция (~7 кодов)

Теперь вся логика находится в **одном месте** ✅

## Использование

### Получить текст статус-кода

```php
use Core\Http\HttpStatus;

echo HttpStatus::getText(200); // "OK"
echo HttpStatus::getText(404); // "Not Found"
echo HttpStatus::getText(500); // "Internal Server Error"
echo HttpStatus::getText(418); // "I'm a teapot"
echo HttpStatus::getText(999); // "Unknown Status"
```

### Получить описание категории

```php
echo HttpStatus::getDescription(200);
// ✅ Success - The request was successfully received, understood, and accepted.

echo HttpStatus::getDescription(404);
// ❌ Client Error - The request contains bad syntax or cannot be fulfilled.

echo HttpStatus::getDescription(500);
// 🔥 Server Error - The server failed to fulfill an apparently valid request.
```

### Получить цвет для статус-кода

```php
echo HttpStatus::getColor(200); // "#4caf50" (зеленый)
echo HttpStatus::getColor(404); // "#ff5722" (красно-оранжевый)
echo HttpStatus::getColor(500); // "#f44336" (красный)
```

### Получить категорию

```php
echo HttpStatus::getCategory(200); // 2
echo HttpStatus::getCategory(404); // 4
echo HttpStatus::getCategory(500); // 5
```

### Проверки статус-кодов

```php
// Успешный ответ (2xx)
HttpStatus::isSuccess(200); // true
HttpStatus::isSuccess(201); // true
HttpStatus::isSuccess(404); // false

// Ошибка клиента (4xx)
HttpStatus::isClientError(404); // true
HttpStatus::isClientError(403); // true
HttpStatus::isClientError(500); // false

// Ошибка сервера (5xx)
HttpStatus::isServerError(500); // true
HttpStatus::isServerError(503); // true
HttpStatus::isServerError(404); // false

// Редирект (3xx)
HttpStatus::isRedirection(301); // true
HttpStatus::isRedirection(302); // true
HttpStatus::isRedirection(200); // false
```

### Получить все статус-коды

```php
$allCodes = HttpStatus::getAll();
// [
//     100 => 'Continue',
//     200 => 'OK',
//     404 => 'Not Found',
//     ...
// ]
```

## Интеграция

### ErrorRenderer

```php
// core/ErrorRenderer.php
private static function getErrorTitle(int $code): string
{
    return HttpStatus::getText($code);
}
```

### ResponseCollector (Debug Toolbar)

```php
// core/DebugToolbar/Collectors/ResponseCollector.php
$this->data = [
    'status_code' => $statusCode,
    'status_text' => HttpStatus::getText($statusCode),
    // ...
];

$statusColor = HttpStatus::getColor($this->data['status_code']);
$description = HttpStatus::getDescription($this->data['status_code']);
```

### abort() функция

```php
// core/helpers/app/http.php
function abort(int $code = 404, string $message = ''): never
{
    if (empty($message)) {
        $message = HttpStatus::getText($code);
    }
    // ...
}
```

## Поддерживаемые статус-коды

### 1xx Informational

| Код | Описание |
|-----|----------|
| 100 | Continue |
| 101 | Switching Protocols |
| 102 | Processing |
| 103 | Early Hints |

### 2xx Success

| Код | Описание |
|-----|----------|
| 200 | OK |
| 201 | Created |
| 202 | Accepted |
| 203 | Non-Authoritative Information |
| 204 | No Content |
| 205 | Reset Content |
| 206 | Partial Content |
| 207 | Multi-Status |
| 208 | Already Reported |
| 226 | IM Used |

### 3xx Redirection

| Код | Описание |
|-----|----------|
| 300 | Multiple Choices |
| 301 | Moved Permanently |
| 302 | Found |
| 303 | See Other |
| 304 | Not Modified |
| 305 | Use Proxy |
| 307 | Temporary Redirect |
| 308 | Permanent Redirect |

### 4xx Client Errors

| Код | Описание |
|-----|----------|
| 400 | Bad Request |
| 401 | Unauthorized |
| 402 | Payment Required |
| 403 | Forbidden |
| 404 | Not Found |
| 405 | Method Not Allowed |
| 406 | Not Acceptable |
| 407 | Proxy Authentication Required |
| 408 | Request Timeout |
| 409 | Conflict |
| 410 | Gone |
| 411 | Length Required |
| 412 | Precondition Failed |
| 413 | Payload Too Large |
| 414 | URI Too Long |
| 415 | Unsupported Media Type |
| 416 | Range Not Satisfiable |
| 417 | Expectation Failed |
| 418 | I'm a teapot |
| 421 | Misdirected Request |
| 422 | Unprocessable Entity |
| 423 | Locked |
| 424 | Failed Dependency |
| 425 | Too Early |
| 426 | Upgrade Required |
| 428 | Precondition Required |
| 429 | Too Many Requests |
| 431 | Request Header Fields Too Large |
| 451 | Unavailable For Legal Reasons |

### 5xx Server Errors

| Код | Описание |
|-----|----------|
| 500 | Internal Server Error |
| 501 | Not Implemented |
| 502 | Bad Gateway |
| 503 | Service Unavailable |
| 504 | Gateway Timeout |
| 505 | HTTP Version Not Supported |
| 506 | Variant Also Negotiates |
| 507 | Insufficient Storage |
| 508 | Loop Detected |
| 510 | Not Extended |
| 511 | Network Authentication Required |

## Цветовая схема

| Категория | Цвет | Hex | Использование |
|-----------|------|-----|---------------|
| 1xx Informational | 🔵 Синий | #2196f3 | Информационные |
| 2xx Success | 🟢 Зеленый | #4caf50 | Успешные |
| 3xx Redirection | 🟠 Оранжевый | #ff9800 | Редиректы |
| 4xx Client Error | 🔴 Красно-оранжевый | #ff5722 | Ошибки клиента |
| 5xx Server Error | 🔴 Красный | #f44336 | Ошибки сервера |
| Unknown | ⚫ Серый | #757575 | Неизвестные |

## API Reference

### `getText(int $code): string`

Получить текстовое описание HTTP статус-кода.

**Параметры:**
- `$code` - HTTP статус-код

**Возвращает:** Текстовое описание или "Unknown Status"

---

### `getDescription(int $code): string`

Получить полное описание категории статус-кода.

**Параметры:**
- `$code` - HTTP статус-код

**Возвращает:** Полное описание категории с эмодзи

---

### `getColor(int $code): string`

Получить HEX цвет для статус-кода.

**Параметры:**
- `$code` - HTTP статус-код

**Возвращает:** HEX цвет (например, "#4caf50")

---

### `getCategory(int $code): int`

Получить категорию статус-кода (1, 2, 3, 4, 5).

**Параметры:**
- `$code` - HTTP статус-код

**Возвращает:** Категория (1-5) или 0 для неизвестных

---

### `isSuccess(int $code): bool`

Проверить, является ли код успешным (2xx).

---

### `isClientError(int $code): bool`

Проверить, является ли код ошибкой клиента (4xx).

---

### `isServerError(int $code): bool`

Проверить, является ли код ошибкой сервера (5xx).

---

### `isRedirection(int $code): bool`

Проверить, является ли код редиректом (3xx).

---

### `getAll(): array<int, string>`

Получить все доступные статус-коды в виде массива [код => текст].

## Преимущества

✅ **Единый источник истины** - все статус-коды в одном месте  
✅ **Консистентность** - одинаковые описания во всём фреймворке  
✅ **Легко расширять** - добавить новый код нужно только в одном месте  
✅ **Типобезопасность** - статические методы с типизацией  
✅ **Удобство** - хелперы для проверки категорий  
✅ **Поддержка цветов** - для визуализации в UI

## Где используется

1. ✅ **ErrorRenderer** - страницы ошибок
2. ✅ **ResponseCollector** - Debug Toolbar
3. ✅ **abort()** - хелпер функция
4. ✅ **Response** - класс HTTP ответа (константы остаются для BC)

## Совместимость

Класс полностью обратно совместим. Старые константы в `Response` классе оставлены для backward compatibility:

```php
// Старый способ (всё ещё работает)
Response::HTTP_NOT_FOUND; // 404

// Новый способ (рекомендуется)
HttpStatus::getText(404); // "Not Found"
```

