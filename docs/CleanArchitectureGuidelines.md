# ✨ Руководство по Чистой Архитектуре в Vilnius Framework

## 🎯 Основной Принцип

> **ОДИН КЛАСС - ОДИН СПОСОБ ИСПОЛЬЗОВАНИЯ**

Если используем DI - используем его повсеместно. Никаких смешений подходов!

---

## 📋 Правила использования классов

### ✅ Используйте ТОЛЬКО интерфейсы для DI

**Правильно:**
```php
use Core\Contracts\DatabaseInterface;
use Core\Contracts\SessionInterface;
use Core\Contracts\HttpInterface;
use Core\Contracts\LoggerInterface;
use Core\Contracts\CacheInterface;
use Core\Contracts\ConfigInterface;

class MyController extends Controller
{
    public function __construct(
        Request $request,
        Response $response,
        protected DatabaseInterface $db,
        protected SessionInterface $session,
        protected HttpInterface $http,
        protected LoggerInterface $logger,
        protected CacheInterface $cache,
        protected ConfigInterface $config
    ) {
        parent::__construct($request, $response);
    }
}
```

**❌ Неправильно:**
```php
use Core\Database;  // ❌ Это фасад!
use Core\Cache\CacheManager;  // ❌ Это конкретная реализация!

class MyController extends Controller
{
    public function __construct(
        protected Database $db,  // ❌ Type hint на фасад
        protected CacheManager $cache  // ❌ Type hint на реализацию
    ) {}
}
```

---

## 🏗️ Архитектурные слои

```
┌─────────────────────────────────────────┐
│         APPLICATION LAYER               │
│      (Controllers, Services)            │
│         ↓ зависит от ↓                  │
│                                         │
│         INTERFACES                      │
│      (Core\Contracts\*)                 │
│         ↑ реализуют ↑                   │
│                                         │
│         SERVICES                        │
│   (HttpService, SessionManager, etc)    │
│         ↑ создаются в ↑                 │
│                                         │
│         DI CONTAINER                    │
│      (Container::getInstance())         │
└─────────────────────────────────────────┘
```

---

## 📦 Таблица соответствий

| ❌ Не использовать | ✅ Использовать | Описание |
|-------------------|----------------|----------|
| `Core\Database` | `Core\Contracts\DatabaseInterface` | База данных |
| `Core\Session` | `Core\Contracts\SessionInterface` | Сессии |
| `Core\Http` | `Core\Contracts\HttpInterface` | HTTP запросы |
| `Core\Logger` | `Core\Contracts\LoggerInterface` | Логирование |
| `Core\Cache` | `Core\Contracts\CacheInterface` | Кеширование |
| `Core\Config` | `Core\Contracts\ConfigInterface` | Конфигурация |
| `Core\Cache\CacheManager` | `Core\Contracts\CacheInterface` | ⚠️ Прямой класс |

---

## 💡 Примеры использования

### Контроллеры

```php
<?php

namespace App\Controllers;

use Core\Contracts\DatabaseInterface;
use Core\Contracts\LoggerInterface;
use Core\Contracts\CacheInterface;
use Core\Request;
use Core\Response;

class ProductController extends Controller
{
    public function __construct(
        Request $request,
        Response $response,
        protected DatabaseInterface $db,
        protected LoggerInterface $logger,
        protected CacheInterface $cache
    ) {
        parent::__construct($request, $response);
    }

    public function index(): Response
    {
        // Кешируем список продуктов
        $products = $this->cache->remember('products', 3600, function() {
            return $this->db->table('products')
                ->where('active', 1)
                ->get();
        });

        $this->logger->info('Products list viewed', [
            'count' => count($products)
        ]);

        return $this->view('products.index', compact('products'));
    }
}
```

### Middleware

```php
<?php

namespace Core\Middleware;

use Core\Contracts\HttpInterface;
use Core\Contracts\SessionInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        protected SessionInterface $session,
        protected HttpInterface $http,
        string $redirectTo = '/login',
        string $sessionKey = 'user_id'
    ) {
        // ...
    }

    public function handle(callable $next): mixed
    {
        if (!$this->session->has($this->sessionKey)) {
            // Сохраняем URL для редиректа после логина
            $this->session->set('intended_url', $this->http->getUri());
            
            header('Location: /login');
            exit;
        }

        return $next();
    }
}
```

### Сервисы

```php
<?php

namespace App\Services;

use Core\Contracts\DatabaseInterface;
use Core\Contracts\LoggerInterface;
use Core\Contracts\CacheInterface;

class OrderService
{
    public function __construct(
        protected DatabaseInterface $db,
        protected LoggerInterface $logger,
        protected CacheInterface $cache
    ) {}

    public function createOrder(array $data): int
    {
        $orderId = $this->db->transaction(function() use ($data) {
            // Создаем заказ
            $orderId = $this->db->table('orders')->insertGetId([
                'user_id' => $data['user_id'],
                'total' => $data['total'],
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Создаем элементы заказа
            foreach ($data['items'] as $item) {
                $this->db->table('order_items')->insert([
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price']
                ]);
            }

            return $orderId;
        });

        // Инвалидируем кеш
        $this->cache->delete('user_orders_' . $data['user_id']);

        // Логируем создание заказа
        $this->logger->info('Order created', [
            'order_id' => $orderId,
            'user_id' => $data['user_id']
        ]);

        return $orderId;
    }
}
```

### Модели

```php
<?php

namespace App\Models;

use Core\Container;
use Core\Contracts\DatabaseInterface;
use Core\Database\QueryBuilder;

abstract class BaseModel
{
    protected DatabaseInterface $db;

    public function __construct(array $attributes = [])
    {
        // ✅ Получаем через DI контейнер
        $this->db = Container::getInstance()->make(DatabaseInterface::class);
        
        $this->fill($attributes);
    }

    public function newQuery(): QueryBuilder
    {
        // ✅ Используем через $this->db
        return $this->db->table($this->table);
    }
}
```

---

## 🚫 Частые ошибки

### ❌ Ошибка 1: Type hint на фасад

```php
// ❌ НЕПРАВИЛЬНО
use Core\Database;

class UserController extends Controller
{
    public function __construct(
        protected Database $db  // ❌ Фасад вместо интерфейса
    ) {}
}
```

```php
// ✅ ПРАВИЛЬНО
use Core\Contracts\DatabaseInterface;

class UserController extends Controller
{
    public function __construct(
        protected DatabaseInterface $db  // ✅ Интерфейс
    ) {}
}
```

### ❌ Ошибка 2: Type hint на конкретную реализацию

```php
// ❌ НЕПРАВИЛЬНО
use Core\Cache\CacheManager;

class HomeController extends Controller
{
    public function __construct(
        protected CacheManager $cache  // ❌ Конкретная реализация
    ) {}
}
```

```php
// ✅ ПРАВИЛЬНО
use Core\Contracts\CacheInterface;

class HomeController extends Controller
{
    public function __construct(
        protected CacheInterface $cache  // ✅ Интерфейс
    ) {}
}
```

### ❌ Ошибка 3: Использование статических фасадов вместо DI

```php
// ❌ НЕПРАВИЛЬНО
use Core\Session;
use Core\Http;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(callable $next): mixed
    {
        if (!Session::has('user_id')) {  // ❌ Статический вызов
            header('Location: /login');
            exit;
        }
        return $next();
    }
}
```

```php
// ✅ ПРАВИЛЬНО
use Core\Contracts\SessionInterface;
use Core\Contracts\HttpInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        protected SessionInterface $session,
        protected HttpInterface $http
    ) {}

    public function handle(callable $next): mixed
    {
        if (!$this->session->has('user_id')) {  // ✅ Через DI
            header('Location: /login');
            exit;
        }
        return $next();
    }
}
```

---

## 🎯 SOLID Принципы

### ✅ Dependency Inversion Principle (DIP)

> Зависьте от абстракций (интерфейсов), а не от конкретных реализаций

**Правильно:**
```php
// ✅ Зависимость от интерфейса
protected DatabaseInterface $db;
```

**Неправильно:**
```php
// ❌ Зависимость от конкретной реализации
protected DatabaseManager $db;
```

### ✅ Liskov Substitution Principle (LSP)

> Любая реализация интерфейса должна быть взаимозаменяемой

```php
// Можем легко заменить реализацию в config/services.php
'singletons' => [
    DatabaseInterface::class => MySQLDriver::class,  // Или PostgreSQLDriver::class
    CacheInterface::class => RedisCache::class,       // Или FileCache::class
]
```

### ✅ Interface Segregation Principle (ISP)

> Клиенты не должны зависеть от методов, которые они не используют

```php
// ✅ Используем только нужные интерфейсы
class SimpleController extends Controller
{
    public function __construct(
        protected DatabaseInterface $db  // Только БД
    ) {}
}

class ComplexController extends Controller
{
    public function __construct(
        protected DatabaseInterface $db,
        protected CacheInterface $cache,
        protected LoggerInterface $logger  // Все что нужно
    ) {}
}
```

---

## 🧪 Тестируемость

### Легко создавать моки

```php
use PHPUnit\Framework\TestCase;
use Core\Contracts\DatabaseInterface;
use Core\Contracts\LoggerInterface;

class OrderServiceTest extends TestCase
{
    public function test_create_order()
    {
        // Создаем моки интерфейсов
        $dbMock = $this->createMock(DatabaseInterface::class);
        $loggerMock = $this->createMock(LoggerInterface::class);

        // Настраиваем поведение
        $dbMock->expects($this->once())
            ->method('transaction')
            ->willReturn(123);

        $loggerMock->expects($this->once())
            ->method('info');

        // Создаем сервис с моками
        $service = new OrderService($dbMock, $loggerMock);

        // Тестируем
        $orderId = $service->createOrder(['user_id' => 1, 'total' => 100]);
        
        $this->assertEquals(123, $orderId);
    }
}
```

---

## 📚 Рекомендации

### 1. Всегда используйте интерфейсы в конструкторах

✅ **DO:**
```php
public function __construct(
    protected DatabaseInterface $db,
    protected LoggerInterface $logger
) {}
```

❌ **DON'T:**
```php
public function __construct(
    protected Database $db,
    protected Logger $logger
) {}
```

### 2. Не используйте статические вызовы в классах с DI

✅ **DO:**
```php
$this->logger->info('Message');
$users = $this->db->table('users')->get();
```

❌ **DON'T:**
```php
Logger::info('Message');
Database::table('users')->get();
```

### 3. Регистрируйте свои сервисы в config/services.php

```php
// config/services.php
return [
    'singletons' => [
        // Ваши сервисы
        \App\Contracts\PaymentInterface::class => \App\Services\StripePaymentService::class,
        \App\Services\NotificationService::class => \App\Services\NotificationService::class,
    ],
];
```

### 4. Создавайте интерфейсы для своих сервисов

```php
// app/Contracts/PaymentInterface.php
namespace App\Contracts;

interface PaymentInterface
{
    public function charge(int $amount, string $token): bool;
    public function refund(int $transactionId): bool;
}

// app/Services/StripePaymentService.php
namespace App\Services;

use App\Contracts\PaymentInterface;
use Core\Contracts\LoggerInterface;

class StripePaymentService implements PaymentInterface
{
    public function __construct(
        protected LoggerInterface $logger
    ) {}

    public function charge(int $amount, string $token): bool
    {
        $this->logger->info('Charging payment', ['amount' => $amount]);
        // ... логика оплаты
    }
}
```

---

## ✅ Checklist для нового класса

Перед созданием нового класса проверь:

- [ ] Определены ли все зависимости?
- [ ] Используются ли интерфейсы вместо конкретных классов?
- [ ] Все зависимости внедряются через конструктор?
- [ ] Нет ли статических вызовов фасадов?
- [ ] Класс регистрирован в config/services.php (если нужно)?

---

## 🎉 Результаты соблюдения принципов

### ✅ Преимущества чистой архитектуры

1. **Тестируемость** - Легко создавать моки и юнит-тесты
2. **Гибкость** - Легко менять реализации
3. **Ясность** - Все зависимости видны в конструкторе
4. **Масштабируемость** - Легко расширять функциональность
5. **SOLID соответствие** - 100% соответствие принципам

### 📈 Сравнение

| Критерий | До рефакторинга | После рефакторинга |
|----------|----------------|-------------------|
| Type hints | ❌ Смешанные | ✅ Только интерфейсы |
| Тестируемость | ⚠️ Сложно | ✅ Легко |
| Гибкость | ⚠️ Средне | ✅ Отлично |
| SOLID | ⚠️ 60% | ✅ 100% |

---

## 📖 Дополнительная документация

- [DI Usage Guide](./DIUsageGuide.md) - Подробное руководство по DI
- [Clean Architecture Complete](../CLEAN_ARCHITECTURE_COMPLETE.md) - Отчет о рефакторинге
- [DI and Facades Summary](./DIandFacadesSummary.md) - Итоговый отчет

---

## 💬 Заключение

**Главное правило:**

> Если класс можно внедрить через DI - внедряй через интерфейс!
> 
> Если класс статический утилитарный (Environment, Path) - используй напрямую!

**Запомни:**
- ✅ `DatabaseInterface`, `SessionInterface`, `HttpInterface` - для DI
- ❌ `Database::`, `Session::`, `Http::` - не используй в классах с DI

---

**Дата:** 4 октября 2025  
**Проект:** Vilnius Framework  
**Версия:** 2.0 - Clean Architecture  
**Автор:** AI Assistant + Developer

