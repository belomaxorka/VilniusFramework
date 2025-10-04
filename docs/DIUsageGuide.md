# Руководство по использованию DI в вашем фреймворке

## Быстрая справка

### ✅ Правильно: Использование DI в контроллерах

```php
<?php

namespace App\Controllers;

use Core\Contracts\DatabaseInterface;
use Core\Contracts\SessionInterface;
use Core\Contracts\LoggerInterface;
use Core\Contracts\ConfigInterface;
use Core\Request;
use Core\Response;

class UserController extends Controller
{
    public function __construct(
        Request $request,
        Response $response,
        protected DatabaseInterface $db,
        protected SessionInterface $session,
        protected LoggerInterface $logger,
        protected ConfigInterface $config
    ) {
        parent::__construct($request, $response);
    }

    public function profile(): Response
    {
        // Используем внедренные зависимости
        $userId = $this->session->get('user_id');
        $user = $this->db->table('users')->find($userId);
        $appName = $this->config->get('app.name');
        
        $this->logger->info('User viewed profile', [
            'user_id' => $userId
        ]);

        return $this->view('profile', compact('user', 'appName'));
    }
}
```

### ✅ Правильно: Использование фасадов для простого кода

```php
<?php

use Core\Config;
use Core\Logger;
use Core\Session;
use Core\Database;

// Фасады работают как раньше!
$config = Config::get('app.name');
Logger::info('Something happened');
Session::set('key', 'value');
$users = Database::table('users')->get();
```

### ❌ Неправильно: Type hint на фасад вместо интерфейса

```php
<?php

use Core\Database;  // ❌ Это фасад!

class UserController 
{
    public function __construct(
        protected Database $db  // ❌ Неправильно!
    ) {}
}
```

**Правильно:**

```php
<?php

use Core\Contracts\DatabaseInterface;  // ✅ Это интерфейс!

class UserController 
{
    public function __construct(
        protected DatabaseInterface $db  // ✅ Правильно!
    ) {}
}
```

## Таблица соответствия: Фасад → Интерфейс

| Фасад (для use) | Интерфейс (для DI) | Описание |
|-----------------|-------------------|----------|
| `Core\Http` | `Core\Contracts\HttpInterface` | HTTP запросы |
| `Core\Config` | `Core\Contracts\ConfigInterface` | Конфигурация |
| `Core\Logger` | `Core\Contracts\LoggerInterface` | Логирование |
| `Core\Session` | `Core\Contracts\SessionInterface` | Сессии |
| `Core\Database` | `Core\Contracts\DatabaseInterface` | База данных |

## Когда использовать что?

### Используйте DI (через интерфейсы) когда:

✅ Пишете контроллеры
✅ Пишете сервисы/классы с бизнес-логикой
✅ Нужна тестируемость (моки)
✅ Класс имеет состояние/зависимости

**Пример:**
```php
class OrderService
{
    public function __construct(
        private DatabaseInterface $db,
        private LoggerInterface $logger,
        private SessionInterface $session
    ) {}
    
    public function createOrder(array $data): Order
    {
        $userId = $this->session->get('user_id');
        
        $this->logger->info('Creating order', ['user_id' => $userId]);
        
        return $this->db->transaction(function() use ($data, $userId) {
            // Логика создания заказа
        });
    }
}
```

### Используйте фасады когда:

✅ Пишете простые вспомогательные функции
✅ Нужен быстрый доступ без DI
✅ Код не требует тестирования с моками
✅ Миграция старого кода

**Пример:**
```php
// helpers.php
function getCurrentUser(): ?array
{
    $userId = Session::get('user_id');
    if (!$userId) {
        return null;
    }
    
    return Database::table('users')
        ->where('id', $userId)
        ->first();
}

function logError(string $message): void
{
    Logger::error($message);
}
```

## Примеры использования

### Пример 1: Контроллер с DI

```php
<?php

namespace App\Controllers;

use Core\Contracts\DatabaseInterface;
use Core\Contracts\LoggerInterface;
use Core\Request;
use Core\Response;

class PostController extends Controller
{
    public function __construct(
        Request $request,
        Response $response,
        private DatabaseInterface $db,
        private LoggerInterface $logger
    ) {
        parent::__construct($request, $response);
    }

    public function index(): Response
    {
        $this->logger->info('Fetching all posts');
        
        $posts = $this->db->table('posts')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->view('posts/index', compact('posts'));
    }

    public function store(): Response
    {
        $data = $this->request->all();
        
        $postId = $this->db->table('posts')->insertGetId($data);
        
        $this->logger->info('Post created', ['id' => $postId]);

        return $this->redirect('/posts');
    }
}
```

### Пример 2: Сервисный класс с DI

```php
<?php

namespace App\Services;

use Core\Contracts\DatabaseInterface;
use Core\Contracts\LoggerInterface;
use Core\Contracts\ConfigInterface;

class PaymentService
{
    public function __construct(
        private DatabaseInterface $db,
        private LoggerInterface $logger,
        private ConfigInterface $config
    ) {}

    public function processPayment(int $orderId, float $amount): bool
    {
        $apiKey = $this->config->get('payment.api_key');
        
        $this->logger->info('Processing payment', [
            'order_id' => $orderId,
            'amount' => $amount
        ]);

        return $this->db->transaction(function() use ($orderId, $amount) {
            // Логика обработки платежа
            
            $this->db->table('orders')
                ->where('id', $orderId)
                ->update(['status' => 'paid']);
            
            $this->db->table('transactions')->insert([
                'order_id' => $orderId,
                'amount' => $amount,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            return true;
        });
    }
}
```

### Пример 3: Использование сервиса в контроллере

```php
<?php

namespace App\Controllers;

use App\Services\PaymentService;
use Core\Request;
use Core\Response;

class PaymentController extends Controller
{
    public function __construct(
        Request $request,
        Response $response,
        private PaymentService $paymentService  // Автоматически резолвится!
    ) {
        parent::__construct($request, $response);
    }

    public function process(): Response
    {
        $orderId = $this->request->input('order_id');
        $amount = $this->request->input('amount');

        $success = $this->paymentService->processPayment($orderId, $amount);

        if ($success) {
            return $this->json(['message' => 'Payment processed']);
        }

        return $this->json(['error' => 'Payment failed'], 400);
    }
}
```

## Регистрация собственных сервисов

Добавьте свои сервисы в `config/services.php`:

```php
return [
    'singletons' => [
        // Ваши сервисы
        \App\Contracts\PaymentInterface::class => function ($container) {
            $config = $container->make(\Core\Contracts\ConfigInterface::class);
            $logger = $container->make(\Core\Contracts\LoggerInterface::class);
            
            return new \App\Services\StripePaymentService($config, $logger);
        },
        
        \App\Services\EmailNotificationService::class => function ($container) {
            $logger = $container->make(\Core\Contracts\LoggerInterface::class);
            return new \App\Services\EmailNotificationService($logger);
        },
    ],
    
    'bindings' => [
        // Обычные привязки (создаются каждый раз)
    ],
    
    'aliases' => [
        'payment' => \App\Contracts\PaymentInterface::class,
    ],
];
```

## Тестирование с моками

```php
<?php

use PHPUnit\Framework\TestCase;
use Core\Contracts\DatabaseInterface;
use Core\Contracts\LoggerInterface;
use App\Services\OrderService;

class OrderServiceTest extends TestCase
{
    public function test_create_order()
    {
        // Создаем моки
        $dbMock = $this->createMock(DatabaseInterface::class);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $sessionMock = $this->createMock(SessionInterface::class);

        // Настраиваем поведение моков
        $sessionMock->method('get')->willReturn(123);
        
        $dbMock->expects($this->once())
            ->method('transaction')
            ->willReturn(true);
            
        $loggerMock->expects($this->once())
            ->method('info');

        // Создаем сервис с моками
        $service = new OrderService($dbMock, $loggerMock, $sessionMock);

        // Тестируем
        $result = $service->createOrder(['item_id' => 1]);
        
        $this->assertTrue($result);
    }
}
```

## Часто задаваемые вопросы

### Q: Можно ли использовать фасады в контроллерах?

**A:** Да, можно! Но рекомендуется использовать DI для лучшей тестируемости:

```php
// ✅ Хорошо - DI
public function __construct(private LoggerInterface $logger) {}

public function index() {
    $this->logger->info('Action');
}

// ✅ Тоже нормально - фасад
public function index() {
    Logger::info('Action');
}
```

### Q: Что если мне нужен Config в хелпере?

**A:** Используйте фасад:

```php
// helpers.php
function getAppName(): string
{
    return Config::get('app.name', 'My App');
}
```

### Q: Как внедрить зависимость в middleware?

**A:** Через конструктор, контейнер автоматически резолвит:

```php
class AuthMiddleware
{
    public function __construct(
        private SessionInterface $session,
        private DatabaseInterface $db
    ) {}
    
    public function handle(): bool
    {
        $userId = $this->session->get('user_id');
        // ...
    }
}
```

### Q: Нужно ли менять весь старый код?

**A:** Нет! Фасады обеспечивают 100% обратную совместимость. Меняйте постепенно:

1. Новый код пишите с DI
2. Старый код работает как раньше (через фасады)
3. Рефакторите старый код по мере необходимости

## Чеклист миграции существующего контроллера

- [ ] Заменить `use Core\Database` на `use Core\Contracts\DatabaseInterface`
- [ ] Заменить `use Core\Session` на `use Core\Contracts\SessionInterface`
- [ ] Заменить `use Core\Logger` на `use Core\Contracts\LoggerInterface`
- [ ] Заменить `use Core\Config` на `use Core\Contracts\ConfigInterface`
- [ ] Добавить зависимости в конструктор
- [ ] Заменить статические вызовы на вызовы через `$this->`
- [ ] Убедиться что тайп-хинты используют интерфейсы, а не фасады

**До:**
```php
use Core\Database;
use Core\Logger;

class MyController {
    public function index() {
        Logger::info('Test');
        $users = Database::table('users')->get();
    }
}
```

**После:**
```php
use Core\Contracts\DatabaseInterface;
use Core\Contracts\LoggerInterface;

class MyController {
    public function __construct(
        private DatabaseInterface $db,
        private LoggerInterface $logger
    ) {}
    
    public function index() {
        $this->logger->info('Test');
        $users = $this->db->table('users')->get();
    }
}
```

## Заключение

✅ **Для новых контроллеров и сервисов** - используйте DI через интерфейсы
✅ **Для простого кода и хелперов** - используйте фасады
✅ **Старый код** - продолжает работать через фасады
✅ **Type hints** - всегда используйте интерфейсы, а не фасады!

**Главное правило:** 
> При внедрении зависимостей всегда используйте интерфейсы (`DatabaseInterface`), 
> а не фасады (`Database`)!

