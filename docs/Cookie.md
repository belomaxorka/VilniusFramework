# Cookie Класс

Класс для удобной работы с HTTP Cookie.

## Содержание

- [Введение](#введение)
- [Основные операции](#основные-операции)
- [Безопасность](#безопасность)
- [Удобные методы](#удобные-методы)
- [Работа с JSON](#работа-с-json)
- [Примеры использования](#примеры-использования)
- [Лучшие практики](#лучшие-практики)

## Введение

Класс `Core\Cookie` предоставляет удобный и безопасный API для работы с HTTP cookies в PHP. Он инкапсулирует встроенные функции PHP `setcookie()` и `$_COOKIE`, предоставляя более чистый интерфейс с разумными значениями по умолчанию.

### Преимущества

- 🔒 **Безопасность**: HttpOnly и SameSite по умолчанию
- 🎯 **Простота**: Чистый и понятный API
- ⏰ **Удобство**: Методы для установки на дни/часы
- 📦 **JSON поддержка**: Работа со сложными данными

## Основные операции

### Установка cookie

```php
use Core\Cookie;

// Простая установка (до закрытия браузера)
Cookie::set('user_name', 'John');

// С временем жизни (в секундах)
Cookie::set('user_name', 'John', 3600); // 1 час

// С полными параметрами
Cookie::set(
    name: 'user_name',
    value: 'John',
    expires: 3600,           // Время жизни в секундах
    path: '/',               // Путь
    domain: '',              // Домен
    secure: true,            // Только HTTPS
    httponly: true,          // Недоступна для JS
    samesite: 'Strict'       // SameSite политика
);
```

### Получение cookie

```php
// Получить значение
$name = Cookie::get('user_name'); // "John"

// С значением по умолчанию
$lang = Cookie::get('language', 'en'); // "en" если не установлена
```

### Проверка существования

```php
if (Cookie::has('user_name')) {
    // Cookie существует
}
```

### Удаление cookie

```php
// Удалить cookie
Cookie::delete('user_name');

// Удалить с указанием пути и домена
Cookie::delete('user_name', '/admin', 'example.com');
```

### Получение всех cookies

```php
// Получить все cookies как массив
$allCookies = Cookie::all();
/*
[
    'user_name' => 'John',
    'language' => 'ru',
    'theme' => 'dark'
]
*/
```

### Очистка всех cookies

```php
// Удалить все cookies
Cookie::clear();

// С указанием пути и домена
Cookie::clear('/admin', 'example.com');
```

## Безопасность

### Автоматически безопасные cookies

По умолчанию cookies создаются с безопасными настройками:
- `httponly = true` - защита от XSS атак
- `samesite = 'Lax'` - защита от CSRF атак

```php
// Эта cookie защищена по умолчанию
Cookie::set('session_id', 'abc123');
```

### Автоматический secure для HTTPS

```php
// Автоматически установит secure=true если HTTPS
Cookie::setSecure('token', 'secret_value', 3600);
```

### SameSite политики

```php
// Lax (по умолчанию) - баланс безопасности и удобства
Cookie::set('data', 'value', 3600, '/', '', false, true, 'Lax');

// Strict - максимальная защита от CSRF
Cookie::set('data', 'value', 3600, '/', '', false, true, 'Strict');

// None - требуется для кросс-доменных запросов (нужен secure=true)
Cookie::set('data', 'value', 3600, '/', '', true, true, 'None');
```

## Удобные методы

### Установка на дни

```php
// На 30 дней
Cookie::setForDays('remember_token', 'xyz789', 30);

// На 7 дней
Cookie::setForDays('preference', 'value', 7);
```

### Установка на часы

```php
// На 2 часа
Cookie::setForHours('temp_data', 'value', 2);

// На 1 час (по умолчанию)
Cookie::setForHours('cart_id', '12345');
```

### Постоянная cookie (5 лет)

```php
// Установить "навсегда" (на 5 лет)
Cookie::forever('user_preferences', 'dark_theme');
```

## Работа с JSON

### Сохранение сложных данных

```php
// Сохранить массив/объект как JSON
$userData = [
    'id' => 123,
    'name' => 'John',
    'roles' => ['admin', 'user']
];

Cookie::setJson('user_data', $userData, 3600);
```

### Получение JSON данных

```php
// Получить и автоматически декодировать
$userData = Cookie::getJson('user_data');
/*
[
    'id' => 123,
    'name' => 'John',
    'roles' => ['admin', 'user']
]
*/

// С значением по умолчанию
$settings = Cookie::getJson('settings', ['theme' => 'light']);
```

## Примеры использования

### Пример 1: Запоминание языка пользователя

```php
use Core\Cookie;

// Установить язык на 1 год
function setUserLanguage(string $lang): void
{
    Cookie::setForDays('user_language', $lang, 365);
}

// Получить язык или en по умолчанию
function getUserLanguage(): string
{
    return Cookie::get('user_language', 'en');
}

// Использование
setUserLanguage('ru');
$lang = getUserLanguage(); // "ru"
```

### Пример 2: "Запомнить меня" при входе

```php
use Core\Cookie;

function rememberUser(int $userId, string $token): void
{
    // Сохраняем на 30 дней
    $data = [
        'user_id' => $userId,
        'token' => $token
    ];
    
    Cookie::setJson('remember_me', $data, 30 * 24 * 60 * 60);
}

function getRememberedUser(): ?array
{
    return Cookie::getJson('remember_me');
}

function forgetUser(): void
{
    Cookie::delete('remember_me');
}
```

### Пример 3: Корзина покупок для гостей

```php
use Core\Cookie;

function addToGuestCart(int $productId, int $quantity): void
{
    $cart = Cookie::getJson('guest_cart', []);
    
    $cart[$productId] = [
        'id' => $productId,
        'quantity' => $quantity,
        'added_at' => time()
    ];
    
    // Храним 7 дней
    Cookie::setJson('guest_cart', $cart, 7 * 24 * 60 * 60);
}

function getGuestCart(): array
{
    return Cookie::getJson('guest_cart', []);
}

function clearGuestCart(): void
{
    Cookie::delete('guest_cart');
}
```

### Пример 4: Отслеживание посещений

```php
use Core\Cookie;

function trackVisit(): void
{
    $visits = (int)Cookie::get('visit_count', 0);
    $visits++;
    
    Cookie::setForDays('visit_count', (string)$visits, 365);
    
    if ($visits === 1) {
        // Первое посещение
        Cookie::set('first_visit', date('Y-m-d H:i:s'));
    }
    
    // Последнее посещение
    Cookie::set('last_visit', date('Y-m-d H:i:s'));
}

function getVisitInfo(): array
{
    return [
        'count' => (int)Cookie::get('visit_count', 0),
        'first_visit' => Cookie::get('first_visit'),
        'last_visit' => Cookie::get('last_visit'),
    ];
}
```

### Пример 5: Пользовательские настройки

```php
use Core\Cookie;

class UserPreferences
{
    public static function save(array $preferences): void
    {
        Cookie::setJson('preferences', $preferences, 365 * 24 * 60 * 60);
    }
    
    public static function get(): array
    {
        $defaults = [
            'theme' => 'light',
            'language' => 'en',
            'notifications' => true,
            'items_per_page' => 20
        ];
        
        return Cookie::getJson('preferences', $defaults);
    }
    
    public static function update(string $key, mixed $value): void
    {
        $prefs = self::get();
        $prefs[$key] = $value;
        self::save($prefs);
    }
    
    public static function reset(): void
    {
        Cookie::delete('preferences');
    }
}

// Использование
UserPreferences::update('theme', 'dark');
UserPreferences::update('language', 'ru');

$prefs = UserPreferences::get();
// ['theme' => 'dark', 'language' => 'ru', ...]
```

### Пример 6: GDPR Cookie Consent

```php
use Core\Cookie;

class CookieConsent
{
    public static function grant(array $categories): void
    {
        $consent = [
            'granted_at' => time(),
            'categories' => $categories
        ];
        
        Cookie::setJson('cookie_consent', $consent, 365 * 24 * 60 * 60);
    }
    
    public static function hasConsent(string $category = 'necessary'): bool
    {
        $consent = Cookie::getJson('cookie_consent');
        
        if (!$consent) {
            return $category === 'necessary';
        }
        
        return in_array($category, $consent['categories'] ?? []);
    }
    
    public static function revoke(): void
    {
        Cookie::delete('cookie_consent');
        // Удаляем все не обязательные cookies
        self::clearNonEssentialCookies();
    }
    
    private static function clearNonEssentialCookies(): void
    {
        $essential = ['cookie_consent', 'session_id'];
        
        foreach (Cookie::all() as $name => $value) {
            if (!in_array($name, $essential)) {
                Cookie::delete($name);
            }
        }
    }
}

// Использование
if (!CookieConsent::hasConsent('analytics')) {
    // Не загружаем аналитику
} else {
    // Загружаем Google Analytics
}
```

### Пример 7: A/B тестирование

```php
use Core\Cookie;

class ABTest
{
    public static function assignVariant(string $testName): string
    {
        $cookieName = "ab_test_$testName";
        
        if (Cookie::has($cookieName)) {
            return Cookie::get($cookieName);
        }
        
        // Случайное распределение
        $variant = random_int(0, 1) === 0 ? 'A' : 'B';
        
        // Сохраняем на время теста (30 дней)
        Cookie::setForDays($cookieName, $variant, 30);
        
        return $variant;
    }
    
    public static function getVariant(string $testName): ?string
    {
        return Cookie::get("ab_test_$testName");
    }
}

// Использование
$variant = ABTest::assignVariant('homepage_design');

if ($variant === 'A') {
    // Показываем дизайн A
} else {
    // Показываем дизайн B
}
```

## Лучшие практики

### 1. Всегда используйте HttpOnly для чувствительных данных

```php
// ✅ Хорошо - защищено от XSS
Cookie::set('session_token', $token, 3600, '/', '', true, true);

// ❌ Плохо - доступно из JavaScript
Cookie::set('session_token', $token, 3600, '/', '', true, false);
```

### 2. Используйте Secure для HTTPS сайтов

```php
// ✅ Хорошо - автоматически определяет HTTPS
Cookie::setSecure('auth_token', $token, 3600);

// ✅ Хорошо - явно указываем secure
Cookie::set('auth_token', $token, 3600, '/', '', true, true);
```

### 3. Минимизируйте размер cookies

```php
// ❌ Плохо - слишком много данных
Cookie::setJson('user', $fullUserObject);

// ✅ Хорошо - только необходимое
Cookie::setJson('user', ['id' => $user->id, 'role' => $user->role]);
```

### 4. Устанавливайте разумное время жизни

```php
// ✅ Хорошо - ясное намерение
Cookie::setForDays('preference', 'value', 30);  // Настройки
Cookie::setForHours('temp', 'value', 1);        // Временные данные
Cookie::forever('tracking', 'id');              // Долгосрочное отслеживание
```

### 5. Всегда проверяйте существование перед использованием

```php
// ✅ Хорошо - с проверкой
if (Cookie::has('user_id')) {
    $userId = Cookie::get('user_id');
}

// ✅ Хорошо - со значением по умолчанию
$theme = Cookie::get('theme', 'light');
```

### 6. Используйте правильную SameSite политику

```php
// Для обычных cookies
Cookie::set('data', 'value', 3600, '/', '', false, true, 'Lax');

// Для строгой защиты от CSRF
Cookie::set('csrf_token', $token, 0, '/', '', true, true, 'Strict');

// Для кросс-доменных запросов (требуется secure)
Cookie::set('tracking', 'id', 3600, '/', '', true, true, 'None');
```

## Интеграция с другими компонентами

### С Session классом

```php
use Core\Cookie;
use Core\Session;

// Реализация "Remember Me"
if (Cookie::has('remember_token')) {
    $token = Cookie::get('remember_token');
    $user = authenticateByToken($token);
    
    if ($user) {
        Session::set('user_id', $user->id);
    }
}
```

### С Http классом

```php
use Core\Cookie;
use Core\Http;

// Автоматическая безопасность на основе протокола
if (Http::isSecure()) {
    Cookie::set('token', $value, 3600, '/', '', true, true, 'Strict');
} else {
    Cookie::set('token', $value, 3600, '/', '', false, true, 'Lax');
}

// Или просто используйте setSecure()
Cookie::setSecure('token', $value, 3600);
```

## Ограничения

1. **Размер**: Максимум ~4KB на cookie
2. **Количество**: Ограничено браузером (обычно ~50 на домен)
3. **Безопасность**: Могут быть украдены при MitM атаках без HTTPS
4. **Privacy**: Пользователи могут их удалять

## См. также

- [Session](Session.md) - Работа с сессиями
- [Http](Http.md) - HTTP запросы
- [Security Best Practices](Security.md) - Лучшие практики безопасности

