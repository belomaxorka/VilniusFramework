# Dependency Injection (DI)

## Overview

Vilnius Framework имеет встроенный **Dependency Injection Container** с автоматическим разрешением зависимостей.

---

## Основные концепции

### Container

Container - это сервис-локатор, который:
- ✅ Автоматически создает объекты
- ✅ Разрешает зависимости через constructor
- ✅ Управляет жизненным циклом объектов (singleton/transient)
- ✅ Позволяет тестировать код (легко мокать зависимости)

---

## Регистрация сервисов

### config/services.php

```php
return [
    // Singleton - один экземпляр на весь lifecycle
    'singletons' => [
        \Core\Database::class => \Core\Database::class,
        \Core\Cache\CacheManager::class => function ($container) {
            return new \Core\Cache\CacheManager(\Core\Config::get('cache'));
        },
    ],

    // Regular bindings - новый экземпляр каждый раз
    'bindings' => [
        \Core\Request::class => function ($container) {
            return \Core\Request::capture();
        },
        \Core\Response::class => \Core\Response::class,
        
        // Interface binding
        \App\Contracts\PaymentInterface::class => \App\Services\StripePayment::class,
    ],

    // Aliases - короткие имена
    'aliases' => [
        'db' => \Core\Database::class,
        'cache' => \Core\Cache\CacheManager::class,
    ],
];
```

---

## Использование в контроллерах

### Базовый пример

```php
<?php

namespace App\Controllers;

use Core\Request;
use Core\Response;

class UserController extends Controller
{
    public function __construct(
        Request $request,
        Response $response,
    ) {
        parent::__construct($request, $response);
    }
    
    public function index()
    {
        // Request и Response уже доступны через $this
        return $this->view('users.index');
    }
}
```

### С дополнительными зависимостями

```php
<?php

namespace App\Controllers;

use Core\Request;
use Core\Response;
use Core\Database;
use Core\Cache\CacheManager;
use Core\Logger;

class ProductController extends Controller
{
    public function __construct(
        Request $request,
        Response $response,
        protected Database $db,
        protected CacheManager $cache,
        protected Logger $logger,
    ) {
        parent::__construct($request, $response);
        
        // Теперь доступны:
        // $this->db
        // $this->cache
        // $this->logger
    }
    
    public function index()
    {
        // Использование DI зависимостей
        $products = $this->cache->remember('products', 3600, function() {
            return $this->db->table('products')->get();
        });
        
        $this->logger->info('Products loaded', ['count' => count($products)]);
        
        return $this->view('products.index', [
            'products' => $products
        ]);
    }
}
```

---

## Использование Interface Binding

### 1. Создайте интерфейс

```php
<?php

namespace App\Contracts;

interface PaymentInterface
{
    public function charge(float $amount): bool;
}
```

### 2. Создайте реализацию

```php
<?php

namespace App\Services;

use App\Contracts\PaymentInterface;

class StripePayment implements PaymentInterface
{
    public function charge(float $amount): bool
    {
        // Stripe API logic
        return true;
    }
}
```

### 3. Зарегистрируйте в config/services.php

```php
'bindings' => [
    \App\Contracts\PaymentInterface::class => \App\Services\StripePayment::class,
],
```

### 4. Используйте в контроллере

```php
<?php

namespace App\Controllers;

use App\Contracts\PaymentInterface;

class CheckoutController extends Controller
{
    public function __construct(
        Request $request,
        Response $response,
        protected PaymentInterface $payment,
    ) {
        parent::__construct($request, $response);
    }
    
    public function process()
    {
        $amount = $this->request->input('amount');
        
        if ($this->payment->charge($amount)) {
            return $this->success('Payment successful');
        }
        
        return $this->error('Payment failed');
    }
}
```

---

## Ручное использование Container

### Получить экземпляр

```php
use Core\Container;

// Получить singleton Container
$container = Container::getInstance();

// Создать объект через Container
$service = $container->make(SomeService::class);
```

### В обычном классе (не контроллере)

```php
<?php

namespace App\Services;

use Core\Container;
use Core\Database;

class UserService
{
    protected Database $db;
    
    public function __construct()
    {
        // Ручное разрешение зависимостей
        $container = Container::getInstance();
        $this->db = $container->make(Database::class);
    }
    
    public function getActiveUsers()
    {
        return $this->db->table('users')
            ->where('status', 'active')
            ->get();
    }
}
```

---

## Преимущества DI

### ✅ Тестируемость

```php
// Легко заменить реальную зависимость на mock
$mockDb = Mockery::mock(Database::class);
$controller = new UserController($request, $response, $mockDb);
```

### ✅ Явные зависимости

```php
// Сразу видно что нужно классу
public function __construct(
    Database $db,
    CacheManager $cache,
    Logger $logger
) { }
```

### ✅ Гибкость

```php
// Легко менять реализацию через config
'bindings' => [
    PaymentInterface::class => StripePayment::class, // или PayPalPayment
],
```

### ✅ Следование SOLID

- **S** - Single Responsibility
- **O** - Open/Closed
- **L** - Liskov Substitution
- **I** - Interface Segregation
- **D** - **Dependency Inversion** ✅

---

## Best Practices

### ✅ DO:

- Используйте type hints в конструкторе
- Инжектите зависимости, а не создавайте их вручную
- Используйте интерфейсы для абстракций
- Регистрируйте сервисы в config/services.php

### ❌ DON'T:

- Не используйте `new` для создания зависимостей внутри класса
- Не используйте статические вызовы где можно инжектить
- Не создавайте циклические зависимости (A зависит от B, B от A)

---

## Примеры из реальных проектов

### REST API Controller

```php
<?php

namespace App\Controllers\Api;

use App\Services\UserService;
use App\Validators\UserValidator;
use Core\Request;
use Core\Response;
use Core\Logger;

class UserApiController extends Controller
{
    public function __construct(
        Request $request,
        Response $response,
        protected UserService $userService,
        protected UserValidator $validator,
        protected Logger $logger,
    ) {
        parent::__construct($request, $response);
    }
    
    public function store()
    {
        // Валидация
        $validated = $this->validator->validate($this->request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
        ]);
        
        // Бизнес-логика в Service
        $user = $this->userService->createUser($validated);
        
        // Логирование
        $this->logger->info('User created', ['user_id' => $user->id]);
        
        // Response
        return $this->created($user, 'User created successfully');
    }
}
```

---

## Troubleshooting

### Проблема: "Class not found"

**Решение:** Убедитесь что класс зарегистрирован в `config/services.php`

### Проблема: "Too few arguments"

**Решение:** Container не может автоматически разрешить примитивные типы (string, int). Используйте closure:

```php
'bindings' => [
    MyService::class => function($container) {
        return new MyService('some-api-key');
    },
],
```

### Проблема: Циклические зависимости

**Решение:** Рефакторинг архитектуры. Создайте третий класс или используйте Events.

---

## Summary

✅ DI делает код чище, тестируемее и гибче  
✅ Container автоматически разрешает зависимости  
✅ Используйте type hints и интерфейсы  
✅ Регистрируйте сервисы в config/services.php  

🎯 **Результат:** Чистая архитектура, легкое тестирование, гибкая разработка!

