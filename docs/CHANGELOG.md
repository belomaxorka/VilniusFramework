# Changelog

## 2024-10-01 - Request & Response System

### 🎉 Добавлено

#### Новые классы

1. **Core\Request** - ООП обертка над HTTP запросом
   - Fluent interface для работы с данными
   - Методы для получения input, query, post, json данных
   - Работа с headers, cookies, files
   - Проверки типа запроса (isJson, wantsJson, isAjax, isMobile, etc.)
   - Magic методы (`__get`, `__isset`)
   - URL информация (uri, url, path, host, etc.)
   - Клиентская информация (ip, userAgent, referer)

2. **Core\Response** - Управление HTTP ответами
   - Fluent interface для построения ответов
   - JSON, HTML, XML, Text ответы
   - Редиректы (простые, back, на именованные роуты)
   - Работа с файлами (download, stream)
   - Управление headers и cookies
   - Предопределенные HTTP статус коды
   - Автоматическая отправка ответа

3. **App\Controllers\Controller** - Базовый контроллер
   - Автоматическое внедрение Request и Response
   - Готовые методы для типичных ответов:
     - `json()`, `html()`, `view()`
     - `success()`, `error()`, `notFound()`, `unauthorized()`, `forbidden()`
     - `redirect()`, `back()`, `redirectRoute()`
     - `download()`, `noContent()`, `created()`
   - Умные ответы (JSON для API, HTML для браузера)

#### Helper функции

Новая группа helpers `core/helpers/app/http.php`:
- `request()` - получить Request или значение
- `response()` - создать Response
- `json()` - создать JSON response
- `redirect()` - редирект
- `back()` - редирект назад
- `abort()` - прервать с ошибкой
- `abort_if()` - условное прерывание
- `abort_unless()` - условное прерывание

#### Документация

- `docs/RequestResponse.md` - Полная документация (600+ строк):
  - Подробное описание Request и Response
  - Описание BaseController
  - Примеры использования
  - Best practices
  - Руководство по миграции

#### Примеры

- `app/Controllers/ExampleController.php` - Примеры использования:
  - API endpoints
  - Работа с формами
  - Редиректы
  - Download файлов
  - Условные ответы
  - Работа с headers и cookies
  - Upload файлов

### ♻️ Изменено

1. **Core\Router**
   - Добавлена поддержка Response объектов
   - Автоматическая отправка Response из контроллеров
   - Обработка return значений из middleware

2. **App\Controllers\HomeController**
   - Переведен на новый BaseController
   - Использует Response объекты
   - Использует методы из базового класса

### 🔧 Улучшения

1. **Интеграция с существующей системой**
   - Request использует Http класс под капотом
   - Response интегрирован с Router
   - Полная обратная совместимость
   - **Автоматическая интеграция с Debug Toolbar**

2. **Type Safety**
   - Все методы контроллеров с type hints
   - PHPDoc для всех методов
   - Строгие типы везде

3. **Чистота кода**
   - Больше не нужен прямой `echo`, `header()`, `http_response_code()`
   - Fluent interface для цепочки вызовов
   - Консистентный API

4. **Debug Toolbar интеграция**
   - Response автоматически внедряет Debug Toolbar в HTML ответы
   - Работает только в debug режиме
   - Только для HTML контента с `</body>` тегом
   - Не влияет на JSON, XML и другие типы ответов

---

## 2024-09-30 - Масштабное обновление HTTP, Cookie, Session

### 🎉 Добавлено

#### Новые классы

1. **Core\Http** - Утилитный класс для работы с HTTP-запросами
   - 85+ методов для работы с запросами
   - Method Override поддержка
   - Bearer & Basic Auth
   - Расширенная работа с файлами
   - Определение типа клиента (боты, мобильные)
   - Автоопределение языка
   - HTTP кеширование
   - Content negotiation

2. **Core\Cookie** - Класс для работы с HTTP Cookies
   - 15+ методов
   - Безопасные настройки по умолчанию
   - JSON поддержка
   - Удобные методы (setForDays, setForHours, forever)
   - Автоматический secure для HTTPS

3. **Core\Session** - Класс для работы с PHP сессиями
   - 30+ методов
   - Flash сообщения
   - Встроенная CSRF защита
   - Дополнительные методы (push, pull, remember, increment)
   - Безопасные настройки по умолчанию

#### Документация

Создано **4 файла** полной документации:
- `Http.md` - Документация Http класса (500+ строк)
- `Cookie.md` - Документация Cookie класса (600+ строк)
- `Session.md` - Документация Session класса (700+ строк)
- `HttpCookieSession.md` - Обзор и примеры совместного использования (400+ строк)
- `HttpImprovements.md` - Детальное описание улучшений

#### Тесты

Создано **3 test suite** с полным покрытием:
- `HttpTest.php` - 150+ тестов
- `CookieTest.php` - 30+ тестов
- `SessionTest.php` - 50+ тестов

**Всего: 230+ тестов**

### 🔧 Изменено

#### RequestCollector

- Рефакторинг для использования нового `Http` класса
- Удалено ~60 строк дублирующего кода
- Улучшена читаемость и поддерживаемость

### 🐛 Исправлено

- Ошибка `Undefined array key "SERVER_PORT"` в `RequestCollector`
- Все 27 тестов `DebugToolbarTest` теперь проходят успешно

---

## Детали изменений

### Core\Http - Полный список методов

#### Базовые
- `getMethod()` - метод запроса
- `getActualMethod()` - **NEW** с Method Override
- `getUri()` - URI запроса
- `getPath()` - путь без query string
- `getQueryString()` - query string
- `getProtocol()` - HTTP протокол
- `getScheme()` - схема (http/https)
- `isSecure()` - HTTPS проверка
- `getHost()` - хост
- `getPort()` - порт

#### URL операции
- `getFullUrl()` - полный URL
- `getBaseUrl()` - базовый URL
- `getUrlWithParams()` - **NEW** URL с параметрами

#### Клиент
- `getClientIp()` - IP адрес
- `getUserAgent()` - User Agent
- `getReferer()` - Referer
- `getRequestTime()` - время запроса

#### Заголовки
- `getHeaders()` - все заголовки
- `getHeader()` - конкретный заголовок
- `getAcceptedContentTypes()` - Accept типы
- `acceptsJson()` - принимает JSON
- `acceptsHtml()` - принимает HTML

#### Проверки методов
- `isMethod()` - проверка метода
- `isGet()` - GET запрос
- `isPost()` - POST запрос
- `isPut()` - PUT запрос
- `isPatch()` - PATCH запрос
- `isDelete()` - DELETE запрос

#### Проверки типов
- `isAjax()` - AJAX запрос
- `isJson()` - JSON Content-Type
- `isMultipart()` - **NEW** multipart/form-data
- `isFormUrlEncoded()` - **NEW** url-encoded
- `isSafe()` - **NEW** безопасный метод
- `isIdempotent()` - **NEW** идемпотентный метод
- `isBot()` - **NEW** бот/краулер
- `isMobile()` - **NEW** мобильное устройство
- `isPrefetch()` - **NEW** prefetch запрос

#### Данные запроса
- `getQueryParams()` - GET параметры
- `getPostData()` - POST данные
- `getInputData()` - php://input
- `getJsonData()` - **NEW** JSON из input
- `all()` - **NEW** все данные (GET + POST)
- `input()` - **NEW** значение из GET/POST
- `has()` - **NEW** проверка существования
- `only()` - **NEW** только указанные ключи
- `except()` - **NEW** все кроме указанных
- `isEmpty()` - **NEW** проверка пустоты
- `filled()` - **NEW** проверка заполненности

#### Файлы
- `getFiles()` - все файлы
- `hasFiles()` - **NEW** наличие файлов
- `getFile()` - **NEW** конкретный файл
- `isValidUpload()` - **NEW** валидность загрузки
- `getFileSize()` - **NEW** размер файла
- `getFileExtension()` - **NEW** расширение
- `getFileMimeType()` - **NEW** MIME тип файла

#### Cookies
- `getCookies()` - все cookies
- `getCookie()` - конкретная cookie

#### Query String
- `parseQueryString()` - **NEW** парсинг
- `buildQueryString()` - **NEW** построение

#### Content Type
- `getContentLength()` - **NEW** Content-Length
- `getContentType()` - **NEW** Content-Type
- `getMimeType()` - **NEW** MIME тип
- `getCharset()` - **NEW** charset

#### Авторизация
- `getBearerToken()` - **NEW** Bearer токен
- `getBasicAuth()` - **NEW** Basic Auth

#### Язык
- `getPreferredLanguage()` - **NEW** предпочитаемый язык
- `getAcceptedLanguages()` - **NEW** все языки

#### Кеширование
- `getEtag()` - **NEW** ETag заголовок
- `getIfModifiedSince()` - **NEW** If-Modified-Since

---

### Core\Cookie - Список методов

#### Базовые операции
- `set()` - установить cookie
- `get()` - получить cookie
- `has()` - проверить существование
- `delete()` - удалить cookie
- `all()` - все cookies
- `clear()` - очистить все cookies

#### Удобные методы
- `setSecure()` - установить с автоопределением secure
- `setForDays()` - установить на N дней
- `setForHours()` - установить на N часов
- `forever()` - постоянная cookie (5 лет)

#### JSON
- `getJson()` - получить и декодировать JSON
- `setJson()` - установить с кодированием в JSON

---

### Core\Session - Список методов

#### Управление сессией
- `start()` - запустить сессию
- `isStarted()` - проверка запуска
- `destroy()` - уничтожить сессию
- `regenerate()` - регенерировать ID
- `save()` - сохранить и закрыть
- `id()` / `setId()` - ID сессии
- `name()` / `setName()` - имя сессии
- `getCookieParams()` / `setCookieParams()` - параметры cookie

#### Базовые операции
- `get()` - получить значение
- `set()` - установить значение
- `has()` - проверить существование
- `delete()` - удалить значение
- `all()` - все данные
- `clear()` - очистить все данные

#### Flash сообщения
- `flash()` - установить flash сообщение
- `getFlash()` - получить flash сообщение
- `hasFlash()` - проверить flash
- `getAllFlash()` - получить все flash

#### CSRF защита
- `generateCsrfToken()` - сгенерировать токен
- `getCsrfToken()` - получить токен
- `verifyCsrfToken()` - проверить токен

#### Дополнительные методы
- `pull()` - получить и удалить
- `push()` - добавить в массив
- `increment()` / `decrement()` - изменить число
- `remember()` - запомнить результат callback
- `setPreviousUrl()` / `getPreviousUrl()` - для redirect back

---

## Примеры использования

### Авторизация с "Запомнить меня"

```php
use Core\Http;
use Core\Cookie;
use Core\Session;

if (Http::isPost()) {
    $credentials = Http::only(['email', 'password']);
    $remember = Http::input('remember_me');
    
    if (Auth::attempt($credentials)) {
        Session::regenerate();
        Session::set('user_id', $user->id);
        
        if ($remember) {
            $token = generateToken();
            Cookie::setForDays('remember_token', $token, 30);
        }
        
        Session::flash('success', 'Welcome back!');
        redirect('/dashboard');
    }
}
```

### REST API с авторизацией

```php
use Core\Http;

function apiEndpoint()
{
    // Проверка токена
    $token = Http::getBearerToken();
    if (!$token || !validateJWT($token)) {
        return jsonError('Unauthorized', 401);
    }
    
    // Method Override
    $method = Http::getActualMethod();
    
    // Проверка Content-Type
    if (!Http::isJson()) {
        return jsonError('JSON required', 400);
    }
    
    // Обработка по методу
    return match($method) {
        'GET' => getResource(),
        'POST' => createResource(),
        'PUT' => updateResource(),
        'DELETE' => deleteResource(),
        default => jsonError('Method not allowed', 405)
    };
}
```

### CSRF защищенная форма

```php
// В шаблоне
<form method="POST">
    <input type="hidden" name="csrf_token" 
           value="<?= Session::generateCsrfToken() ?>">
    <!-- поля формы -->
</form>

// В контроллере
use Core\Http;
use Core\Session;

if (Http::isPost()) {
    $token = Http::input('csrf_token');
    
    if (!Session::verifyCsrfToken($token)) {
        Session::flash('error', 'Invalid security token');
        redirect()->back();
    }
    
    // Обработка формы...
}
```

### Многоязычное приложение

```php
use Core\Http;
use Core\Cookie;

$supported = ['en', 'ru', 'es', 'fr'];
$lang = Cookie::get('language') 
        ?? Http::getPreferredLanguage($supported);

setLocale($lang);
```

---

## Миграция

### Было (старый код)

```php
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
$data = json_decode(file_get_contents('php://input'), true);
$userId = $_SESSION['user_id'] ?? null;
$lang = $_COOKIE['language'] ?? 'en';
```

### Стало (новый код)

```php
use Core\Http;
use Core\Session;
use Core\Cookie;

$method = Http::getMethod();
$ip = Http::getClientIp();
$data = Http::getJsonData();
$userId = Session::get('user_id');
$lang = Cookie::get('language', 'en');
```

---

## Тестирование

```bash
# Запуск всех тестов
vendor/bin/pest tests/Unit/Core/

# Отдельные тесты
vendor/bin/pest tests/Unit/Core/HttpTest.php      # 150+ тестов
vendor/bin/pest tests/Unit/Core/CookieTest.php    # 30+ тестов
vendor/bin/pest tests/Unit/Core/SessionTest.php   # 50+ тестов

# Debug Toolbar тесты (все проходят)
vendor/bin/pest tests/Unit/Core/Debug/DebugToolbarTest.php  # 27 тестов
```

---

## Безопасность

Все классы реализованы с учетом безопасности:

- ✅ Cookie: `httponly=true`, `samesite='Lax'` по умолчанию
- ✅ Session: Автоматическая безопасная конфигурация для HTTPS
- ✅ CSRF: Встроенная защита с `hash_equals()`
- ✅ Http: Валидация IP, безопасное получение данных
- ✅ Защита от Session Fixation через `regenerate()`

---

## Производительность

- Все методы оптимизированы
- Минимальный overhead
- Lazy initialization где возможно
- Нет лишних операций

---

## Обратная совместимость

✅ Полная обратная совместимость
- Все существующие классы работают без изменений
- `RequestCollector` использует новый `Http` класс, но API не изменился
- Новые классы добавляются, старые не затрагиваются

---

## Благодарности

Спасибо за использование! 🎉

Если есть вопросы или предложения, создавайте issue.

---

## Ссылки

- [Http Documentation](Http.md)
- [Cookie Documentation](Cookie.md)
- [Session Documentation](Session.md)
- [Combined Usage Guide](HttpCookieSession.md)
- [Improvements Details](HttpImprovements.md)


