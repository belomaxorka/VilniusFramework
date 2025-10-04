# DI Architecture - Quick Reference

## 🔴 Основные проблемы

### 1. Классы со смешанной архитектурой

| Класс | Проблема | Статус |
|-------|----------|--------|
| `Database` | Статический singleton + регистрация в DI | ❌ Несовместимо |
| `TemplateEngine` | `getInstance()` + регистрация в DI | ❌ Дублирование |
| `Config` | Только статические методы | ❌ Не подходит для DI |
| `Logger` | Только статические методы | ❌ Не подходит для DI |
| `Session` | Только статические методы | ❌ Не подходит для DI |
| `Http` | Только статические методы | ❌ Не подходит для DI |
| `Cookie` | Только статические методы | ❌ Не подходит для DI |

### 2. Жесткие зависимости

```php
// ❌ Проблема: статические вызовы создают жесткие зависимости
class Router {
    protected function handleNotFound() {
        echo ErrorRenderer::render(404, 'Not Found'); // Жестко привязан к ErrorRenderer
    }
    
    protected function getGroupDomain() {
        $currentDomain = Http::getHost(); // Жестко привязан к Http
    }
}

class TemplateEngine {
    private function logUndefinedVariable() {
        Logger::warning($message); // Жестко привязан к Logger
    }
}

class Session {
    public static function start() {
        if (Http::isSecure()) { // Жестко привязан к Http
            // ...
        }
    }
}
```

**Последствия:**
- Невозможно подменить зависимости в тестах
- Нельзя использовать моки
- Нарушается принцип инверсии зависимостей (DIP)

### 3. Неверная регистрация в контейнере

```php
// config/services.php
'aliases' => [
    'config' => \Core\Config::class,    // ❌ Класс полностью статический
    'logger' => \Core\Logger::class,    // ❌ Класс полностью статический
    'session' => \Core\Session::class,  // ❌ Класс полностью статический
    'http' => \Core\Http::class,        // ❌ Класс полностью статический
]
```

**Проблема:** Эти классы никогда не создаются как instance, все методы статические.

## ✅ Решение

### Архитектура с правильным DI

```
┌─────────────────────────────────────────────────┐
│                   Contracts/                     │
│              (Интерфейсы)                        │
│  SessionInterface, HttpInterface, ConfigInterface│
└──────────────────┬──────────────────────────────┘
                   │ implements
                   ↓
┌─────────────────────────────────────────────────┐
│                  Services/                       │
│           (Instance-based классы)                │
│  SessionManager, HttpService, ConfigRepository   │
└──────────────────┬──────────────────────────────┘
                   │ используется через
                   ↓
┌─────────────────────────────────────────────────┐
│                  Facades/                        │
│           (Статические обертки)                  │
│     Session, Http, Config (для совместимости)    │
└─────────────────────────────────────────────────┘
```

### Правильная структура класса для DI

```php
// ✅ ПРАВИЛЬНО: Instance-based с внедрением зависимостей

// 1. Интерфейс
interface SessionInterface {
    public function start(array $options = []): bool;
    public function get(string $key, mixed $default = null): mixed;
}

// 2. Реализация с DI
class SessionManager implements SessionInterface {
    public function __construct(
        private HttpInterface $http  // ✅ Зависимости через конструктор
    ) {}
    
    public function start(array $options = []): bool {
        if ($this->http->isSecure()) {  // ✅ Используем интерфейс
            // ...
        }
    }
}

// 3. Фасад для обратной совместимости
class Session {
    private static ?SessionInterface $instance = null;
    
    protected static function getManager(): SessionInterface {
        if (self::$instance === null) {
            self::$instance = Container::getInstance()->make(SessionInterface::class);
        }
        return self::$instance;
    }
    
    public static function start(array $options = []): bool {
        return self::getManager()->start($options);
    }
}

// 4. Регистрация в контейнере
'singletons' => [
    SessionInterface::class => SessionManager::class,
]
```

## 📋 Чеклист для проверки класса

### Должен ли класс использоваться через DI?

#### ✅ ДА, если класс:
- [ ] Имеет **состояние** (instance переменные)
- [ ] Имеет **зависимости** от других классов
- [ ] Требует **конфигурации** при создании
- [ ] Нужно **тестировать** с моками
- [ ] Может иметь **разные реализации**

**Примеры:** `SessionManager`, `DatabaseManager`, `LoggerService`, `ConfigRepository`, `TemplateEngine`

#### ❌ НЕТ, если класс:
- [ ] Только **утилитные функции** без состояния
- [ ] Простые **статические хелперы**
- [ ] Работает только с **глобальными переменными** ($_SERVER, $_POST)

**Примеры:** `Str::random()`, `Arr::flatten()`, возможно `Path::normalize()`

### Правильная реализация класса для DI

```php
// ❌ ПЛОХО
class MyService {
    public static function doSomething() {
        $config = Config::get('app.name');      // Жесткая зависимость
        Logger::info('Doing something');         // Жесткая зависимость
        // ...
    }
}

// ✅ ХОРОШО
class MyService {
    public function __construct(
        private ConfigInterface $config,   // ✅ Внедрение через конструктор
        private LoggerInterface $logger    // ✅ Зависимости явно указаны
    ) {}
    
    public function doSomething() {
        $name = $this->config->get('app.name');
        $this->logger->info('Doing something');
        // ...
    }
}
```

## 🎯 Приоритеты рефакторинга

### Высокий приоритет (критически важно)

1. **Config** → `ConfigRepository`
   - Используется везде
   - Блокирует тестирование других классов

2. **Logger** → `LoggerService`  
   - Используется во многих местах
   - Важен для отладки

3. **Session** → `SessionManager`
   - Критичен для безопасности
   - Часто используется в контроллерах

### Средний приоритет

4. **Database** → использовать `DatabaseManager` напрямую
   - Убрать статическую обертку
   - Уже есть хорошая реализация

5. **Http** → `HttpService`
   - Используется в Session, Router
   - Базовый сервис

### Низкий приоритет

6. **Cookie** - можно оставить как утилитный класс
7. **Path** - можно оставить как утилитный класс

## 🔧 Примеры рефакторинга

### Контроллер

```php
// ❌ БЫЛО
class UserController {
    public function profile() {
        $userId = Session::get('user_id');
        $user = Database::table('users')->find($userId);
        $appName = Config::get('app.name');
        Logger::info('User visited profile');
        
        return view('profile', ['user' => $user]);
    }
}

// ✅ СТАЛО
class UserController {
    public function __construct(
        private SessionInterface $session,
        private DatabaseInterface $db,
        private ConfigInterface $config,
        private LoggerInterface $logger,
        private TemplateEngine $view
    ) {}
    
    public function profile() {
        $userId = $this->session->get('user_id');
        $user = $this->db->table('users')->find($userId);
        $appName = $this->config->get('app.name');
        $this->logger->info('User visited profile');
        
        return $this->view->render('profile', ['user' => $user]);
    }
}
```

### Сервис

```php
// ❌ БЫЛО
class EmailService {
    public function sendWelcome(User $user) {
        $fromEmail = Config::get('mail.from');
        $appName = Config::get('app.name');
        
        // Отправка письма...
        
        Logger::info("Welcome email sent to {$user->email}");
    }
}

// ✅ СТАЛО
class EmailService {
    public function __construct(
        private ConfigInterface $config,
        private LoggerInterface $logger,
        private MailerInterface $mailer
    ) {}
    
    public function sendWelcome(User $user) {
        $fromEmail = $this->config->get('mail.from');
        $appName = $this->config->get('app.name');
        
        $this->mailer->send(/* ... */);
        
        $this->logger->info("Welcome email sent to {$user->email}");
    }
}
```

## 🧪 Тестирование

### До рефакторинга (невозможно тестировать)

```php
// ❌ Невозможно подменить Config, Logger
class UserService {
    public function create(array $data) {
        $maxUsers = Config::get('limits.max_users');
        Logger::info('Creating user');
        // ...
    }
}

// ❌ Нельзя написать нормальный unit-тест
public function test_create_user() {
    // Как подменить Config и Logger? Никак!
    $service = new UserService();
    $service->create(['name' => 'John']);
}
```

### После рефакторинга (легко тестировать)

```php
// ✅ Можно подменить все зависимости
class UserService {
    public function __construct(
        private ConfigInterface $config,
        private LoggerInterface $logger
    ) {}
    
    public function create(array $data) {
        $maxUsers = $this->config->get('limits.max_users');
        $this->logger->info('Creating user');
        // ...
    }
}

// ✅ Легкий unit-тест с моками
public function test_create_user() {
    $configMock = $this->createMock(ConfigInterface::class);
    $loggerMock = $this->createMock(LoggerInterface::class);
    
    $configMock->expects($this->once())
        ->method('get')
        ->with('limits.max_users')
        ->willReturn(100);
    
    $loggerMock->expects($this->once())
        ->method('info')
        ->with('Creating user');
    
    $service = new UserService($configMock, $loggerMock);
    $service->create(['name' => 'John']);
}
```

## 📚 Полезные ссылки

- [DIAnalysis.md](DIAnalysis.md) - Детальный анализ проблем
- [DIRefactoringExample.md](DIRefactoringExample.md) - Пошаговый пример рефакторинга Session
- [DependencyInjection.md](DependencyInjection.md) - Документация по DI контейнеру

## 🎓 Основные принципы

1. **Зависимости через конструктор** - все зависимости передаются при создании
2. **Программирование через интерфейсы** - зависимости объявляются как интерфейсы
3. **Один способ создания** - либо DI, либо статика, не оба сразу
4. **Тестируемость** - любой класс должен легко тестироваться с моками
5. **Обратная совместимость** - используйте фасады для старого кода

## ❓ FAQ

**Q: Нужно ли рефакторить весь код сразу?**  
A: Нет! Используйте фасады для обратной совместимости и рефакторьте постепенно.

**Q: Что делать с утилитными классами типа `Str`, `Arr`?**  
A: Оставьте их статическими, они не имеют состояния и зависимостей.

**Q: Как использовать DI в middleware?**  
A: Middleware также могут получать зависимости через конструктор.

**Q: Нужно ли удалять статические фасады?**  
A: Нет, фасады можно оставить для удобства, но внутри они должны использовать DI.

**Q: Как быть с глобальными функциями (helpers)?**  
A: Оставьте их для простых утилит, но для сервисов лучше использовать DI.

