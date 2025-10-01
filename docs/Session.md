# Session Класс

Класс для удобной работы с PHP сессиями.

## Содержание

- [Введение](#введение)
- [Основные операции](#основные-операции)
- [Управление сессией](#управление-сессией)
- [Flash сообщения](#flash-сообщения)
- [CSRF защита](#csrf-защита)
- [Дополнительные возможности](#дополнительные-возможности)
- [Примеры использования](#примеры-использования)
- [Лучшие практики](#лучшие-практики)

## Введение

Класс `Core\Session` предоставляет удобный и безопасный API для работы с PHP сессиями. Он автоматически управляет жизненным циклом сессии и предоставляет множество полезных методов.

### Преимущества

- 🔒 **Безопасность**: HttpOnly и Secure cookies по умолчанию для HTTPS
- 🎯 **Простота**: Чистый API без необходимости работы с `$_SESSION` напрямую
- ⚡ **Flash сообщения**: Встроенная поддержка одноразовых сообщений
- 🛡️ **CSRF защита**: Встроенная генерация и проверка токенов

## Основные операции

### Запуск сессии

```php
use Core\Session;

// Автоматически запускается с безопасными настройками
Session::start();

// С кастомными опциями
Session::start([
    'cookie_lifetime' => 3600,
    'cookie_httponly' => true,
    'cookie_secure' => true,
]);
```

Сессия запускается автоматически при первом обращении к данным, но вы можете запустить её явно.

### Установка значений

```php
// Установить значение
Session::set('user_id', 123);
Session::set('user_name', 'John Doe');

// Установить массив
Session::set('user_data', [
    'id' => 123,
    'name' => 'John',
    'role' => 'admin'
]);
```

### Получение значений

```php
// Получить значение
$userId = Session::get('user_id'); // 123

// С значением по умолчанию
$theme = Session::get('theme', 'light'); // "light" если не установлено
```

### Проверка существования

```php
if (Session::has('user_id')) {
    // Пользователь авторизован
}
```

### Удаление значений

```php
// Удалить одно значение
Session::delete('temp_data');

// Удалить несколько
Session::delete('key1');
Session::delete('key2');
```

### Получение всех данных

```php
// Получить все данные сессии
$allData = Session::all();
/*
[
    'user_id' => 123,
    'user_name' => 'John',
    'cart' => [...]
]
*/
```

### Очистка данных

```php
// Очистить все данные, но сохранить сессию
Session::clear();
```

## Управление сессией

### Получение ID сессии

```php
// Текущий ID
$sessionId = Session::id(); // "abc123xyz..."

// Установить ID (до старта сессии)
Session::setId('custom_session_id');
```

### Имя сессии

```php
// Получить имя сессии
$name = Session::name(); // "PHPSESSID"

// Установить имя (до старта сессии)
Session::setName('MY_SESSION');
```

### Регенерация ID

```php
// Регенерировать ID (важно после авторизации)
Session::regenerate();

// Без удаления старой сессии
Session::regenerate(deleteOldSession: false);
```

### Уничтожение сессии

```php
// Полностью уничтожить сессию
Session::destroy();
```

### Сохранение и закрытие

```php
// Сохранить данные и закрыть запись
// Полезно для длительных операций
Session::save();
```

## Flash сообщения

Flash сообщения доступны только в следующем запросе - идеально для уведомлений после redirect.

### Установка flash сообщений

```php
// Установить flash сообщение
Session::flash('success', 'User created successfully!');
Session::flash('error', 'Something went wrong');
Session::flash('info', 'Please check your email');
```

### Получение flash сообщений

```php
// Получить и удалить flash сообщение
$message = Session::getFlash('success'); // "User created successfully!"

// При повторном вызове вернёт null
$message = Session::getFlash('success'); // null

// С значением по умолчанию
$error = Session::getFlash('error', 'No errors');
```

### Проверка flash сообщений

```php
if (Session::hasFlash('success')) {
    $message = Session::getFlash('success');
}
```

### Получение всех flash сообщений

```php
// Получить все flash и очистить их
$flash = Session::getAllFlash();
/*
[
    'success' => 'User created!',
    'info' => 'Check your email'
]
*/
```

## CSRF защита

### Генерация CSRF токена

```php
// Генерировать или получить существующий токен
$token = Session::generateCsrfToken();

// В форме
echo '<input type="hidden" name="csrf_token" value="' . $token . '">';
```

### Проверка CSRF токена

```php
use Core\Http;

$submittedToken = Http::getPostData()['csrf_token'] ?? '';

if (Session::verifyCsrfToken($submittedToken)) {
    // Токен валиден, обрабатываем форму
} else {
    // Неверный токен, возможна CSRF атака
    die('Invalid CSRF token');
}
```

### Получение CSRF токена

```php
// Получить текущий токен (без генерации нового)
$token = Session::getCsrfToken(); // string|null
```

## Дополнительные возможности

### Pull (получить и удалить)

```php
// Получить значение и сразу удалить его
$message = Session::pull('one_time_message', 'default');

// После этого значение удалено из сессии
```

### Push (добавить в массив)

```php
// Добавить значение в массив
Session::push('notifications', 'New message');
Session::push('notifications', 'New comment');

$notifications = Session::get('notifications');
// ['New message', 'New comment']
```

### Increment/Decrement

```php
// Увеличить счётчик
Session::increment('page_views'); // 1
Session::increment('page_views'); // 2
Session::increment('page_views', 5); // 7

// Уменьшить
Session::decrement('credits'); // -1
Session::decrement('credits', 10); // -11
```

### Remember (запомнить результат)

```php
// Выполнить callback только если значения нет
$user = Session::remember('current_user', function() {
    return User::find(Session::get('user_id'));
});

// При следующем вызове вернёт сохранённое значение
$user = Session::remember('current_user', function() {
    // Этот код не выполнится
});
```

### Previous URL

```php
// Сохранить предыдущий URL (для redirect back)
Session::setPreviousUrl('/profile');

// Получить предыдущий URL
$previous = Session::getPreviousUrl('/'); // '/profile' или '/' по умолчанию
```

## Примеры использования

### Пример 1: Авторизация пользователя

```php
use Core\Session;

class Auth
{
    public static function login(User $user): void
    {
        // Регенерируем ID для безопасности
        Session::regenerate();
        
        // Сохраняем данные пользователя
        Session::set('user_id', $user->id);
        Session::set('user_role', $user->role);
        Session::set('logged_in_at', time());
        
        Session::flash('success', 'Welcome back, ' . $user->name . '!');
    }
    
    public static function logout(): void
    {
        Session::clear();
        Session::destroy();
        
        Session::flash('info', 'You have been logged out');
    }
    
    public static function check(): bool
    {
        return Session::has('user_id');
    }
    
    public static function user(): ?User
    {
        if (!self::check()) {
            return null;
        }
        
        return Session::remember('user_object', function() {
            $userId = Session::get('user_id');
            return User::find($userId);
        });
    }
    
    public static function id(): ?int
    {
        return Session::get('user_id');
    }
}

// Использование
if (Auth::check()) {
    $user = Auth::user();
    echo "Hello, " . $user->name;
}
```

### Пример 2: Корзина покупок

```php
use Core\Session;

class Cart
{
    public static function add(int $productId, int $quantity = 1): void
    {
        $cart = Session::get('cart', []);
        
        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] += $quantity;
        } else {
            $cart[$productId] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'added_at' => time()
            ];
        }
        
        Session::set('cart', $cart);
        
        Session::flash('success', 'Product added to cart');
    }
    
    public static function remove(int $productId): void
    {
        $cart = Session::get('cart', []);
        unset($cart[$productId]);
        Session::set('cart', $cart);
        
        Session::flash('info', 'Product removed from cart');
    }
    
    public static function get(): array
    {
        return Session::get('cart', []);
    }
    
    public static function count(): int
    {
        return count(self::get());
    }
    
    public static function total(): int
    {
        $cart = self::get();
        $total = 0;
        
        foreach ($cart as $item) {
            $total += $item['quantity'];
        }
        
        return $total;
    }
    
    public static function clear(): void
    {
        Session::delete('cart');
        Session::flash('info', 'Cart cleared');
    }
}

// Использование
Cart::add(123, 2);
Cart::add(456, 1);

$cartItems = Cart::get();
$itemCount = Cart::count(); // 2
$totalItems = Cart::total(); // 3
```

### Пример 3: Мастер с несколькими шагами

```php
use Core\Session;

class WizardManager
{
    private string $wizardKey;
    
    public function __construct(string $wizardName)
    {
        $this->wizardKey = "wizard_$wizardName";
    }
    
    public function setStep(int $step): void
    {
        Session::set("{$this->wizardKey}_step", $step);
    }
    
    public function getStep(): int
    {
        return Session::get("{$this->wizardKey}_step", 1);
    }
    
    public function setData(string $key, mixed $value): void
    {
        $data = $this->getData();
        $data[$key] = $value;
        Session::set("{$this->wizardKey}_data", $data);
    }
    
    public function getData(): array
    {
        return Session::get("{$this->wizardKey}_data", []);
    }
    
    public function nextStep(): void
    {
        Session::increment("{$this->wizardKey}_step");
    }
    
    public function previousStep(): void
    {
        Session::decrement("{$this->wizardKey}_step");
    }
    
    public function complete(): array
    {
        $data = $this->getData();
        $this->clear();
        return $data;
    }
    
    public function clear(): void
    {
        Session::delete("{$this->wizardKey}_step");
        Session::delete("{$this->wizardKey}_data");
    }
}

// Использование
$wizard = new WizardManager('registration');

// Шаг 1
$wizard->setData('email', 'user@example.com');
$wizard->nextStep();

// Шаг 2
$wizard->setData('password', 'hashed_password');
$wizard->nextStep();

// Шаг 3
$wizard->setData('name', 'John Doe');

// Завершение
$userData = $wizard->complete();
// ['email' => '...', 'password' => '...', 'name' => '...']
```

### Пример 4: Flash сообщения в шаблонах

```php
// В контроллере
use Core\Session;

function createUser($data)
{
    $user = User::create($data);
    
    if ($user) {
        Session::flash('success', 'User created successfully!');
        redirect('/users');
    } else {
        Session::flash('error', 'Failed to create user');
        redirect('/users/create');
    }
}

// В шаблоне (welcome.twig или layout)
use Core\Session;

$flash = Session::getAllFlash();

foreach ($flash as $type => $message) {
    $color = match($type) {
        'success' => 'green',
        'error' => 'red',
        'warning' => 'orange',
        'info' => 'blue',
        default => 'gray'
    };
    
    echo "<div style='background: $color; padding: 10px;'>$message</div>";
}
```

### Пример 5: CSRF защита в формах

```php
// Вспомогательная функция для форм
function csrfField(): string
{
    $token = \Core\Session::generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

// В шаблоне формы
?>
<form method="POST" action="/users/create">
    <?= csrfField() ?>
    
    <input type="text" name="name">
    <input type="email" name="email">
    
    <button type="submit">Create User</button>
</form>

<?php
// В контроллере
use Core\Session;
use Core\Http;

function handleUserCreate()
{
    $token = Http::getPostData()['csrf_token'] ?? '';
    
    if (!Session::verifyCsrfToken($token)) {
        Session::flash('error', 'Invalid security token');
        redirect('/users/create');
        return;
    }
    
    // Обрабатываем форму...
}
```

### Пример 6: Rate Limiting

```php
use Core\Session;

class RateLimiter
{
    public static function attempt(string $action, int $maxAttempts = 5, int $decayMinutes = 1): bool
    {
        $key = "rate_limit_$action";
        $attempts = Session::get($key, ['count' => 0, 'time' => time()]);
        
        // Сброс если прошло время
        if (time() - $attempts['time'] > $decayMinutes * 60) {
            $attempts = ['count' => 0, 'time' => time()];
        }
        
        // Проверка лимита
        if ($attempts['count'] >= $maxAttempts) {
            return false;
        }
        
        // Увеличиваем счётчик
        $attempts['count']++;
        Session::set($key, $attempts);
        
        return true;
    }
    
    public static function remaining(string $action, int $maxAttempts = 5): int
    {
        $key = "rate_limit_$action";
        $attempts = Session::get($key, ['count' => 0]);
        
        return max(0, $maxAttempts - $attempts['count']);
    }
}

// Использование
if (!RateLimiter::attempt('login', 5, 15)) {
    Session::flash('error', 'Too many login attempts. Try again in 15 minutes.');
    redirect('/login');
}

// Попытка входа...
```

### Пример 7: Breadcrumbs (хлебные крошки)

```php
use Core\Session;

class Breadcrumbs
{
    public static function push(string $title, string $url): void
    {
        Session::push('breadcrumbs', [
            'title' => $title,
            'url' => $url
        ]);
        
        // Ограничиваем до 5 последних
        $breadcrumbs = Session::get('breadcrumbs', []);
        if (count($breadcrumbs) > 5) {
            $breadcrumbs = array_slice($breadcrumbs, -5);
            Session::set('breadcrumbs', $breadcrumbs);
        }
    }
    
    public static function get(): array
    {
        return Session::get('breadcrumbs', []);
    }
    
    public static function clear(): void
    {
        Session::delete('breadcrumbs');
    }
}

// Использование
Breadcrumbs::push('Home', '/');
Breadcrumbs::push('Users', '/users');
Breadcrumbs::push('Profile', '/users/123');

foreach (Breadcrumbs::get() as $crumb) {
    echo "<a href='{$crumb['url']}'>{$crumb['title']}</a> / ";
}
```

## Лучшие практики

### 1. Регенерируйте ID после авторизации

```php
// ✅ Хорошо - защита от session fixation
function login($user) {
    Session::regenerate();
    Session::set('user_id', $user->id);
}
```

### 2. Очищайте сессию при выходе

```php
// ✅ Хорошо - полная очистка
function logout() {
    Session::clear();
    Session::destroy();
}
```

### 3. Используйте Flash для одноразовых сообщений

```php
// ✅ Хорошо - сообщение показывается один раз
Session::flash('success', 'Saved!');
redirect('/profile');
```

### 4. Всегда проверяйте CSRF токены для POST запросов

```php
// ✅ Хорошо - защита от CSRF
if (!Session::verifyCsrfToken($token)) {
    die('CSRF attack detected');
}
```

### 5. Используйте remember() для кеширования в рамках запроса

```php
// ✅ Хорошо - загрузка пользователя один раз за запрос
$user = Session::remember('user', fn() => User::find($userId));
```

### 6. Закрывайте сессию для длительных операций

```php
// ✅ Хорошо - освобождаем блокировку сессии
Session::save();
performLongRunningTask();
```

## Безопасность

### Защита от Session Fixation

```php
// Всегда регенерируйте после смены привилегий
Session::regenerate();
```

### Защита от Session Hijacking

- Класс автоматически устанавливает `httponly=true`
- Для HTTPS автоматически устанавливает `secure=true`
- Используйте `SameSite` cookies

### Таймауты сессии

```php
// При старте сессии
Session::start([
    'cookie_lifetime' => 3600, // 1 час
    'gc_maxlifetime' => 3600,
]);

// Или установите параметры заранее
Session::setCookieParams(
    lifetime: 3600,
    secure: true,
    httponly: true,
    samesite: 'Strict'
);
Session::start();
```

## Интеграция с другими компонентами

### С Cookie классом

```php
use Core\Cookie;
use Core\Session;

// Remember Me функциональность
if ($rememberMe) {
    $token = generateToken();
    Cookie::setForDays('remember_token', $token, 30);
    Session::set('user_id', $user->id);
}
```

### С Http классом

```php
use Core\Http;
use Core\Session;

// Автоматическая CSRF проверка
if (Http::isPost()) {
    $token = Http::getPostData()['csrf_token'] ?? '';
    
    if (!Session::verifyCsrfToken($token)) {
        http_response_code(419);
        die('CSRF token mismatch');
    }
}
```

## См. также

- [Cookie](Cookie.md) - Работа с cookies
- [Http](Http.md) - HTTP запросы
- [Security Best Practices](Security.md) - Лучшие практики безопасности

