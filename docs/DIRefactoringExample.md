# Практический пример рефакторинга для DI

## Рефакторинг класса Session

### Шаг 1: Создание интерфейса

```php
// core/Contracts/SessionInterface.php
<?php declare(strict_types=1);

namespace Core\Contracts;

interface SessionInterface
{
    public function start(array $options = []): bool;
    public function isStarted(): bool;
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value): void;
    public function has(string $key): bool;
    public function delete(string $key): void;
    public function all(): array;
    public function clear(): void;
    public function destroy(): bool;
    public function regenerate(bool $deleteOldSession = true): bool;
    public function flash(string $key, mixed $value): void;
    public function getFlash(string $key, mixed $default = null): mixed;
}
```

### Шаг 2: Создание instance-based реализации

```php
// core/Services/SessionManager.php
<?php declare(strict_types=1);

namespace Core\Services;

use Core\Contracts\SessionInterface;
use Core\Contracts\HttpInterface;

class SessionManager implements SessionInterface
{
    private bool $started = false;

    public function __construct(
        private HttpInterface $http
    ) {}

    public function start(array $options = []): bool
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }

        // Настройки безопасности по умолчанию
        $defaultOptions = [
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'use_strict_mode' => true,
        ];

        // Используем внедренную зависимость вместо статического вызова
        if ($this->http->isSecure()) {
            $defaultOptions['cookie_secure'] = true;
        }

        $options = array_merge($defaultOptions, $options);

        $this->started = session_start($options);
        
        return $this->started;
    }

    public function isStarted(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->ensureStarted();
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->ensureStarted();
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        $this->ensureStarted();
        return array_key_exists($key, $_SESSION);
    }

    public function delete(string $key): void
    {
        $this->ensureStarted();
        unset($_SESSION[$key]);
    }

    public function all(): array
    {
        $this->ensureStarted();
        return $_SESSION;
    }

    public function clear(): void
    {
        $this->ensureStarted();
        $_SESSION = [];
    }

    public function destroy(): bool
    {
        $this->ensureStarted();
        
        $_SESSION = [];
        
        if ($this->http->getCookie(session_name()) !== null) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 3600,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        $this->started = false;
        
        return session_destroy();
    }

    public function regenerate(bool $deleteOldSession = true): bool
    {
        $this->ensureStarted();
        return session_regenerate_id($deleteOldSession);
    }

    public function flash(string $key, mixed $value): void
    {
        $this->set("_flash.$key", $value);
    }

    public function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $this->get("_flash.$key", $default);
        $this->delete("_flash.$key");
        return $value;
    }

    private function ensureStarted(): void
    {
        if (!$this->isStarted()) {
            $this->start();
        }
    }
}
```

### Шаг 3: Создание интерфейса для Http

```php
// core/Contracts/HttpInterface.php
<?php declare(strict_types=1);

namespace Core\Contracts;

interface HttpInterface
{
    public function isSecure(): bool;
    public function getHost(): string;
    public function getMethod(): string;
    public function getUri(): string;
    public function getCookie(string $name): ?string;
    public function getCookies(): array;
    // ... другие методы
}
```

### Шаг 4: Рефакторинг Http в instance-based

```php
// core/Services/HttpService.php
<?php declare(strict_types=1);

namespace Core\Services;

use Core\Contracts\HttpInterface;

class HttpService implements HttpInterface
{
    public function isSecure(): bool
    {
        if (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || $this->getPort() == 443
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        ) {
            return true;
        }
        return false;
    }

    public function getHost(): string
    {
        return $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    }

    public function getMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    public function getUri(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    public function getPort(): int
    {
        return (int)($_SERVER['SERVER_PORT'] ?? 80);
    }

    public function getCookie(string $name): ?string
    {
        return $_COOKIE[$name] ?? null;
    }

    public function getCookies(): array
    {
        return $_COOKIE ?? [];
    }

    // ... остальные методы
}
```

### Шаг 5: Создание фасада для обратной совместимости

```php
// core/Session.php (заменяем старый класс)
<?php declare(strict_types=1);

namespace Core;

use Core\Contracts\SessionInterface;

/**
 * Статический фасад для SessionManager
 * Обеспечивает обратную совместимость со старым API
 */
class Session
{
    private static ?SessionInterface $instance = null;

    /**
     * Получить instance SessionManager из контейнера
     */
    protected static function getManager(): SessionInterface
    {
        if (self::$instance === null) {
            self::$instance = Container::getInstance()->make(SessionInterface::class);
        }
        return self::$instance;
    }

    /**
     * Установить custom instance (для тестирования)
     */
    public static function setInstance(SessionInterface $instance): void
    {
        self::$instance = $instance;
    }

    // Фасад-методы (делегируют к SessionManager)
    
    public static function start(array $options = []): bool
    {
        return self::getManager()->start($options);
    }

    public static function isStarted(): bool
    {
        return self::getManager()->isStarted();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::getManager()->get($key, $default);
    }

    public static function set(string $key, mixed $value): void
    {
        self::getManager()->set($key, $value);
    }

    public static function has(string $key): bool
    {
        return self::getManager()->has($key);
    }

    public static function delete(string $key): void
    {
        self::getManager()->delete($key);
    }

    public static function all(): array
    {
        return self::getManager()->all();
    }

    public static function clear(): void
    {
        self::getManager()->clear();
    }

    public static function destroy(): bool
    {
        return self::getManager()->destroy();
    }

    public static function regenerate(bool $deleteOldSession = true): bool
    {
        return self::getManager()->regenerate($deleteOldSession);
    }

    public static function flash(string $key, mixed $value): void
    {
        self::getManager()->flash($key, $value);
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        return self::getManager()->getFlash($key, $default);
    }
}
```

### Шаг 6: Регистрация в контейнере

```php
// config/services.php
return [
    'singletons' => [
        // HTTP Service
        \Core\Contracts\HttpInterface::class => \Core\Services\HttpService::class,
        
        // Session Service
        \Core\Contracts\SessionInterface::class => \Core\Services\SessionManager::class,
        
        // Остальные сервисы...
    ],
    
    'aliases' => [
        'http' => \Core\Contracts\HttpInterface::class,
        'session' => \Core\Contracts\SessionInterface::class,
    ],
];
```

### Шаг 7: Использование через DI в контроллерах

```php
// app/Controllers/UserController.php
<?php declare(strict_types=1);

namespace App\Controllers;

use Core\Contracts\SessionInterface;
use Core\Contracts\DatabaseInterface;

class UserController
{
    public function __construct(
        private SessionInterface $session,
        private DatabaseInterface $db
    ) {}

    public function login()
    {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        $user = $this->db->table('users')
            ->where('username', $username)
            ->first();
            
        if ($user && password_verify($password, $user['password'])) {
            $this->session->set('user_id', $user['id']);
            $this->session->flash('success', 'Успешный вход!');
            
            return redirect('/dashboard');
        }
        
        $this->session->flash('error', 'Неверные учетные данные');
        return redirect('/login');
    }
    
    public function logout()
    {
        $this->session->destroy();
        return redirect('/');
    }
}
```

### Шаг 8: Старый код продолжает работать!

```php
// Старый код с статическими вызовами продолжает работать
Session::start();
Session::set('user_id', 123);
$userId = Session::get('user_id');

// Внутри фасад делегирует к SessionManager через DI контейнер!
```

### Шаг 9: Тестирование стало простым!

```php
// tests/Unit/UserControllerTest.php
<?php

use App\Controllers\UserController;
use Core\Contracts\SessionInterface;
use Core\Contracts\DatabaseInterface;
use PHPUnit\Framework\TestCase;

class UserControllerTest extends TestCase
{
    public function test_successful_login()
    {
        // Создаем моки зависимостей
        $sessionMock = $this->createMock(SessionInterface::class);
        $dbMock = $this->createMock(DatabaseInterface::class);
        
        // Настраиваем поведение моков
        $dbMock->expects($this->once())
            ->method('table')
            ->with('users')
            ->willReturnSelf();
            
        $dbMock->expects($this->once())
            ->method('where')
            ->with('username', 'john')
            ->willReturnSelf();
            
        $dbMock->expects($this->once())
            ->method('first')
            ->willReturn([
                'id' => 1,
                'username' => 'john',
                'password' => password_hash('secret', PASSWORD_DEFAULT)
            ]);
            
        $sessionMock->expects($this->once())
            ->method('set')
            ->with('user_id', 1);
            
        $sessionMock->expects($this->once())
            ->method('flash')
            ->with('success', 'Успешный вход!');
        
        // Создаем контроллер с моками
        $controller = new UserController($sessionMock, $dbMock);
        
        // Тестируем
        $_POST['username'] = 'john';
        $_POST['password'] = 'secret';
        
        $response = $controller->login();
        
        $this->assertInstanceOf(Response::class, $response);
    }
}
```

## Преимущества после рефакторинга

### До:
```php
class Session {
    public static function start(): bool {
        if (Http::isSecure()) { // ❌ Жесткая зависимость
            // ...
        }
    }
}

// ❌ Невозможно тестировать
// ❌ Нельзя подменить Http
// ❌ Нарушение DIP
```

### После:
```php
class SessionManager {
    public function __construct(
        private HttpInterface $http // ✅ Внедрение зависимости
    ) {}
    
    public function start(): bool {
        if ($this->http->isSecure()) { // ✅ Используем интерфейс
            // ...
        }
    }
}

// ✅ Легко тестировать с моками
// ✅ Можно подменить реализацию Http
// ✅ Соответствует SOLID
// ✅ Старый код работает через фасад
```

## Итоговая структура

```
core/
├── Contracts/           # Интерфейсы
│   ├── SessionInterface.php
│   ├── HttpInterface.php
│   ├── ConfigInterface.php
│   └── DatabaseInterface.php
│
├── Services/           # Instance-based реализации
│   ├── SessionManager.php
│   ├── HttpService.php
│   ├── ConfigRepository.php
│   └── DatabaseManager.php (уже есть!)
│
├── Session.php        # Статический фасад (обратная совместимость)
├── Http.php           # Статический фасад
├── Config.php         # Статический фасад
├── Database.php       # Статический фасад
└── Container.php      # DI контейнер (без изменений)
```

## Миграция существующего кода

### Вариант 1: Постепенная миграция через фасады
Старый код продолжает работать без изменений:
```php
Session::start();
Config::get('app.name');
```

### Вариант 2: Новый код использует DI
Новые контроллеры и сервисы используют DI:
```php
class MyController {
    public function __construct(
        private SessionInterface $session,
        private ConfigInterface $config
    ) {}
}
```

### Вариант 3: Постепенный рефакторинг старого кода
По мере работы над старыми контроллерами, добавляем DI:
```php
// Было:
class OldController {
    public function index() {
        $user = Session::get('user');
    }
}

// Стало:
class OldController {
    public function __construct(
        private SessionInterface $session
    ) {}
    
    public function index() {
        $user = $this->session->get('user');
    }
}
```

## Заключение

Этот рефакторинг обеспечивает:
- ✅ **Чистую архитектуру** с правильным DI
- ✅ **Обратную совместимость** через фасады
- ✅ **Тестируемость** с моками
- ✅ **Гибкость** в расширении и модификации
- ✅ **Постепенную миграцию** без breaking changes

Такой же подход можно применить ко всем остальным сервисам: Config, Logger, Database и т.д.

