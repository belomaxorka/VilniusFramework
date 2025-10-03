# Dependency Injection - Примеры использования

## Зарегистрированные сервисы

Теперь в DI контейнере доступны **все ключевые сервисы** фреймворка!

---

## 📦 Список доступных сервисов

### Core Services
- ✅ `Router` - маршрутизация
- ✅ `Database` - база данных
- ✅ `TemplateEngine` - шаблонизатор
- ✅ `Session` - сессии
- ✅ `Logger` - логирование
- ✅ `CacheManager` - кэширование

### Email
- ✅ `Emailer` - отправка писем

### Configuration
- ✅ `Config` - конфигурация
- ✅ `Environment` - окружение
- ✅ `Env` - .env переменные

### Utilities
- ✅ `Cookie` - работа с cookies
- ✅ `Path` - работа с путями
- ✅ `Lang` - локализация
- ✅ `Http` - HTTP утилиты

### Debug & Profiling
- ✅ `DebugToolbar` - debug панель
- ✅ `Debug` - отладка
- ✅ `MemoryProfiler` - профилирование памяти
- ✅ `QueryDebugger` - отладка запросов

### Validation
- ✅ `RouteParameterValidator` - валидация параметров

---

## 🎯 Примеры использования

### Базовый контроллер с Database + Cache

```php
<?php

namespace App\Controllers;

use Core\Request;
use Core\Response;
use Core\Database;
use Core\Cache\CacheManager;

class ProductController extends Controller
{
    public function __construct(
        Request $request,
        Response $response,
        protected Database $db,
        protected CacheManager $cache,
    ) {
        parent::__construct($request, $response);
    }
    
    public function index()
    {
        // Кэширование на 1 час
        $products = $this->cache->remember('products.all', 3600, function() {
            return $this->db->table('products')
                ->where('active', true)
                ->orderBy('name')
                ->get();
        });
        
        return $this->view('products.index', [
            'products' => $products
        ]);
    }
}
```

---

### Контроллер с Email отправкой

```php
<?php

namespace App\Controllers;

use Core\Request;
use Core\Response;
use Core\Database;
use Core\Emailer;
use Core\Logger;

class OrderController extends Controller
{
    public function __construct(
        Request $request,
        Response $response,
        protected Database $db,
        protected Emailer $emailer,
        protected Logger $logger,
    ) {
        parent::__construct($request, $response);
    }
    
    public function create()
    {
        $data = $this->request->all();
        
        // Создаём заказ
        $orderId = $this->db->table('orders')->insert([
            'user_id' => $data['user_id'],
            'total' => $data['total'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        
        // Отправляем email
        $this->emailer
            ->to($data['email'])
            ->subject('Заказ #' . $orderId . ' создан')
            ->html('<p>Спасибо за заказ!</p>')
            ->send();
        
        // Логируем
        $this->logger->info('Order created', [
            'order_id' => $orderId,
            'user_id' => $data['user_id'],
        ]);
        
        return $this->created(['order_id' => $orderId]);
    }
}
```

---

### Многоязычный контроллер

```php
<?php

namespace App\Controllers;

use Core\Request;
use Core\Response;
use Core\Lang;
use Core\Session;

class LocalizationController extends Controller
{
    public function __construct(
        Request $request,
        Response $response,
        protected Lang $lang,
        protected Session $session,
    ) {
        parent::__construct($request, $response);
    }
    
    public function switchLanguage(string $locale)
    {
        // Меняем язык
        $this->lang->setLocale($locale);
        
        // Сохраняем в сессии
        $this->session->put('locale', $locale);
        
        return $this->redirect($this->request->header('Referer') ?? '/');
    }
    
    public function index()
    {
        // Используем переводы
        $greeting = $this->lang->get('messages.welcome');
        
        return $this->view('home', [
            'greeting' => $greeting
        ]);
    }
}
```

---

### Работа с Cookies

```php
<?php

namespace App\Controllers;

use Core\Request;
use Core\Response;
use Core\Cookie;

class PreferencesController extends Controller
{
    public function __construct(
        Request $request,
        Response $response,
        protected Cookie $cookie,
    ) {
        parent::__construct($request, $response);
    }
    
    public function saveTheme()
    {
        $theme = $this->request->input('theme', 'light');
        
        // Сохраняем на 30 дней
        $this->cookie->set('theme', $theme, 60 * 60 * 24 * 30);
        
        return $this->success('Theme saved');
    }
    
    public function getTheme()
    {
        $theme = $this->cookie->get('theme', 'light');
        
        return $this->json(['theme' => $theme]);
    }
}
```

---

### Полноценный пример (все вместе!)

```php
<?php

namespace App\Controllers;

use Core\Request;
use Core\Response;
use Core\Database;
use Core\Cache\CacheManager;
use Core\Emailer;
use Core\Logger;
use Core\Session;
use Core\Cookie;
use Core\Lang;

class AdvancedController extends Controller
{
    public function __construct(
        Request $request,
        Response $response,
        protected Database $db,
        protected CacheManager $cache,
        protected Emailer $emailer,
        protected Logger $logger,
        protected Session $session,
        protected Cookie $cookie,
        protected Lang $lang,
    ) {
        parent::__construct($request, $response);
    }
    
    public function processOrder()
    {
        // 1. Получаем данные
        $userId = $this->session->get('user_id');
        $cartId = $this->cookie->get('cart_id');
        
        // 2. Проверяем кэш
        $cart = $this->cache->get("cart.{$cartId}");
        
        if (!$cart) {
            // 3. Загружаем из БД
            $cart = $this->db->table('carts')
                ->where('id', $cartId)
                ->first();
                
            // 4. Кэшируем
            $this->cache->put("cart.{$cartId}", $cart, 600);
        }
        
        // 5. Создаём заказ
        $orderId = $this->db->table('orders')->insert([
            'user_id' => $userId,
            'cart_id' => $cartId,
            'total' => $cart['total'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        
        // 6. Отправляем email
        $user = $this->db->table('users')->find($userId);
        $emailSubject = $this->lang->get('emails.order.subject', ['id' => $orderId]);
        
        $this->emailer
            ->to($user['email'])
            ->subject($emailSubject)
            ->template('emails.order-created', [
                'order' => $orderId,
                'user' => $user,
            ])
            ->send();
        
        // 7. Логируем
        $this->logger->info('Order processed', [
            'order_id' => $orderId,
            'user_id' => $userId,
            'total' => $cart['total'],
        ]);
        
        // 8. Очищаем корзину
        $this->cache->forget("cart.{$cartId}");
        $this->cookie->delete('cart_id');
        
        // 9. Сохраняем в сессию
        $this->session->flash('success', 'Order created successfully!');
        
        return $this->created(['order_id' => $orderId]);
    }
}
```

---

## 🔥 Использование через Aliases

Вместо полных имен классов можно использовать короткие алиасы:

```php
use Core\Request;
use Core\Response;

class MyController extends Controller
{
    public function __construct(
        Request $request,
        Response $response,
    ) {
        parent::__construct($request, $response);
        
        // Получение через Container с alias
        $this->db = app('db');              // Database
        $this->cache = app('cache');        // CacheManager
        $this->logger = app('log');         // Logger
        $this->mailer = app('email');       // Emailer
        $this->lang = app('lang');          // Lang
    }
}
```

### Helper функция app()

```php
// В любом месте приложения можно использовать:

// Получить сервис
$db = app('db');
$cache = app('cache');

// Или с полным именем класса
$db = app(\Core\Database::class);
```

---

## 💡 Best Practices

### ✅ DO:

```php
// Инжектите зависимости в конструктор
public function __construct(
    Request $request,
    Response $response,
    protected Database $db,
) {
    parent::__construct($request, $response);
}
```

### ❌ DON'T:

```php
// НЕ создавайте вручную!
public function index()
{
    $db = Database::getInstance(); // ❌ BAD
    $db = new Database();           // ❌ BAD
}
```

---

## 🎯 Резюме

Теперь в DI доступны **все основные сервисы**:

| Категория | Сервисы | Alias |
|-----------|---------|-------|
| **Database** | Database | `db`, `database` |
| **Cache** | CacheManager | `cache` |
| **Email** | Emailer | `email`, `emailer`, `mailer` |
| **Logging** | Logger | `log`, `logger` |
| **Session** | Session | `session` |
| **Views** | TemplateEngine | `view`, `template` |
| **Localization** | Lang | `lang` |
| **Config** | Config, Environment | `config`, `env` |
| **Utilities** | Cookie, Path, Http | `cookie`, `path`, `http` |

✅ Все готово к использованию!  
✅ Просто инжектите в конструктор!  
✅ Чистая архитектура гарантирована!

🚀 **Happy coding!**

