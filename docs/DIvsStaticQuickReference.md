# DI vs Static - Quick Reference

## 📋 Шпаргалка: Как использовать классы фреймворка

### ✅ Внедрять через DI (Instance классы)

```php
public function __construct(
    protected Database $db,
    protected CacheManager $cache,
    protected Router $router,
    protected TemplateEngine $view,
    protected Session $session,
    protected Request $request,
    protected Response $response,
) {}

// Использование
$this->db->query('...');
$this->cache->get('key');
```

### ✅ Вызывать напрямую (Static классы)

```php
use Core\Logger;
use Core\Config;
use Core\Debug;

// Использование - БЕЗ $this, БЕЗ конструктора
Logger::info('message');
Config::get('app.name');
Debug::dump($var);
Environment::isProduction();
Env::get('APP_KEY');
Lang::get('messages.welcome');
Cookie::set('name', 'value');
Path::storage('logs');
Http::getUri();
```

## ⚡ Правило одной строки

**Если класс имеет `static` методы и свойства → вызывай напрямую `Class::method()`**

**Если класс имеет `__construct()` с параметрами → внедряй через DI**

## 🚫 Частая ошибка

```php
// ❌ НЕПРАВИЛЬНО
public function __construct(
    protected Logger $logger,  // Logger - статический!
) {}

$this->logger::info('test');  // Плохо!
```

```php
// ✅ ПРАВИЛЬНО
use Core\Logger;

Logger::info('test');  // Хорошо!
```

---

## 📚 Полная документация

См. [DIvsStatic.md](./DIvsStatic.md)

