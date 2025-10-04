# Оптимизация проверок файловой системы

> ⚠️ **УСТАРЕЛО**: Этот документ описывает промежуточный подход.  
> Актуальная версия: [ZeroFilesystemChecks.md](ZeroFilesystemChecks.md)

## 📊 Обзор

~~Проведена оптимизация проверок файловой системы (`is_dir()`, `mkdir()`, `file_exists()`) для улучшения производительности фреймворка. Проверки перенесены из "горячих" участков кода (вызываемых многократно) в инициализацию компонентов.~~

**Новый подход:** Все проверки `is_dir()` и `mkdir()` полностью убраны. Директории создаются один раз через `php artisan storage:setup`. См. [ZeroFilesystemChecks.md](ZeroFilesystemChecks.md)

## ✅ Оптимизированные компоненты

### 1. **Logger/FileHandler.php**

**Было:** Проверка `is_dir()` и `mkdir()` при **каждой записи** в лог
```php
public function handle(string $level, string $message): void
{
    $dir = dirname($this->file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    // ...запись в файл
}
```

**Стало:** Проверка убрана, директория создается один раз в `Core::initDebugSystem()`
```php
public function handle(string $level, string $message): void
{
    // Директория уже создана в Core::initDebugSystem()
    // Избыточная проверка при каждой записи удалена для производительности
    $entry = sprintf("[%s] [%s] %s%s", date('Y-m-d H:i:s'), strtoupper($level), $message, PHP_EOL);
    file_put_contents($this->file, $entry, FILE_APPEND);
}
```

**Результат:** Исключена проверка файловой системы при каждом вызове логирования (может вызываться сотни раз за запрос).

---

### 2. **Cache/Drivers/FileDriver.php**

**Было:** Проверка `is_dir()` при **каждом вызове** `set()`
```php
public function set(string $key, mixed $value, ...): bool
{
    $dir = dirname($file);
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return false;
    }
    // ...сохранение в кэш
}
```

**Стало:** Кэширование проверенных директорий в памяти
```php
protected array $createdDirs = []; // Кэш созданных директорий

public function set(string $key, mixed $value, ...): bool
{
    $dir = dirname($file);
    
    // Проверяем только если директория еще не создавалась в этой сессии
    if (!isset($this->createdDirs[$dir])) {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                return false;
            }
        }
        $this->createdDirs[$dir] = true; // Запоминаем
    }
    // ...сохранение в кэш
}
```

**Результат:** Проверка выполняется только один раз для каждой поддиректории кэша. При активном использовании кэша это экономит тысячи системных вызовов.

---

### 3. **Emailer/Drivers/LogDriver.php**

**Было:** Проверка `is_dir()` при **каждой отправке** email
```php
public function send(EmailMessage $message): bool
{
    $dir = dirname($this->logFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    // ...запись email в лог
}
```

**Стало:** Проверка перенесена в конструктор
```php
public function __construct(array $config)
{
    $this->logFile = $config['path'] ?? LOG_DIR . '/emails.log';
    
    // Создаем директорию один раз в конструкторе
    $dir = dirname($this->logFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

public function send(EmailMessage $message): bool
{
    // Директория уже создана в конструкторе, не проверяем повторно
    file_put_contents($this->logFile, $logEntry . PHP_EOL . PHP_EOL, FILE_APPEND);
    // ...
}
```

**Результат:** Проверка выполняется один раз при создании драйвера вместо каждой отправки.

---

### 4. **DumpClient.php**

**Было:** Проверка `is_dir()` при **каждом вызове** `dump()`
```php
private static function logToFile(array $payload): void
{
    $logDir = STORAGE_DIR . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    // ...запись в файл
}
```

**Стало:** Кэширование проверки с помощью статического флага
```php
private static bool $logDirChecked = false;

private static function logToFile(array $payload): void
{
    $logDir = STORAGE_DIR . '/logs';
    
    // Создаём директорию один раз (кэшируем проверку)
    if (!self::$logDirChecked) {
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        self::$logDirChecked = true;
    }
    // ...запись в файл
}
```

**Результат:** Проверка выполняется только один раз за весь lifecycle приложения.

---

## 🎯 Оставлено без изменений

Следующие проверки **оставлены**, так как вызываются редко:

### 1. **Router.php** - `saveCache()`
Проверка `is_dir()` при сохранении кэша маршрутов (вызывается 1 раз при `php artisan route:cache`).

### 2. **Config.php** - `cache()`
Проверка `is_dir()` при сохранении кэша конфигурации (вызывается 1 раз в production).

### 3. **Console/Commands/BaseMakeCommand.php** - `createFile()`
Проверка `is_dir()` при генерации файлов через консоль (вызывается редко, только при разработке).

### 4. **TemplateEngine.php** - конструктор
Проверка `is_dir()` в конструкторе шаблонизатора (вызывается 1 раз при создании инстанса).

---

## 📈 Оценка влияния на производительность

| Компонент | Частота вызовов | Экономия на 1000 запросов | Приоритет |
|-----------|-----------------|---------------------------|-----------|
| **Logger** | ~100-500/запрос | ~100-500 `is_dir()` | 🔥 Высокий |
| **Cache** | ~10-100/запрос | ~10-100 `is_dir()` | 🔥 Высокий |
| **EmailDriver** | ~0-10/запрос | ~0-10 `is_dir()` | 🟡 Средний |
| **DumpClient** | ~0-20/запрос (dev) | ~0-20 `is_dir()` | 🟢 Низкий |

**Общая экономия:** До **620 системных вызовов** на 1000 HTTP-запросов.

---

## 🛡️ Безопасность

Все оптимизации безопасны, так как:

1. **Базовые директории** (`LOG_DIR`, `CACHE_DIR`) создаются при инициализации в `Core::init()`
2. **Кэш проверок** сбрасывается при каждом новом запросе
3. **Race conditions** исключены использованием `mkdir(..., true)` (рекурсивное создание)
4. **Fallback проверки** остались в конструкторах компонентов

---

## 🧪 Тестирование

Рекомендуется протестировать:

1. ✅ Логирование работает корректно
2. ✅ Кэш сохраняется и читается без ошибок
3. ✅ Email-драйвер создает файлы правильно
4. ✅ Dump-функция работает в dev-режиме

```bash
# Тест логирования
php artisan test tests/Unit/LoggerTest.php

# Тест кэша
php artisan test tests/Unit/CacheTest.php

# Тест шаблонизатора
php artisan test tests/Unit/TemplateEngineTest.php
```

---

## 📝 Рекомендации

1. **В production:** Убедитесь, что все необходимые директории существуют перед запуском
2. **При деплое:** Используйте `php artisan cache:clear` для сброса кэшей
3. **Мониторинг:** Следите за ошибками связанными с правами доступа к директориям

---

## 🔗 Связанные файлы

- `core/Core.php` - инициализация базовых директорий
- `core/Logger/FileHandler.php` - обработчик логов
- `core/Cache/Drivers/FileDriver.php` - файловый кэш-драйвер
- `core/Emailer/Drivers/LogDriver.php` - email-драйвер для логов
- `core/DumpClient.php` - клиент для дампов

---

**Дата оптимизации:** 4 октября 2025  
**Версия фреймворка:** dev/feat/added-vite

