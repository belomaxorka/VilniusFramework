# HTTP, Cookie и Session - Работа с запросами и состоянием

Три взаимосвязанных класса для работы с HTTP-запросами, cookies и сессиями.

## Обзор классов

### 🌐 [Http](Http.md) - Работа с HTTP запросами

Утилитный класс для работы с HTTP-запросами, инкапсулирующий доступ к `$_SERVER`, `$_GET`, `$_POST` и другим суперглобальным массивам.

**Основные возможности:**
- Получение информации о запросе (метод, URI, заголовки)
- Определение типа запроса (AJAX, JSON, HTTPS)
- Получение данных (GET, POST, JSON, файлы)
- Работа с IP-адресами и User Agent
- Content negotiation

**Пример:**
```php
use Core\Http;

// Проверка типа запроса
if (Http::isPost() && Http::isJson()) {
    $data = Http::getJsonData();
}

// Получение информации о клиенте
$ip = Http::getClientIp();
$url = Http::getFullUrl();
```

[Полная документация по Http →](Http.md)

---

### 🍪 [Cookie](Cookie.md) - Работа с HTTP Cookies

Класс для удобной и безопасной работы с cookies.

**Основные возможности:**
- Установка cookies с безопасными настройками по умолчанию
- Получение и удаление cookies
- Удобные методы: `setForDays()`, `setForHours()`, `forever()`
- Работа с JSON данными
- Автоматический secure для HTTPS

**Пример:**
```php
use Core\Cookie;

// Установить cookie на 30 дней
Cookie::setForDays('user_language', 'ru', 30);

// Получить cookie
$lang = Cookie::get('user_language', 'en');

// Сохранить массив как JSON
Cookie::setJson('preferences', ['theme' => 'dark', 'notifications' => true]);

// Получить JSON
$prefs = Cookie::getJson('preferences');
```

[Полная документация по Cookie →](Cookie.md)

---

### 💾 [Session](Session.md) - Работа с PHP сессиями

Класс для работы с PHP сессиями с дополнительными возможностями.

**Основные возможности:**
- Управление жизненным циклом сессии
- Flash сообщения (одноразовые сообщения)
- Встроенная CSRF защита
- Дополнительные методы: `push()`, `pull()`, `remember()`, `increment()`
- Безопасные настройки по умолчанию

**Пример:**
```php
use Core\Session;

// Установить значение
Session::set('user_id', 123);

// Получить значение
$userId = Session::get('user_id');

// Flash сообщение
Session::flash('success', 'User created!');

// CSRF токен
$token = Session::generateCsrfToken();
if (Session::verifyCsrfToken($submittedToken)) {
    // Обработка формы
}
```

[Полная документация по Session →](Session.md)

---

## Взаимодействие классов

Эти три класса отлично работают вместе:

### Пример 1: Авторизация с "Запомнить меня"

```php
use Core\Http;
use Core\Cookie;
use Core\Session;

// При входе
function login(User $user, bool $remember): void
{
    // Сохраняем в сессию
    Session::regenerate();
    Session::set('user_id', $user->id);
    Session::flash('success', 'Welcome back!');
    
    // Если нужно запомнить
    if ($remember) {
        $token = generateRememberToken();
        Cookie::setForDays('remember_token', $token, 30);
    }
}

// При каждом запросе
function checkAuth(): ?User
{
    // Проверяем сессию
    if (Session::has('user_id')) {
        return User::find(Session::get('user_id'));
    }
    
    // Проверяем remember cookie
    if (Cookie::has('remember_token')) {
        $token = Cookie::get('remember_token');
        $user = authenticateByToken($token);
        
        if ($user) {
            Session::set('user_id', $user->id);
            return $user;
        }
    }
    
    return null;
}
```

### Пример 2: CSRF защищенная форма

```php
use Core\Http;
use Core\Session;

// В шаблоне формы
?>
<form method="POST" action="/users/create">
    <input type="hidden" name="csrf_token" value="<?= Session::generateCsrfToken() ?>">
    
    <input type="text" name="name" required>
    <button type="submit">Create</button>
</form>

<?php
// В контроллере
function handleCreate(): void
{
    // Проверка метода
    if (!Http::isPost()) {
        http_response_code(405);
        return;
    }
    
    // Проверка CSRF
    $token = Http::getPostData()['csrf_token'] ?? '';
    if (!Session::verifyCsrfToken($token)) {
        Session::flash('error', 'Invalid security token');
        redirect('/users/create');
        return;
    }
    
    // Обработка...
    $user = createUser(Http::getPostData());
    
    Session::flash('success', 'User created successfully!');
    redirect('/users');
}
```

### Пример 3: API с Rate Limiting

```php
use Core\Http;
use Core\Session;
use Core\Cookie;

function apiEndpoint(): void
{
    // Проверка типа запроса
    if (!Http::isPost() || !Http::isJson()) {
        jsonResponse(['error' => 'Invalid request'], 400);
        return;
    }
    
    // Rate limiting по сессии
    if (!Session::remember('api_calls_limit', function() {
        return ['count' => 0, 'reset_at' => time() + 3600];
    })) {
        $limits = Session::get('api_calls_limit');
        
        if ($limits['count'] >= 100) {
            if (time() < $limits['reset_at']) {
                jsonResponse(['error' => 'Rate limit exceeded'], 429);
                return;
            }
            // Сброс
            Session::set('api_calls_limit', ['count' => 0, 'reset_at' => time() + 3600]);
        }
    }
    
    // Увеличиваем счётчик
    $limits = Session::get('api_calls_limit');
    $limits['count']++;
    Session::set('api_calls_limit', $limits);
    
    // Обработка API запроса
    $data = Http::getJsonData();
    // ...
}
```

### Пример 4: Многоязычность с сохранением

```php
use Core\Http;
use Core\Cookie;
use Core\Session;

class Language
{
    public static function get(): string
    {
        // Приоритет: сессия > cookie > заголовок Accept-Language > default
        
        // 1. Проверяем сессию (текущий запрос)
        if (Session::has('language')) {
            return Session::get('language');
        }
        
        // 2. Проверяем cookie (долгосрочное хранение)
        if (Cookie::has('language')) {
            $lang = Cookie::get('language');
            Session::set('language', $lang);
            return $lang;
        }
        
        // 3. Проверяем Accept-Language header
        $acceptLang = Http::getHeader('Accept-Language');
        if ($acceptLang) {
            $lang = self::parseAcceptLanguage($acceptLang);
            if ($lang) {
                return $lang;
            }
        }
        
        // 4. Дефолтный язык
        return 'en';
    }
    
    public static function set(string $lang): void
    {
        Session::set('language', $lang);
        Cookie::setForDays('language', $lang, 365);
    }
    
    private static function parseAcceptLanguage(string $header): ?string
    {
        $supported = ['en', 'ru', 'es', 'fr', 'de'];
        $langs = explode(',', $header);
        
        foreach ($langs as $lang) {
            $code = strtolower(substr(trim($lang), 0, 2));
            if (in_array($code, $supported)) {
                return $code;
            }
        }
        
        return null;
    }
}

// Использование
$currentLang = Language::get();

// Пользователь меняет язык
if (Http::isPost() && isset($_POST['language'])) {
    Language::set($_POST['language']);
    Session::flash('success', 'Language changed');
    redirect('/');
}
```

### Пример 5: Корзина покупок (гости + авторизованные)

```php
use Core\Session;
use Core\Cookie;

class Cart
{
    public static function add(int $productId, int $quantity = 1): void
    {
        $cart = self::getCart();
        
        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] += $quantity;
        } else {
            $cart[$productId] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'added_at' => time()
            ];
        }
        
        self::saveCart($cart);
        
        Session::flash('success', 'Product added to cart');
    }
    
    private static function getCart(): array
    {
        // Авторизованные пользователи - в сессии
        if (Session::has('user_id')) {
            return Session::get('cart', []);
        }
        
        // Гости - в cookie
        return Cookie::getJson('guest_cart', []);
    }
    
    private static function saveCart(array $cart): void
    {
        if (Session::has('user_id')) {
            Session::set('cart', $cart);
        } else {
            Cookie::setJson('guest_cart', $cart, 7 * 24 * 60 * 60); // 7 дней
        }
    }
    
    public static function merge(): void
    {
        // При авторизации мержим корзину из cookie в сессию
        if (Cookie::has('guest_cart')) {
            $guestCart = Cookie::getJson('guest_cart', []);
            $userCart = Session::get('cart', []);
            
            foreach ($guestCart as $productId => $item) {
                if (isset($userCart[$productId])) {
                    $userCart[$productId]['quantity'] += $item['quantity'];
                } else {
                    $userCart[$productId] = $item;
                }
            }
            
            Session::set('cart', $userCart);
            Cookie::delete('guest_cart');
        }
    }
}
```

## Безопасность

Все три класса реализованы с учетом безопасности:

### Http
- ✅ Безопасное получение данных с проверкой существования
- ✅ Валидация IP-адресов
- ✅ Защита от инъекций через правильную работу с данными

### Cookie
- ✅ `httponly = true` по умолчанию (защита от XSS)
- ✅ `samesite = 'Lax'` по умолчанию (защита от CSRF)
- ✅ Автоматический `secure` для HTTPS
- ✅ Все значения экранируются

### Session
- ✅ `httponly` и `secure` cookies по умолчанию
- ✅ Автоматическая регенерация ID после авторизации
- ✅ Встроенная CSRF защита с `hash_equals()`
- ✅ `use_strict_mode` включен

## Производительность

### Рекомендации:

1. **Session**: Закрывайте сессию для длительных операций
   ```php
   Session::save(); // Освобождает блокировку
   performLongTask();
   ```

2. **Cookie**: Минимизируйте размер (макс. 4KB)
   ```php
   // ❌ Плохо
   Cookie::setJson('user', $fullUserObject);
   
   // ✅ Хорошо
   Cookie::setJson('user', ['id' => $user->id]);
   ```

3. **Http**: Кешируйте результаты в рамках запроса
   ```php
   // В Session есть remember() для этого
   $user = Session::remember('user', fn() => loadUser());
   ```

## Тестирование

Все классы покрыты unit-тестами:

```bash
# Запуск всех тестов
vendor/bin/pest tests/Unit/Core/HttpTest.php
vendor/bin/pest tests/Unit/Core/CookieTest.php
vendor/bin/pest tests/Unit/Core/SessionTest.php

# Или все вместе
vendor/bin/pest tests/Unit/Core/
```

## Миграция с прямого использования суперглобалов

### Было (старый код):
```php
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$userId = $_SESSION['user_id'] ?? null;
$lang = $_COOKIE['language'] ?? 'en';
```

### Стало (новый код):
```php
$method = Http::getMethod();
$userId = Session::get('user_id');
$lang = Cookie::get('language', 'en');
```

### Преимущества:
- ✅ Чище и понятнее
- ✅ Безопаснее
- ✅ Легче тестировать
- ✅ Больше возможностей (Flash, CSRF, JSON и т.д.)

## См. также

- [Http - Подробная документация](Http.md)
- [Cookie - Подробная документация](Cookie.md)
- [Session - Подробная документация](Session.md)
- [Router](Router.md) - Маршрутизация
- [Debug Toolbar](DebugToolbar.md) - Отладка

