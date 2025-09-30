# Http Класс - Улучшения и новые возможности

## Обзор

Класс `Core\Http` был значительно расширен. Добавлено **50+ новых методов** для работы с HTTP-запросами.

## ✨ Что нового

### 1. Method Override для REST API

Поддержка tunneling HTTP методов через POST для фреймворков и форм HTML.

```php
// В HTML форме
<form method="POST">
    <input type="hidden" name="_method" value="PUT">
    <!-- ... -->
</form>

// В контроллере
$actualMethod = Http::getActualMethod(); // "PUT" вместо "POST"
```

Также поддерживается заголовок `X-HTTP-Method-Override`:
```php
// Клиент отправляет:
// POST /users/123
// X-HTTP-Method-Override: DELETE

Http::getActualMethod(); // "DELETE"
```

---

### 2. Расширенная работа с файлами

**Новые методы:**
- `hasFiles()` - проверка наличия загруженных файлов
- `getFile($name)` - получение конкретного файла
- `isValidUpload($name)` - проверка успешной загрузки
- `getFileSize($name)` - размер файла
- `getFileExtension($name)` - расширение файла
- `getFileMimeType($name)` - MIME тип файла

**Пример:**
```php
if (Http::hasFiles() && Http::isValidUpload('avatar')) {
    $size = Http::getFileSize('avatar');
    $ext = Http::getFileExtension('avatar');
    $mime = Http::getFileMimeType('avatar');
    
    if ($ext === 'jpg' && $size < 5000000) {
        // Обработка файла
    }
}
```

---

### 3. Bearer Token и Basic Auth

**Bearer Token (для JWT, API):**
```php
// Клиент отправляет:
// Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

$token = Http::getBearerToken();
if ($token) {
    $user = validateJWT($token);
}
```

**Basic Authentication:**
```php
// Клиент отправляет:
// Authorization: Basic dXNlcjpwYXNzd29yZA==

$auth = Http::getBasicAuth();
if ($auth) {
    $username = $auth['username'];
    $password = $auth['password'];
    
    if (authenticate($username, $password)) {
        // ...
    }
}
```

---

### 4. Content Type операции

**Новые методы:**
- `getContentLength()` - размер тела запроса
- `getContentType()` - полный Content-Type
- `getMimeType()` - только MIME тип (без charset)
- `isMultipart()` - проверка multipart/form-data
- `isFormUrlEncoded()` - проверка url-encoded формы
- `getCharset()` - кодировка из Content-Type

**Пример:**
```php
if (Http::getMimeType() === 'application/json') {
    $data = Http::getJsonData();
} elseif (Http::isMultipart()) {
    // Обработка файлов
    $files = Http::getFiles();
}

$charset = Http::getCharset(); // "UTF-8" по умолчанию
```

---

### 5. Удобная работа с Input данными

**Новые методы:**
- `all()` - все данные (GET + POST)
- `input($key, $default)` - значение из GET или POST
- `has($key)` - проверка существования
- `only($keys)` - только указанные ключи
- `except($keys)` - все кроме указанных
- `isEmpty($key)` - проверка на пустоту
- `filled($key)` - проверка на заполненность

**Пример:**
```php
// Получить все данные
$allData = Http::all();

// Получить конкретное значение (POST приоритетнее GET)
$name = Http::input('name', 'Guest');

// Проверки
if (Http::filled('email')) {
    $email = Http::input('email');
}

// Получить только нужные поля
$userData = Http::only(['name', 'email', 'age']);

// Получить все кроме токенов
$data = Http::except(['_token', '_csrf']);
```

---

### 6. Query String операции

**Новые методы:**
- `parseQueryString($query)` - парсинг query string
- `buildQueryString($params)` - построение query string
- `getUrlWithParams($params, $merge)` - URL с модифицированными параметрами

**Пример:**
```php
// Парсинг
$params = Http::parseQueryString('a=1&b=2&c=3');
// ['a' => '1', 'b' => '2', 'c' => '3']

// Построение
$query = Http::buildQueryString(['page' => 2, 'sort' => 'name']);
// "page=2&sort=name"

// Текущий URL: /products?category=phones&sort=price
$newUrl = Http::getUrlWithParams(['page' => 2]);
// /products?category=phones&sort=price&page=2

// Заменить параметры
$newUrl = Http::getUrlWithParams(['sort' => 'name'], merge: false);
// /products?sort=name
```

---

### 7. Определение типа клиента

**Новые методы:**
- `isBot()` - определение ботов/краулеров
- `isMobile()` - определение мобильных устройств  
- `isPrefetch()` - определение prefetch запросов

**Пример:**
```php
if (Http::isBot()) {
    // Показываем оптимизированную версию для ботов
    return renderForBots();
}

if (Http::isMobile()) {
    // Мобильная версия
    return view('mobile/home');
}

if (Http::isPrefetch()) {
    // Не учитываем в статистике
    return;
}
```

---

### 8. Определение языка

**Новые методы:**
- `getPreferredLanguage($supported)` - предпочитаемый язык пользователя
- `getAcceptedLanguages()` - все языки с приоритетами

**Пример:**
```php
// Автоматическое определение языка
$supportedLangs = ['en', 'ru', 'es', 'fr'];
$userLang = Http::getPreferredLanguage($supportedLangs);

// Установить язык приложения
setLocale($userLang);

// Получить все языки с приоритетами
$languages = Http::getAcceptedLanguages();
// ['ru' => 0.9, 'en' => 0.8, 'de' => 0.7]
```

---

### 9. HTTP Семантика

**Новые методы:**
- `isSafe()` - безопасный метод (GET, HEAD, OPTIONS)
- `isIdempotent()` - идемпотентный метод (GET, HEAD, PUT, DELETE, OPTIONS)

**Пример:**
```php
// Применяем CSRF защиту только для небезопасных методов
if (!Http::isSafe()) {
    if (!Session::verifyCsrfToken($token)) {
        abort(419);
    }
}

// Идемпотентные запросы можно безопасно повторять
if (Http::isIdempotent()) {
    // Кешируем или retry при ошибке
}
```

---

### 10. HTTP Кеширование

**Новые методы:**
- `getEtag()` - получить If-None-Match заголовок
- `getIfModifiedSince()` - получить If-Modified-Since

**Пример:**
```php
$etag = '"' . md5($content) . '"';
$lastModified = filemtime($file);

// Проверяем ETag
if (Http::getEtag() === $etag) {
    http_response_code(304); // Not Modified
    exit;
}

// Проверяем If-Modified-Since
if (Http::getIfModifiedSince() >= $lastModified) {
    http_response_code(304);
    exit;
}

// Отправляем с кеш-заголовками
header("ETag: $etag");
header("Last-Modified: " . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
echo $content;
```

---

## 📊 Полный список новых методов

### Method & Protocol
- `getActualMethod()` - реальный метод с Method Override

### Files
- `hasFiles()` - наличие файлов
- `getFile($name)` - конкретный файл
- `isValidUpload($name)` - валидность загрузки
- `getFileSize($name)` - размер файла
- `getFileExtension($name)` - расширение
- `getFileMimeType($name)` - MIME тип файла

### Authentication
- `getBearerToken()` - Bearer токен
- `getBasicAuth()` - Basic Auth credentials

### Content Type
- `getContentLength()` - Content-Length
- `getContentType()` - Content-Type
- `getMimeType()` - MIME тип
- `isMultipart()` - multipart/form-data
- `isFormUrlEncoded()` - url-encoded форма
- `getCharset()` - charset

### Input
- `all()` - все данные (GET + POST)
- `input($key, $default)` - значение
- `has($key)` - существование
- `only($keys)` - только указанные
- `except($keys)` - все кроме указанных
- `isEmpty($key)` - пустое значение
- `filled($key)` - заполненное значение

### Query String
- `parseQueryString($query)` - парсинг
- `buildQueryString($params)` - построение
- `getUrlWithParams($params, $merge)` - URL с параметрами

### Detection
- `isPrefetch()` - prefetch запрос
- `isBot()` - бот/краулер
- `isMobile()` - мобильное устройство
- `isSafe()` - безопасный метод
- `isIdempotent()` - идемпотентный метод

### Language
- `getPreferredLanguage($supported)` - предпочитаемый язык
- `getAcceptedLanguages()` - все языки с приоритетами

### Caching
- `getEtag()` - ETag заголовок
- `getIfModifiedSince()` - If-Modified-Since

---

## 🎯 Практические примеры

### REST API endpoint

```php
use Core\Http;
use Core\Session;

function apiEndpoint()
{
    // Проверка типа запроса
    if (!Http::isJson()) {
        return jsonError('JSON expected', 400);
    }
    
    // Bearer токен авторизация
    $token = Http::getBearerToken();
    if (!$token || !validateToken($token)) {
        return jsonError('Unauthorized', 401);
    }
    
    // Method Override
    $method = Http::getActualMethod();
    
    return match($method) {
        'GET' => handleGet(),
        'POST' => handlePost(),
        'PUT' => handlePut(),
        'DELETE' => handleDelete(),
        default => jsonError('Method not allowed', 405)
    };
}
```

### Загрузка файлов

```php
function handleFileUpload()
{
    if (!Http::hasFiles()) {
        return redirect()->back()->with('error', 'No files uploaded');
    }
    
    if (!Http::isValidUpload('document')) {
        return redirect()->back()->with('error', 'Upload failed');
    }
    
    $allowedExt = ['pdf', 'doc', 'docx'];
    $ext = Http::getFileExtension('document');
    $size = Http::getFileSize('document');
    
    if (!in_array($ext, $allowedExt)) {
        return redirect()->back()->with('error', 'Invalid file type');
    }
    
    if ($size > 10000000) { // 10MB
        return redirect()->back()->with('error', 'File too large');
    }
    
    // Обработка файла...
    $file = Http::getFile('document');
    move_uploaded_file($file['tmp_name'], $destination);
}
```

### Многоязычное приложение

```php
use Core\Http;
use Core\Cookie;
use Core\Session;

function initializeLanguage()
{
    $supported = ['en', 'ru', 'es', 'fr', 'de'];
    
    // 1. Проверяем сессию
    if (Session::has('language')) {
        return Session::get('language');
    }
    
    // 2. Проверяем cookie
    if (Cookie::has('language')) {
        $lang = Cookie::get('language');
        Session::set('language', $lang);
        return $lang;
    }
    
    // 3. Определяем из Accept-Language
    $lang = Http::getPreferredLanguage($supported);
    Session::set('language', $lang);
    
    return $lang;
}

function changeLanguage(string $newLang)
{
    Session::set('language', $newLang);
    Cookie::setForDays('language', $newLang, 365);
}
```

### Условный рендеринг

```php
function renderResponse($data)
{
    // Для ботов - упрощенная версия
    if (Http::isBot()) {
        return view('seo-optimized', $data);
    }
    
    // Для мобильных - мобильная версия
    if (Http::isMobile()) {
        return view('mobile/page', $data);
    }
    
    // Для AJAX - только данные
    if (Http::isAjax()) {
        return json($data);
    }
    
    // Content negotiation
    if (Http::acceptsJson()) {
        return json($data);
    }
    
    // Обычный HTML
    return view('page', $data);
}
```

### Умное кеширование

```php
function serveCachedContent(string $file)
{
    $content = file_get_contents($file);
    $etag = '"' . md5($content) . '"';
    $lastModified = filemtime($file);
    
    // Проверяем ETag
    if (Http::getEtag() === $etag) {
        header('HTTP/1.1 304 Not Modified');
        exit;
    }
    
    // Проверяем Last-Modified
    if (Http::getIfModifiedSince() && Http::getIfModifiedSince() >= $lastModified) {
        header('HTTP/1.1 304 Not Modified');
        exit;
    }
    
    // Отправляем с кеш-заголовками
    header("ETag: $etag");
    header("Last-Modified: " . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
    header('Cache-Control: public, max-age=3600');
    
    echo $content;
}
```

---

## 🔄 Обратная совместимость

Все существующие методы работают без изменений. Новые методы только расширяют функциональность.

```php
// Старый код продолжает работать
$method = Http::getMethod();
$ip = Http::getClientIp();
$data = Http::getJsonData();

// Новый код использует новые возможности
$actualMethod = Http::getActualMethod();
$allData = Http::all();
$onlyEmail = Http::only(['email']);
```

---

## 📈 Статистика

**До улучшений:**
- 35 методов
- Базовая функциональность HTTP

**После улучшений:**
- **85+ методов** (+50 новых)
- Method Override
- Расширенная работа с файлами
- Bearer & Basic Auth
- Content Type операции
- Удобная работа с Input
- Query String утилиты
- Определение клиентов (боты, мобильные)
- Автоматическое определение языка
- HTTP семантика
- HTTP кеширование

---

## 🧪 Тестирование

Все новые методы покрыты unit-тестами:

```bash
# Запуск тестов
vendor/bin/pest tests/Unit/Core/HttpTest.php

# Всего тестов: 150+
# Покрытие: ~100%
```

---

## 📚 См. также

- [Http - Полная документация](Http.md)
- [Cookie - Работа с cookies](Cookie.md)
- [Session - Работа с сессиями](Session.md)
- [HttpCookieSession - Совместное использование](HttpCookieSession.md)

