# Dependency Injection vs Static Classes

## 🎯 Правило

**Статические классы НЕ внедряются через DI** - их вызывают напрямую.

## ❌ Неправильно

```php
class HomeController extends Controller
{
    public function __construct(
        protected Logger $logger,  // ❌ Logger - статический класс!
    ) {
        parent::__construct($request, $response);
    }
    
    public function index()
    {
        $this->logger::info('test');  // ❌ Смешанный синтаксис
    }
}
```

## ✅ Правильно

```php
use Core\Logger;

class HomeController extends Controller
{
    public function __construct(
        // Logger не нужен в конструкторе
    ) {
        parent::__construct($request, $response);
    }
    
    public function index()
    {
        Logger::info('test');  // ✅ Прямой вызов
    }
}
```

---

## 📋 Классификация классов фреймворка

### Статические классы (НЕ внедрять через DI)

Вызывайте напрямую через `ClassName::method()`:

| Класс | Примеры использования |
|-------|----------------------|
| `Config` | `Config::get('app.name')` |
| `Logger` | `Logger::info('message')` |
| `Debug` | `Debug::dump($var)` |
| `Env` | `Env::get('APP_ENV')` |
| `Environment` | `Environment::isProduction()` |
| `Lang` | `Lang::get('welcome.title')` |
| `Cookie` | `Cookie::set('name', 'value')` |
| `Path` | `Path::public('assets')` |
| `Http` | `Http::getUri()` |
| `Cache` (фасад) | `Cache::get('key')` |
| `Emailer` (статический API) | `Emailer::send($message)` |

### Инстанс-классы (ВНЕДРЯТЬ через DI)

Внедряйте в конструктор:

| Класс | Тип | Как внедрять |
|-------|-----|--------------|
| `Database` | Instance | `protected Database $db` |
| `CacheManager` | Instance | `protected CacheManager $cache` |
| `Router` | Instance | `protected Router $router` |
| `TemplateEngine` | Instance | `protected TemplateEngine $view` |
| `Session` | Instance | `protected Session $session` |
| `Request` | Instance | `protected Request $request` |
| `Response` | Instance | `protected Response $response` |

---

## 💡 Примеры правильного использования

### Контроллер с DI

```php
<?php

namespace App\Controllers;

use Core\Database;
use Core\CacheManager;
use Core\Logger;      // ← Импорт для статического использования
use Core\Config;      // ← Импорт для статического использования
use Core\Request;
use Core\Response;

class UserController extends Controller
{
    public function __construct(
        Request                $request,
        Response               $response,
        protected Database     $db,        // ✅ Instance класс - внедряем
        protected CacheManager $cache,     // ✅ Instance класс - внедряем
    )
    {
        parent::__construct($request, $response);
    }

    public function index(): Response
    {
        // ✅ Статические классы - напрямую
        $perPage = Config::get('pagination.per_page', 15);
        Logger::info('Fetching users list');
        
        // ✅ Instance классы - через $this
        $users = $this->db->table('users')
            ->limit($perPage)
            ->get();
        
        // ✅ Кэширование через внедренный instance
        $stats = $this->cache->remember('user_stats', 3600, function() {
            return $this->db->table('users')->count();
        });
        
        return $this->view('users.index', [
            'users' => $users,
            'stats' => $stats,
        ]);
    }
}
```

### Сервис-класс

```php
<?php

namespace App\Services;

use Core\Database;
use Core\Logger;
use Core\Config;

class OrderService
{
    public function __construct(
        protected Database $db
    ) {}
    
    public function create(array $data): array
    {
        // Статические классы
        $taxRate = Config::get('billing.tax_rate', 0.2);
        Logger::info('Creating order', $data);
        
        // Instance методы
        return $this->db->transaction(function() use ($data, $taxRate) {
            $order = $this->db->table('orders')->insertGetId([
                'user_id' => $data['user_id'],
                'total' => $data['amount'] * (1 + $taxRate),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            
            Logger::info("Order created: #{$order}");
            
            return ['id' => $order];
        });
    }
}
```

---

## 🔍 Как определить тип класса?

### Признаки статического класса:

```php
class Logger
{
    protected static array $logs = [];  // ← static свойства
    
    public static function info($msg)   // ← static методы
    {
        self::$logs[] = $msg;
    }
}
```

### Признаки instance класса:

```php
class Database
{
    protected PDO $connection;  // ← instance свойство
    
    public function __construct(array $config)  // ← есть конструктор с параметрами
    {
        $this->connection = new PDO(...);
    }
    
    public function query($sql)  // ← instance метод
    {
        return $this->connection->query($sql);
    }
}
```

---

## ⚠️ Частые ошибки

### 1. Внедрение статического класса

```php
// ❌ НЕПРАВИЛЬНО
public function __construct(
    protected Logger $logger,
) {}

public function action() {
    $this->logger::info('test');  // Работает, но это плохая практика!
}
```

```php
// ✅ ПРАВИЛЬНО
use Core\Logger;

public function action() {
    Logger::info('test');
}
```

### 2. Статический вызов instance метода

```php
// ❌ НЕПРАВИЛЬНО
Database::query('SELECT * FROM users');  // Fatal Error!
```

```php
// ✅ ПРАВИЛЬНО
public function __construct(
    protected Database $db
) {}

public function action() {
    $this->db->query('SELECT * FROM users');
}
```

### 3. Регистрация статических классов в контейнере

```php
// ❌ НЕПРАВИЛЬНО в config/services.php
'singletons' => [
    \Core\Logger::class => \Core\Logger::class,  // Не нужно!
    \Core\Config::class => \Core\Config::class,  // Не нужно!
]
```

```php
// ✅ ПРАВИЛЬНО - регистрируем только instance классы
'singletons' => [
    \Core\Database::class => \Core\Database::class,
    \Core\CacheManager::class => function($container) {
        return new CacheManager(Config::get('cache', []));
    },
]
```

---

## 🎓 Best Practices

1. **Статические классы** - для утилит, которые не держат состояние или держат глобальное состояние (Config, Logger, Environment)

2. **Instance классы** - для сервисов с состоянием, подключениями, конфигурацией (Database, CacheManager, Session)

3. **Не смешивайте** - если класс статический, используйте его статически везде

4. **DI предпочтительнее** - если есть выбор, лучше использовать DI (тестируемость, гибкость)

---

## 📚 См. также

- [DependencyInjection.md](./DependencyInjection.md)
- [DIExamples.md](./DIExamples.md)

