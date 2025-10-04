# Анализ архитектуры DI контейнера и рекомендации по рефакторингу

## Текущее состояние

### Проблемы текущей архитектуры

#### 1. Смешение статических и instance-based подходов

Многие классы одновременно используют **статические методы** и регистрируются в **DI контейнере**. Это создает путаницу и противоречия в архитектуре.

**Примеры:**

##### `Database`
```php
// Используется статически
Database::init();
Database::table('users')->where('id', 1)->first();

// Но также регистрируется в контейнере
'singletons' => [
    \Core\Database::class => \Core\Database::class,
]
```

**Проблема:** Класс имеет `static $instance`, но никогда не создается через контейнер как instance.

##### `TemplateEngine`
```php
// Имеет Singleton паттерн
public static function getInstance(?string $templateDir = null, ?string $cacheDir = null): TemplateEngine

// Но также регистрируется в контейнере
\Core\TemplateEngine::class => \Core\TemplateEngine::class,
```

**Проблема:** Класс может быть создан двумя способами: через `getInstance()` или через контейнер.

---

#### 2. Жесткие зависимости через статические вызовы

Классы используют статические вызовы других классов напрямую, что создает **жесткие зависимости** и делает код **нетестируемым**.

**Примеры:**

##### `Router`
```php
class Router {
    protected function handleNotFound(string $method, string $uri): void {
        echo ErrorRenderer::render(404, 'Not Found'); // Статический вызов
    }
    
    protected function getGroupDomain(): ?string {
        $currentDomain = Http::getHost(); // Статический вызов
    }
}
```

##### `TemplateEngine`
```php
class TemplateEngine {
    private function logUndefinedVariable(...) {
        Logger::warning($logMessage); // Статический вызов
    }
    
    public function __construct(?string $templateDir = null, ?string $cacheDir = null) {
        $this->templateDir = $templateDir ?? RESOURCES_DIR . '/views'; // Глобальная константа
    }
}
```

##### `Session`
```php
class Session {
    public static function start(array $options = []): bool {
        if (Http::isSecure()) { // Статический вызов
            $defaultOptions['cookie_secure'] = true;
        }
    }
}
```

##### `Database`
```php
final class Database {
    public static function init(): DatabaseManager {
        if (self::$instance === null) {
            $config = Config::get('database'); // Статический вызов
        }
    }
}
```

**Проблемы:**
- Невозможно подменить зависимости в тестах
- Нарушение Dependency Inversion Principle (зависимость от конкретных реализаций)
- Сложно расширять и модифицировать поведение

---

#### 3. Классы не подходят для DI

Многие классы используют **только статические методы** и не предназначены для создания instance через контейнер:

- `Config` - полностью статический
- `Logger` - полностью статический
- `Session` - полностью статический  
- `Cookie` - полностью статический (обертка над `Http`)
- `Http` - полностью статический

**Проблема:** Эти классы зарегистрированы в алиасах контейнера, но никогда не используются через DI:

```php
'aliases' => [
    'config' => \Core\Config::class,
    'logger' => \Core\Logger::class,
    'session' => \Core\Session::class,
    'cookie' => \Core\Cookie::class,
    'http' => \Core\Http::class,
]
```

Попытка получить их из контейнера создаст instance, но все методы статические - это бессмысленно.

---

#### 4. Отсутствие интерфейсов

Классы не имеют интерфейсов, что затрудняет:
- Подмену реализаций
- Тестирование через моки
- Создание альтернативных реализаций

---

## Рекомендации по рефакторингу

### Вариант 1: Полный переход на DI (рекомендуется)

Преобразовать все сервисы в instance-based классы с внедрением зависимостей через конструктор.

#### Преимущества:
- ✅ Полностью тестируемый код
- ✅ Гибкая архитектура
- ✅ Соответствие SOLID принципам
- ✅ Простая подмена реализаций

#### Недостатки:
- ⚠️ Требует значительного рефакторинга
- ⚠️ Нужно обновить весь существующий код

#### Пример рефакторинга

##### До (текущий подход):
```php
class Session {
    public static function start(array $options = []): bool {
        if (Http::isSecure()) {
            $defaultOptions['cookie_secure'] = true;
        }
        // ...
    }
    
    public static function get(string $key, mixed $default = null): mixed {
        return $_SESSION[$key] ?? $default;
    }
}

// Использование
Session::start();
$user = Session::get('user');
```

##### После (DI подход):
```php
interface HttpInterface {
    public function isSecure(): bool;
    public function getHost(): string;
}

interface SessionInterface {
    public function start(array $options = []): bool;
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value): void;
}

class SessionManager implements SessionInterface {
    public function __construct(
        private HttpInterface $http
    ) {}
    
    public function start(array $options = []): bool {
        $defaultOptions = [
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
        ];
        
        if ($this->http->isSecure()) {
            $defaultOptions['cookie_secure'] = true;
        }
        
        return session_start(array_merge($defaultOptions, $options));
    }
    
    public function get(string $key, mixed $default = null): mixed {
        return $_SESSION[$key] ?? $default;
    }
    
    public function set(string $key, mixed $value): void {
        $_SESSION[$key] = $value;
    }
}

// Регистрация в контейнере
'singletons' => [
    HttpInterface::class => HttpService::class,
    SessionInterface::class => SessionManager::class,
]

// Использование через DI
class UserController {
    public function __construct(
        private SessionInterface $session
    ) {}
    
    public function profile() {
        $user = $this->session->get('user');
    }
}
```

---

### Вариант 2: Гибридный подход (минимальный рефакторинг)

Оставить статические классы для утилит, но сделать основные сервисы instance-based.

#### Разделение на категории:

##### А. Утилитные классы (оставить статическими)
Классы, которые **не имеют состояния** и предоставляют только **вспомогательные функции**:

- `Http` - утилиты для работы с HTTP
- `Cookie` - обертка над setcookie()
- `Path` - работа с путями

**НЕ регистрировать в контейнере!**

##### Б. Сервисы (сделать instance-based)
Классы, которые **имеют состояние** или **зависимости**:

- `Database` → `DatabaseManager` (уже есть!)
- `Session` → `SessionManager`
- `Config` → `ConfigRepository`
- `Logger` → `LoggerService`
- `TemplateEngine` (уже instance-based)
- `Router` (уже instance-based)
- `Emailer` (уже instance-based)

#### Пример рефакторинга утилитного класса

##### До:
```php
class Session {
    public static function start(): bool {
        if (Http::isSecure()) { // Жесткая зависимость
            // ...
        }
    }
}
```

##### После:
```php
class SessionManager {
    public function __construct(
        private ?HttpInterface $http = null
    ) {
        // Для обратной совместимости можно использовать HttpService по умолчанию
        $this->http ??= new HttpService();
    }
    
    public function start(array $options = []): bool {
        if ($this->http->isSecure()) {
            // ...
        }
    }
}

// Добавить статический фасад для удобства (опционально)
class Session {
    private static ?SessionManager $instance = null;
    
    public static function manager(): SessionManager {
        if (self::$instance === null) {
            $container = Container::getInstance();
            self::$instance = $container->make(SessionManager::class);
        }
        return self::$instance;
    }
    
    // Фасад методы для обратной совместимости
    public static function start(array $options = []): bool {
        return self::manager()->start($options);
    }
    
    public static function get(string $key, mixed $default = null): mixed {
        return self::manager()->get($key, $default);
    }
}
```

---

### Вариант 3: Создание интерфейсов + фасадов

Создать интерфейсы для всех сервисов и статические фасады для удобства.

#### Структура:

```
Contracts/
├── ConfigInterface.php
├── LoggerInterface.php
├── SessionInterface.php
├── HttpInterface.php
└── ...

Services/ (instance-based реализации)
├── ConfigRepository.php (implements ConfigInterface)
├── LoggerService.php (implements LoggerInterface)
├── SessionManager.php (implements SessionInterface)
├── HttpService.php (implements HttpInterface)
└── ...

Facades/ (статические обертки для удобства)
├── Config.php
├── Logger.php
├── Session.php
├── Http.php
└── ...
```

#### Пример:

```php
// Contracts/SessionInterface.php
interface SessionInterface {
    public function start(array $options = []): bool;
    public function get(string $key, mixed $default = null): mixed;
}

// Services/SessionManager.php
class SessionManager implements SessionInterface {
    public function __construct(
        private HttpInterface $http
    ) {}
    
    public function start(array $options = []): bool {
        // Реализация с DI
    }
}

// Facades/Session.php (статический фасад)
class Session {
    protected static function getFacadeAccessor(): string {
        return SessionInterface::class;
    }
    
    protected static function resolve(): SessionInterface {
        return Container::getInstance()->make(self::getFacadeAccessor());
    }
    
    public static function __callStatic(string $method, array $args): mixed {
        return self::resolve()->$method(...$args);
    }
}

// Использование остается прежним
Session::start();
$user = Session::get('user');

// Но внутри используется DI!
```

---

## Конкретные рекомендации по классам

### 1. `Database`

**Проблема:** Смешение паттерна Singleton и DI, статические методы.

**Решение:**
```php
// Убрать статику, использовать только DatabaseManager
interface DatabaseInterface {
    public function table(string $table): QueryBuilder;
    public function select(string $query, array $bindings = []): array;
}

// services.php
'singletons' => [
    DatabaseInterface::class => function ($container) {
        $config = $container->make(ConfigInterface::class)->get('database');
        return new DatabaseManager($config);
    },
    'db' => DatabaseInterface::class, // Alias
]

// Использование
class UserRepository {
    public function __construct(
        private DatabaseInterface $db
    ) {}
    
    public function findById(int $id) {
        return $this->db->table('users')->where('id', $id)->first();
    }
}
```

---

### 2. `Config`

**Проблема:** Полностью статический класс.

**Решение:**
```php
interface ConfigInterface {
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value): void;
    public function has(string $key): bool;
}

class ConfigRepository implements ConfigInterface {
    protected array $items = [];
    
    public function __construct(string $configPath) {
        $this->load($configPath);
    }
    
    public function get(string $key, mixed $default = null): mixed {
        // Реализация без static
    }
}

// services.php
'singletons' => [
    ConfigInterface::class => function () {
        $config = new ConfigRepository(CONFIG_DIR);
        $config->load(CONFIG_DIR);
        return $config;
    },
]
```

---

### 3. `Logger`

**Проблема:** Статический класс с состоянием.

**Решение:**
```php
interface LoggerInterface {
    public function log(string $level, string $message, array $context = []): void;
    public function debug(string $message, array $context = []): void;
    public function error(string $message, array $context = []): void;
}

class LoggerService implements LoggerInterface {
    protected array $handlers = [];
    
    public function __construct(ConfigInterface $config) {
        $this->initHandlers($config);
    }
    
    public function log(string $level, string $message, array $context = []): void {
        // Реализация без static
    }
}

// services.php
'singletons' => [
    LoggerInterface::class => function ($container) {
        $config = $container->make(ConfigInterface::class);
        return new LoggerService($config);
    },
]
```

---

### 4. `Session`

**Решение:** См. примеры выше в Вариант 2 и Вариант 3.

---

### 5. `Router`

**Проблема:** Использует статические вызовы внутри.

**Решение:**
```php
class Router {
    public function __construct(
        private Container $container,
        private ?HttpInterface $http = null,
        private ?ConfigInterface $config = null
    ) {
        $this->http ??= $container->make(HttpInterface::class);
        $this->config ??= $container->make(ConfigInterface::class);
    }
    
    protected function getGroupDomain(): ?string {
        $currentDomain = $this->http->getHost(); // Вместо Http::getHost()
        // ...
    }
}
```

---

### 6. `TemplateEngine`

**Проблема:** Использует статические вызовы `Logger::warning()`.

**Решение:**
```php
class TemplateEngine {
    public function __construct(
        ?string $templateDir = null,
        ?string $cacheDir = null,
        private ?LoggerInterface $logger = null,
        private ?ConfigInterface $config = null
    ) {
        // Инжектим зависимости
    }
    
    private function logUndefinedVariable(...) {
        $this->logger?->warning($logMessage); // Вместо Logger::warning()
    }
}

// services.php
'singletons' => [
    \Core\TemplateEngine::class => function ($container) {
        return new TemplateEngine(
            templateDir: RESOURCES_DIR . '/views',
            cacheDir: STORAGE_DIR . '/cache/templates',
            logger: $container->make(LoggerInterface::class),
            config: $container->make(ConfigInterface::class)
        );
    },
]
```

---

## План миграции (поэтапный)

### Фаза 1: Создание интерфейсов (неделя 1)
1. Создать директорию `core/Contracts/`
2. Создать интерфейсы для всех основных сервисов
3. Существующие классы временно реализуют интерфейсы

### Фаза 2: Рефакторинг Config и Logger (неделя 2)
1. `Config` → `ConfigRepository` (instance-based)
2. `Logger` → `LoggerService` (instance-based)
3. Обновить все места использования

### Фаза 3: Рефакторинг Session (неделя 3)
1. `Session` → `SessionManager`
2. Создать фасад для обратной совместимости
3. Постепенная миграция кода

### Фаза 4: Рефакторинг Database (неделя 4)
1. Убрать класс-обертку `Database`
2. Использовать `DatabaseManager` напрямую
3. Обновить регистрацию в контейнере

### Фаза 5: Внедрение зависимостей в существующие сервисы (недели 5-6)
1. Router
2. TemplateEngine
3. Emailer
4. Другие сервисы

### Фаза 6: Тестирование и документация (неделя 7)
1. Написать unit-тесты с моками
2. Обновить документацию
3. Примеры использования

---

## Выводы

### Текущая архитектура:
- ❌ Смешение статического и instance-based подходов
- ❌ Жесткие зависимости через статические вызовы
- ❌ Сложность тестирования
- ❌ Нарушение SOLID принципов

### После рефакторинга:
- ✅ Четкое разделение утилит и сервисов
- ✅ Все зависимости внедряются через конструктор
- ✅ Легкое тестирование с моками
- ✅ Гибкая и расширяемая архитектура
- ✅ Соответствие SOLID принципам

### Рекомендуемый подход:
**Вариант 3** (интерфейсы + фасады) - обеспечивает:
- Чистую архитектуру с DI
- Обратную совместимость через фасады
- Простоту миграции
- Лучшее из обоих миров

