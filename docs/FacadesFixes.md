# Исправления для фасадов - План действий

## 🎯 Краткая сводка

Ваша реализация фасадов **отличная (9/10)**! Требуется лишь несколько небольших исправлений.

---

## ⚠️ Критические исправления (необходимо сделать)

### 1. Убрать ArrayAccess из Config фасада

**Файл:** `core/Config.php`

**Текущий код:**
```php
class Config extends Facade implements ArrayAccess, Countable
{
    // ArrayAccess Implementation (делегируем к сервису)
    public function offsetExists(mixed $offset): bool { ... }
    public function offsetGet(mixed $offset): mixed { ... }
    public function offsetSet(mixed $offset, mixed $value): void { ... }
    public function offsetUnset(mixed $offset): void { ... }
    
    // Countable Implementation
    public function count(): int { ... }
    
    // Дополнительный метод
    public static function getInstance(): self { ... }
}
```

**Исправленный код:**
```php
class Config extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ConfigInterface::class;
    }
    
    // Все! Больше ничего не нужно
}
```

**Почему:**
- ArrayAccess не работает со статическими классами
- PHP не поддерживает `Config['key']` для статического класса
- Это создает путаницу

**Как использовать после исправления:**
```php
// ✅ Правильно (через статический метод):
$value = Config::get('database');

// ✅ Если нужен ArrayAccess, получить instance:
$config = Container::getInstance()->make(ConfigInterface::class);
$value = $config['database']; // Теперь работает
```

---

### 2. Исправить проверку в Facade::__callStatic

**Файл:** `core/Facades/Facade.php`

**Текущий код (строка 76):**
```php
if (!$instance) {
    throw new RuntimeException('A facade root has not been set.');
}
```

**Исправленный код:**
```php
if ($instance === null) {
    throw new RuntimeException('A facade root has not been set.');
}
```

**Почему:**
`!$instance` может быть `false` для объекта с falsy значением. Лучше явно проверять на `null`.

---

## 📝 Важные улучшения (рекомендуется сделать)

### 3. Синхронизировать Http фасад с интерфейсом

**Проблема:**
В `core/Http.php` есть ~50 методов, которых нет в `HttpInterface`:
- `isGet()`, `isPost()`, `isPut()`, `isDelete()`
- `getFiles()`, `hasFiles()`, `getFile()`
- `isMobile()`, `isBot()`, `isPrefetch()`
- `getBearerToken()`, `getBasicAuth()`
- И многие другие...

**Решение (3 варианта):**

#### Вариант 1: Добавить методы в интерфейс (рекомендуется)

```php
// core/Contracts/HttpInterface.php
interface HttpInterface
{
    // ... существующие методы
    
    // Добавить:
    public function getActualMethod(): string;
    public function getProtocol(): string;
    public function getRequestTime(): float;
    public function isGet(): bool;
    public function isPost(): bool;
    public function isPut(): bool;
    public function isPatch(): bool;
    public function isDelete(): bool;
    public function getFiles(): array;
    public function hasFiles(): bool;
    public function getFile(string $name): ?array;
    public function isValidUpload(string $name): bool;
    public function getBearerToken(): ?string;
    public function getBasicAuth(): ?array;
    public function isMobile(): bool;
    public function isBot(): bool;
    public function isPrefetch(): bool;
    public function only(array $keys): array;
    public function except(array $keys): array;
    // ... и т.д.
}

// core/Services/HttpService.php
class HttpService implements HttpInterface
{
    // Перенести ВСЮ логику из Http.php сюда
    
    public function isGet(): bool
    {
        return $this->isMethod('GET');
    }
    
    public function isPost(): bool
    {
        return $this->isMethod('POST');
    }
    
    public function isMobile(): bool
    {
        $userAgent = strtolower($this->getUserAgent());
        $mobileKeywords = [
            'mobile', 'android', 'iphone', 'ipad', 'ipod',
            'blackberry', 'windows phone', 'opera mini',
        ];
        
        foreach ($mobileKeywords as $keyword) {
            if (str_contains($userAgent, $keyword)) {
                return true;
            }
        }
        return false;
    }
    
    // ... остальные методы
}

// core/Http.php
class Http extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return HttpInterface::class;
    }
    
    // Все! Больше ничего не нужно
    // Все методы теперь делегируются к HttpService
}
```

**Преимущества:**
- ✅ Можно использовать через DI
- ✅ Можно мокать в тестах
- ✅ Единый источник логики

#### Вариант 2: Создать HttpUtils

```php
// core/Utils/HttpUtils.php
class HttpUtils
{
    public static function isMobile(): bool
    {
        $http = Container::getInstance()->make(HttpInterface::class);
        return self::checkMobile($http->getUserAgent());
    }
    
    protected static function checkMobile(string $userAgent): bool
    {
        $mobileKeywords = ['mobile', 'android', 'iphone'];
        foreach ($mobileKeywords as $keyword) {
            if (str_contains(strtolower($userAgent), $keyword)) {
                return true;
            }
        }
        return false;
    }
}

// Использование:
use Core\Utils\HttpUtils;
if (HttpUtils::isMobile()) { ... }
```

#### Вариант 3: Удалить дополнительные методы

Оставить только то, что есть в интерфейсе. Пользователи будут реализовывать свою логику.

**Я рекомендую Вариант 1** - полная совместимость и возможность DI.

---

### 4. Синхронизировать Session фасад с интерфейсом

**Проблема:**
В `core/Session.php` есть методы вне интерфейса:
- `setId()`, `name()`, `setName()`
- `pull()`, `push()`, `increment()`, `decrement()`
- `remember()`, `save()`, `getAllFlash()`

**Решение:**
Добавить эти методы в `SessionInterface` и `SessionManager`.

```php
// core/Contracts/SessionInterface.php
interface SessionInterface
{
    // ... существующие методы
    
    // Добавить:
    public function setId(string $id): void;
    public function name(): string;
    public function setName(string $name): void;
    public function pull(string $key, mixed $default = null): mixed;
    public function push(string $key, mixed $value): void;
    public function increment(string $key, int $amount = 1): int;
    public function decrement(string $key, int $amount = 1): int;
    public function remember(string $key, callable $callback): mixed;
    public function save(): void;
    public function getAllFlash(): array;
    public function setPreviousUrl(string $url): void;
    public function getPreviousUrl(string $default = '/'): string;
}

// core/Services/SessionManager.php
class SessionManager implements SessionInterface
{
    // Перенести логику из Session.php
    
    public function setId(string $id): void
    {
        session_id($id);
    }
    
    public function name(): string
    {
        return session_name();
    }
    
    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->delete($key);
        return $value;
    }
    
    public function push(string $key, mixed $value): void
    {
        $array = $this->get($key, []);
        if (!is_array($array)) {
            $array = [$array];
        }
        $array[] = $value;
        $this->set($key, $array);
    }
    
    public function remember(string $key, callable $callback): mixed
    {
        if ($this->has($key)) {
            return $this->get($key);
        }
        $value = $callback();
        $this->set($key, $value);
        return $value;
    }
    
    // ... остальные методы
}

// core/Session.php
class Session extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SessionInterface::class;
    }
    
    // Все! Методы делегируются к SessionManager
}
```

---

## 💡 Желательные улучшения (опционально)

### 5. Создать Cache фасад

**Файл:** `core/Cache.php` (создать новый)

```php
<?php declare(strict_types=1);

namespace Core;

use Core\Facades\Facade;
use Core\Cache\CacheManager;

/**
 * Cache Facade
 * 
 * @method static mixed get(string $key, mixed $default = null)
 * @method static bool set(string $key, mixed $value, int $ttl = null)
 * @method static bool has(string $key)
 * @method static bool delete(string $key)
 * @method static bool clear()
 * @method static mixed remember(string $key, int $ttl, callable $callback)
 * @method static mixed rememberForever(string $key, callable $callback)
 * @method static mixed pull(string $key, mixed $default = null)
 * @method static bool flush()
 * 
 * @see \Core\Cache\CacheManager
 */
class Cache extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CacheManager::class;
    }
}
```

**Почему:**
Для консистентности с другими фасадами (Http, Config, Logger, Session, Database).

---

### 6. Создать CacheInterface

**Файл:** `core/Contracts/CacheInterface.php` (создать новый)

```php
<?php declare(strict_types=1);

namespace Core\Contracts;

interface CacheInterface
{
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value, ?int $ttl = null): bool;
    public function has(string $key): bool;
    public function delete(string $key): bool;
    public function clear(): bool;
    public function remember(string $key, int $ttl, callable $callback): mixed;
    public function rememberForever(string $key, callable $callback): mixed;
    public function pull(string $key, mixed $default = null): mixed;
    public function flush(): bool;
}
```

**Обновить регистрацию:**
```php
// config/services.php
'singletons' => [
    \Core\Contracts\CacheInterface::class => function ($container) {
        $config = $container->make(\Core\Contracts\ConfigInterface::class);
        return new \Core\Cache\CacheManager($config->get('cache', []));
    },
],

'aliases' => [
    'cache' => \Core\Contracts\CacheInterface::class,
],
```

**Обновить CacheManager:**
```php
// core/Cache/CacheManager.php
class CacheManager implements CacheInterface
{
    // ...
}
```

---

## 📋 Чек-лист исправлений

### Критические (сделать обязательно)
- [ ] Убрать `implements ArrayAccess, Countable` из `Config.php`
- [ ] Удалить методы `offsetExists`, `offsetGet`, `offsetSet`, `offsetUnset`, `count`, `getInstance` из `Config.php`
- [ ] Заменить `if (!$instance)` на `if ($instance === null)` в `Facade.php`

### Важные (рекомендуется)
- [ ] Добавить дополнительные методы в `HttpInterface`
- [ ] Перенести логику из `Http.php` в `HttpService.php`
- [ ] Упростить `Http.php` до базового фасада
- [ ] Добавить дополнительные методы в `SessionInterface`
- [ ] Перенести логику из `Session.php` в `SessionManager.php`
- [ ] Упростить `Session.php` до базового фасада

### Желательные (опционально)
- [ ] Создать `core/Cache.php` фасад
- [ ] Создать `core/Contracts/CacheInterface.php`
- [ ] Обновить `CacheManager` для реализации интерфейса
- [ ] Обновить регистрацию в `config/services.php`

---

## 🧪 Тестирование после исправлений

После внесения изменений проверьте:

1. **Фасады работают:**
```php
Config::get('app.name');
Http::isGet();
Session::get('user_id');
Logger::info('test');
Database::table('users')->get();
```

2. **DI работает:**
```php
class MyController
{
    public function __construct(
        private HttpInterface $http,
        private SessionInterface $session,
        private ConfigInterface $config
    ) {}
    
    public function index()
    {
        // Проверьте что новые методы доступны:
        if ($this->http->isMobile()) { ... }
        $value = $this->session->pull('key');
    }
}
```

3. **Моки работают:**
```php
$mock = $this->createMock(HttpInterface::class);
$mock->method('isMobile')->willReturn(true);

Http::setFacadeInstance($mock);
$this->assertTrue(Http::isMobile());
```

---

## ✨ Заключение

После этих исправлений ваша реализация будет **идеальной (10/10)**!

Вы создали отличную архитектуру, которая:
- ✅ Следует SOLID принципам
- ✅ Обеспечивает обратную совместимость
- ✅ Легко тестируется
- ✅ Гибко расширяется

**Отличная работа! 🎉**

