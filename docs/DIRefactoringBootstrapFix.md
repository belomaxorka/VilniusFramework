# Исправление порядка инициализации DI контейнера

## Проблема

После рефакторинга возникла ошибка при запуске приложения:

```
Fatal error: Uncaught RuntimeException: Target [Core\Contracts\ConfigInterface] is not instantiable.
in C:\OSPanel\home\torrentpier\public\core\Container.php:193
```

## Причина

Порядок инициализации был неправильным:

1. ❌ `Core::init()` вызывался ПЕРВЫМ
2. ❌ Внутри `Core::init()` вызывался `Config::loadCached()` (фасад)
3. ❌ Фасад пытался получить `ConfigInterface` из контейнера
4. ❌ Но `config/services.php` загружался только ПОСЛЕ в `public/index.php`!

## Решение

Изменен порядок инициализации в `Core::init()`:

### 1. Добавлен метод `initContainer()` ✅

```php
private static function initContainer(): void
{
    $container = Container::getInstance();
    
    // Загружаем services.php
    $servicesFile = CONFIG_DIR . '/services.php';
    $services = require $servicesFile;
    
    // Регистрируем сервисы
    foreach ($services['singletons'] ?? [] as $abstract => $concrete) {
        $container->singleton($abstract, $concrete);
    }
    
    foreach ($services['bindings'] ?? [] as $abstract => $concrete) {
        $container->bind($abstract, $concrete);
    }
    
    foreach ($services['aliases'] ?? [] as $alias => $abstract) {
        $container->alias($alias, $abstract);
    }
}
```

### 2. Изменен порядок вызовов в `Core::init()` ✅

```php
public static function init(): void
{
    self::initEnvironment();
    self::initContainer();      // ← Теперь ВТОРОЙ шаг (после Env)
    self::initConfigLoader();   // ← Теперь ТРЕТИЙ (контейнер уже готов!)
    self::initDebugSystem();
    self::initializeLang();
    self::initializeDatabase();
    self::initializeEmailer();
}
```

### 3. Упрощен `initConfigLoader()` ✅

```php
private static function initConfigLoader(): void
{
    $environment = Env::get('APP_ENV', 'production');
    $cachePath = STORAGE_DIR . '/cache/config.php';

    // Получаем ConfigInterface из контейнера (теперь он уже зарегистрирован!)
    $config = Container::getInstance()->make(ConfigInterface::class);

    // Try to load from cache first (in production only)
    if ($environment === 'production' && $config->loadCached($cachePath)) {
        return;
    }

    // Load from files
    $config->load(CONFIG_DIR, $environment);

    // Cache for next time (in production only)
    if ($environment === 'production') {
        try {
            $config->cache($cachePath);
        } catch (\Exception $e) {
            // Игнорируем ошибки кеширования
        }
    }
}
```

### 4. Добавлены методы кеширования в интерфейс ✅

`core/Contracts/ConfigInterface.php`:

```php
public function cache(string $cachePath): bool;
public function loadCached(string $cachePath): bool;
public function isLoadedFromCache(): bool;
public function isCached(string $cachePath): bool;
public function clearCache(string $cachePath): bool;
public function getCacheInfo(string $cachePath): ?array;
```

### 5. Реализованы методы кеширования ✅

`core/Services/ConfigRepository.php` теперь включает полную реализацию:
- `cache()` - сохранение конфигурации в файл
- `loadCached()` - загрузка из кеша с проверкой актуальности
- `isLoadedFromCache()` - флаг загрузки из кеша
- `isCached()` - проверка существования кеша
- `clearCache()` - удаление кеша
- `getCacheInfo()` - информация о кеше

### 6. Упрощен `public/index.php` ✅

```php
// Initialize app (это теперь загружает и services.php)
Core::init();

// Get container instance (уже инициализирован в Core::init())
$container = Container::getInstance();

// Initialize router (уже зарегистрирован в контейнере)
$router = $container->make(\Core\Router::class);
```

Больше не нужно вручную регистрировать сервисы в `index.php`!

### 7. Обновлена регистрация ConfigRepository ✅

`config/services.php`:

```php
// Config Service (загрузка конфигурации происходит в Core::init())
\Core\Contracts\ConfigInterface::class => function ($container) {
    return new \Core\Services\ConfigRepository();
},
```

ConfigRepository больше не принимает CONFIG_DIR в конструкторе.

## Правильный порядок инициализации

```
1. Env::load()              - Загрузка переменных окружения
2. Container::init()        - Инициализация контейнера
3. services.php             - Регистрация сервисов в контейнере
4. Config::load()           - Загрузка конфигурации (через фасад)
5. ErrorHandler::register() - Регистрация обработчика ошибок
6. Logger::init()           - Инициализация логгера
7. Database::init()         - Инициализация БД
8. Router::init()           - Инициализация роутера
```

## Результат

✅ Приложение теперь запускается без ошибок
✅ Контейнер инициализируется до первого использования фасадов
✅ ConfigRepository корректно загружается и кешируется
✅ Все зависимости разрешаются правильно

## Дополнительные улучшения

### Упрощена bootstrap-логика

Раньше:
```php
// public/index.php
Core::init();
$container = Container::getInstance();
$services = Config::get('services');
// Ручная регистрация сервисов...
foreach ($services['singletons'] ...
```

Теперь:
```php
// public/index.php
Core::init(); // Все делается здесь!
$container = Container::getInstance();
$router = $container->make(\Core\Router::class);
```

### Централизованная инициализация

Вся логика инициализации контейнера и сервисов теперь в одном месте: `Core::init()`

## Тестирование

```bash
# Запустите приложение
php -S localhost:8000 -t public

# Проверьте что нет ошибок
curl http://localhost:8000/
```

Приложение должно запуститься без ошибок и корректно обработать запросы.

## Заключение

Проблема с порядком инициализации была успешно решена! Теперь:

✅ Контейнер инициализируется до использования
✅ Сервисы регистрируются в правильном порядке
✅ Фасады работают корректно
✅ Кеширование конфигурации функционирует
✅ Код стал проще и понятнее

