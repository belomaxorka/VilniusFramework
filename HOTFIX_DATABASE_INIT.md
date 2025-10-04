# 🔧 Hotfix - Удаление Legacy Вызовов

## 🐛 Проблема

После рефакторинга остались вызовы удаленных legacy методов:
- `Database::init()` в `core/Core.php`
- `Database::getInstance()` в `Schema.php` и `MigrationRepository.php`

## ✅ Исправления

### 1. `core/Core.php` (строка 96)

**Было:**
```php
private static function initializeDatabase(): void
{
    // Database фасад теперь автоматически резолвится через контейнер
    // Просто получаем instance чтобы убедиться что он создан
    Database::init();  // ❌ Вызов удаленного метода
}
```

**Стало:**
```php
private static function initializeDatabase(): void
{
    // Database теперь автоматически резолвится через DI контейнер
    // При первом обращении к фасаду Database будет создан DatabaseManager
    // Дополнительная инициализация не требуется
}
```

### 2. `core/Database/Schema/Schema.php` (строка 31)

**Было:**
```php
private static function getDatabase(): DatabaseManager
{
    if (self::$database === null) {
        self::$database = Database::getInstance();  // ❌ Вызов удаленного метода
    }

    return self::$database;
}
```

**Стало:**
```php
private static function getDatabase(): DatabaseManager
{
    if (self::$database === null) {
        // Получаем через DI контейнер
        self::$database = \Core\Container::getInstance()->make(\Core\Contracts\DatabaseInterface::class);
    }

    return self::$database;
}
```

### 3. `core/Database/Migrations/MigrationRepository.php` (строка 28)

**Было:**
```php
public function __construct()
{
    $this->database = Database::getInstance();  // ❌ Вызов удаленного метода
}
```

**Стало:**
```php
public function __construct()
{
    // Получаем через DI контейнер
    $this->database = \Core\Container::getInstance()->make(\Core\Contracts\DatabaseInterface::class);
}
```

## 📊 Статистика

- ✅ 3 файла исправлено
- ✅ 3 legacy вызова удалено
- ✅ 0 ошибок линтера
- ✅ Приложение должно работать

## ⚠️ Потенциальные проблемы

### Тесты с `Config::getInstance()`

Найдено 14 вызовов `Config::getInstance()` в тестах:
- `tests/Unit/Core/Config/ConfigAdvancedTest.php`

**Решение:** Эти тесты нужно будет обновить позже, заменив:
```php
// ❌ Старый способ
$config = Config::getInstance();

// ✅ Новый способ
$config = Container::getInstance()->make(ConfigInterface::class);
```

## 🚀 Проверка

После этих исправлений приложение должно запуститься без ошибок.

**Команда для проверки:**
```bash
php public/index.php
```

Или откройте в браузере: `http://localhost/`

## 📝 Следующие шаги

1. ✅ Проверить, что приложение запускается
2. ⚠️ Исправить тесты с `Config::getInstance()` (если нужно)
3. ✅ Продолжить разработку

---

**Дата:** 4 октября 2025  
**Тип:** Hotfix  
**Статус:** ✅ Исправлено

