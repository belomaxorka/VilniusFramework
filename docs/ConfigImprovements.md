# Рекомендации по улучшению класса Config

## Текущее состояние ✅

Ваш класс `Core\Config` уже имеет отличную архитектуру:
- ✅ Поддержка dot notation
- ✅ Кэширование конфигурации
- ✅ Макросы для lazy evaluation
- ✅ Блокировка для immutability
- ✅ Рекурсивная загрузка
- ✅ Environment-specific конфиги
- ✅ Отличное покрытие тестами (~95%)

---

## Приоритетные улучшения 🚀

### 1. Безопасность (High Priority)

#### 1.1 Защита от Path Traversal
**Проблема:** Возможна загрузка файлов вне предполагаемой директории.

**Решение:**
```php
protected static function validatePath(string $path, string $basePath): bool
{
    $realPath = realpath($path);
    $realBasePath = realpath($basePath);
    
    if ($realPath === false || $realBasePath === false) {
        return false;
    }
    
    return str_starts_with($realPath, $realBasePath);
}
```

**Применение:**
```php
public static function loadFile(string $filePath): void
{
    $realPath = realpath($filePath);
    
    if ($realPath === false) {
        throw new InvalidArgumentException("File not found: {$filePath}");
    }
    
    // Проверка на выход за пределы разрешенных директорий
    $allowedBasePath = self::$configBasePath ?? getcwd();
    if (!self::validatePath($realPath, $allowedBasePath)) {
        throw new SecurityException("Path traversal detected: {$filePath}");
    }
    
    // ... остальной код
}
```

#### 1.2 Защита от циклических ссылок в макросах
**Проблема:** Макрос может вызвать сам себя, создавая бесконечную рекурсию.

**Решение:**
```php
protected static array $resolvingMacros = [];

public static function resolve(string $key, mixed $default = null): mixed
{
    if (isset(self::$resolvingMacros[$key])) {
        throw new RuntimeException("Circular macro reference detected: {$key}");
    }
    
    $value = self::get($key, $default);
    
    if (is_callable($value) && self::isMacro($key)) {
        self::$resolvingMacros[$key] = true;
        try {
            $result = $value();
        } finally {
            unset(self::$resolvingMacros[$key]);
        }
        return $result;
    }
    
    return $value;
}
```

---

### 2. Удобство использования (High Priority)

#### 2.1 ArrayAccess Interface
**Преимущество:** Более естественный синтаксис доступа к конфигурации.

**Реализация:**
```php
class Config implements ArrayAccess, Countable
{
    public function offsetExists($offset): bool
    {
        return self::has($offset);
    }
    
    public function offsetGet($offset): mixed
    {
        return self::get($offset);
    }
    
    public function offsetSet($offset, $value): void
    {
        self::set($offset, $value);
    }
    
    public function offsetUnset($offset): void
    {
        self::forget($offset);
    }
    
    public function count(): int
    {
        return count(self::$items);
    }
}
```

**Использование:**
```php
// Вместо Config::get('database.host')
$host = Config::getInstance()['database.host'];

// Вместо Config::set('app.name', 'MyApp')
Config::getInstance()['app.name'] = 'MyApp';

// Проверка существования
if (isset(Config::getInstance()['cache.driver'])) {
    // ...
}
```

#### 2.2 Метод getRequired()
**Преимущество:** Явная обработка обязательных параметров.

**Реализация:**
```php
/**
 * Gets a required configuration value, throws if missing
 *
 * @param string $key The configuration key
 * @return mixed The configuration value
 * @throws RuntimeException If key doesn't exist
 */
public static function getRequired(string $key): mixed
{
    if (!self::has($key)) {
        throw new RuntimeException("Required configuration key missing: {$key}");
    }
    
    return self::get($key);
}
```

**Использование:**
```php
// Вместо проверок вручную:
$apiKey = Config::get('api.key');
if ($apiKey === null) {
    throw new RuntimeException('API key is required');
}

// Теперь:
$apiKey = Config::getRequired('api.key');
```

#### 2.3 Метод getMany()
**Преимущество:** Получение нескольких значений одновременно.

**Реализация:**
```php
/**
 * Gets multiple configuration values at once
 *
 * @param array $keys Array of configuration keys
 * @param mixed $default Default value for missing keys
 * @return array Array of values keyed by original keys
 */
public static function getMany(array $keys, mixed $default = null): array
{
    $result = [];
    foreach ($keys as $key) {
        $result[$key] = self::get($key, $default);
    }
    return $result;
}
```

**Использование:**
```php
// Получить несколько значений
$config = Config::getMany([
    'database.host',
    'database.port',
    'database.username',
]);
```

---

### 3. Расширенные возможности (Medium Priority)

#### 3.1 Поддержка JSON файлов
**Преимущество:** Универсальность, совместимость с внешними системами.

**Реализация:**
```php
public static function loadFile(string $filePath): void
{
    if (!file_exists($filePath)) {
        throw new InvalidArgumentException("File not found: {$filePath}");
    }
    
    $extension = pathinfo($filePath, PATHINFO_EXTENSION);
    $key = basename($filePath, '.' . $extension);
    
    $config = match($extension) {
        'php' => require $filePath,
        'json' => json_decode(file_get_contents($filePath), true),
        default => throw new RuntimeException("Unsupported file format: {$extension}")
    };
    
    if (!is_array($config)) {
        throw new RuntimeException("Configuration file must contain an array: {$filePath}");
    }
    
    // ... merge logic
}
```

#### 3.2 Мемоизация макросов
**Преимущество:** Повышение производительности для дорогих вычислений.

**Реализация:**
```php
protected static array $memoizedMacros = [];

/**
 * Registers a memoized macro (result cached after first execution)
 */
public static function memoizedMacro(string $key, callable $callback): void
{
    self::ensureNotLocked();
    
    $memoized = function() use ($key, $callback) {
        if (!isset(self::$memoizedMacros[$key])) {
            self::$memoizedMacros[$key] = $callback();
        }
        return self::$memoizedMacros[$key];
    };
    
    self::$macros[$key] = $memoized;
    self::set($key, $memoized);
}
```

**Использование:**
```php
// Обычный макро - выполняется каждый раз
Config::macro('timestamp', fn() => microtime(true));

// Мемоизированный макро - выполняется один раз
Config::memoizedMacro('app.timezone', function() {
    // Дорогая операция
    return detectTimezone(); 
});
```

#### 3.3 Валидация конфигурации
**Преимущество:** Раннее обнаружение ошибок конфигурации.

**Реализация:**
```php
protected static array $validators = [];

/**
 * Registers a validator for a configuration key
 */
public static function validator(string $key, callable $validator): void
{
    self::$validators[$key] = $validator;
}

/**
 * Validates all registered validators
 * @throws ValidationException
 */
public static function validate(): void
{
    $errors = [];
    
    foreach (self::$validators as $key => $validator) {
        try {
            $value = self::get($key);
            if (!$validator($value)) {
                $errors[] = "Validation failed for key: {$key}";
            }
        } catch (\Throwable $e) {
            $errors[] = "Validation error for {$key}: {$e->getMessage()}";
        }
    }
    
    if (!empty($errors)) {
        throw new ValidationException(implode(', ', $errors));
    }
}
```

**Использование:**
```php
// Регистрация валидаторов
Config::validator('database.port', fn($v) => is_int($v) && $v > 0 && $v < 65536);
Config::validator('app.debug', fn($v) => is_bool($v));
Config::validator('api.key', fn($v) => is_string($v) && strlen($v) > 10);

// Валидация перед использованием
Config::load(__DIR__ . '/config');
Config::validate(); // Бросит исключение если есть проблемы
```

#### 3.4 События/Хуки
**Преимущество:** Расширяемость без модификации основного класса.

**Реализация:**
```php
protected static array $listeners = [];

/**
 * Registers an event listener
 */
public static function on(string $event, callable $callback): void
{
    self::$listeners[$event][] = $callback;
}

/**
 * Fires an event
 */
protected static function fire(string $event, array $data = []): void
{
    if (isset(self::$listeners[$event])) {
        foreach (self::$listeners[$event] as $callback) {
            $callback($data);
        }
    }
}

// В методе set():
public static function set(string $key, mixed $value): void
{
    self::ensureNotLocked();
    
    $oldValue = self::get($key);
    
    // ... set logic
    
    self::fire('config.changed', [
        'key' => $key,
        'old' => $oldValue,
        'new' => $value,
    ]);
}
```

**Использование:**
```php
// Логирование изменений
Config::on('config.changed', function($data) {
    Log::info("Config changed: {$data['key']}");
});

// Инвалидация кэша при изменении
Config::on('config.changed', function($data) {
    if (str_starts_with($data['key'], 'cache.')) {
        Cache::flush();
    }
});
```

---

### 4. Производительность (Low Priority)

#### 4.1 Ленивая загрузка файлов
**Преимущество:** Загрузка только необходимых конфигов.

**Реализация:**
```php
protected static array $lazyFiles = [];

/**
 * Registers a file for lazy loading
 */
public static function registerLazy(string $key, string $filePath): void
{
    self::$lazyFiles[$key] = $filePath;
}

/**
 * Modified get() with lazy loading
 */
public static function get(string $key, mixed $default = null): mixed
{
    // Check if we need to lazy load
    $topLevelKey = explode('.', $key)[0];
    
    if (isset(self::$lazyFiles[$topLevelKey]) && !isset(self::$items[$topLevelKey])) {
        self::loadFile(self::$lazyFiles[$topLevelKey]);
        unset(self::$lazyFiles[$topLevelKey]);
    }
    
    // ... normal get logic
}
```

#### 4.2 Оптимизация операций с dot notation
**Проблема:** Многократное разбиение строк с точками.

**Решение:**
```php
protected static array $pathCache = [];

protected static function parsePath(string $key): array
{
    if (!isset(self::$pathCache[$key])) {
        self::$pathCache[$key] = explode('.', $key);
    }
    return self::$pathCache[$key];
}
```

---

### 5. Дополнительные функции (Nice to Have)

#### 5.1 Wildcard поддержка
**Использование:**
```php
// Получить все хосты всех соединений
$hosts = Config::get('database.connections.*.host');
// ['mysql' => 'localhost', 'postgres' => '192.168.1.1']
```

#### 5.2 Экспорт в разные форматы
```php
public static function export(string $format = 'array'): mixed
{
    return match($format) {
        'array' => self::all(),
        'json' => json_encode(self::all(), JSON_PRETTY_PRINT),
        'yaml' => yaml_emit(self::all()),
        'php' => var_export(self::all(), true),
        default => throw new InvalidArgumentException("Unsupported format: {$format}")
    };
}
```

#### 5.3 Создание изолированных инстансов
**Преимущество:** Множественные конфигурации в одном приложении.

```php
class Config
{
    private static ?self $instance = null;
    private array $items = [];
    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public static function createInstance(): self
    {
        return new self();
    }
    
    // Все методы работают с $this->items вместо self::$items
}
```

---

## Приоритизация изменений

### Фаза 1 (Критично) - 1-2 дня
1. ✅ Защита от path traversal
2. ✅ Валидация пустых ключей
3. ✅ Метод `getRequired()`
4. ✅ Дополнительные edge case тесты

### Фаза 2 (Важно) - 2-3 дня
1. ✅ ArrayAccess интерфейс
2. ✅ Мемоизация макросов
3. ✅ Поддержка JSON файлов
4. ✅ События/хуки

### Фаза 3 (Улучшения) - 3-5 дней
1. ⏳ Валидация конфигурации
2. ⏳ Ленивая загрузка
3. ⏳ Wildcard поддержка
4. ⏳ Экспорт в разные форматы

---

## Примеры использования улучшенного Config

```php
// 1. Удобный доступ через ArrayAccess
$config = Config::getInstance();
$dbHost = $config['database.host'] ?? 'localhost';

// 2. Обязательные параметры
$apiKey = Config::getRequired('api.secret_key');

// 3. Множественное получение
[$host, $port, $user] = array_values(Config::getMany([
    'database.host',
    'database.port',
    'database.username',
]));

// 4. Валидация
Config::validator('app.debug', fn($v) => is_bool($v));
Config::validator('database.port', fn($v) => $v > 0 && $v < 65536);
Config::load(__DIR__ . '/config');
Config::validate(); // Throws if invalid

// 5. События
Config::on('config.changed', function($data) {
    Logger::info("Config {$data['key']} changed");
});

// 6. Мемоизированные макросы (для дорогих операций)
Config::memoizedMacro('app.services', function() {
    return scanForServices(); // Выполнится только один раз
});

// 7. Поддержка JSON
Config::loadFile(__DIR__ . '/config/app.json');
Config::loadFile(__DIR__ . '/config/database.php');

// 8. Экспорт
file_put_contents('config.json', Config::export('json'));
```

---

## Тестирование

Созданы дополнительные тесты (`tests/Unit/Core/ConfigEdgeCasesTest.php`):
- ✅ Edge cases для ключей (пустые, с точками, unicode, специальные символы)
- ✅ Falsy значения (null, false, 0, пустая строка)
- ✅ Большие и глубоко вложенные конфигурации
- ✅ Edge cases для макросов
- ✅ Edge cases для кэширования
- ✅ Производительность операций
- ✅ Многократная загрузка файлов
- ✅ Файлы с side effects

Запуск тестов:
```bash
./vendor/bin/pest tests/Unit/Core/ConfigTest.php
./vendor/bin/pest tests/Unit/Core/ConfigEdgeCasesTest.php
```

---

## Заключение

Ваш класс `Config` уже на высоком уровне. Предложенные улучшения:
1. **Повышают безопасность** (path traversal, circular refs)
2. **Улучшают удобство** (ArrayAccess, getRequired, getMany)
3. **Добавляют гибкость** (JSON, события, валидация)
4. **Оптимизируют производительность** (мемоизация, ленивая загрузка)

Рекомендую начать с **Фазы 1** и постепенно внедрять остальные улучшения по мере необходимости.
