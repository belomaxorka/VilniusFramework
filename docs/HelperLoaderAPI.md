# HelperLoader API Reference

Полная документация по API загрузчика хелперов.

## Методы класса

### Загрузка отдельных файлов

#### `load(string $name): bool`
Загружает отдельный файл хелпера по имени (без .php).

```php
$loader = HelperLoader::getInstance();
$loader->load('custom_helper');  // Загрузит core/helpers/custom_helper.php
```

**Возвращает:** `true` если загружен, `false` если уже был загружен

---

#### `loadMultiple(array $names): bool`
Загружает несколько файлов хелперов.

```php
$loader->loadMultiple(['helper1', 'helper2', 'helper3']);
```

**Возвращает:** `true` если все загружены успешно

---

### Загрузка групп

#### `loadGroup(string $groupName): bool`
Загружает все файлы из директории-группы.

```php
$loader->loadGroup('app');     // Загрузит core/helpers/app/*.php
$loader->loadGroup('debug');   // Загрузит core/helpers/debug/*.php
```

**Возвращает:** `true` если загружена, `false` если уже была загружена

**Исключения:**
- `RuntimeException` - если группа не найдена
- `RuntimeException` - если группа пустая

---

#### `loadGroups(array $groups): bool`
Загружает несколько групп одновременно.

```php
$loader->loadGroups(['app', 'environment', 'debug']);
```

**Возвращает:** `true` если все загружены успешно

---

#### `loadAll(): bool` ⭐ NEW
Автоматически загружает ВСЕ доступные группы из директории helpers/.

```php
$loader->loadAll();  // Загрузит app, environment, debug, profiler, database, context
```

**Возвращает:** `true` если хотя бы одна группа была загружена, `false` если нет доступных групп

**Особенности:**
- Автоматически находит все папки в `core/helpers/`
- Пропускает уже загруженные группы
- Идеально для быстрого старта или небольших проектов

---

### Информация о загруженном

#### `isLoaded(string $name): bool`
Проверяет, загружен ли файл или группа.

```php
// Проверка файла
$loader->isLoaded('custom_helper');  // true/false

// Проверка группы
$loader->isLoaded('group:app');      // true/false
```

---

#### `getLoaded(): array`
Возвращает список всех загруженных файлов и групп.

```php
$loaded = $loader->getLoaded();
// ['group:app', 'group:debug', 'custom_helper']
```

---

#### `getAvailable(): array`
Возвращает список доступных файлов хелперов (в корне helpers/).

```php
$available = $loader->getAvailable();
// ['custom_helper', 'legacy_helper']
```

---

#### `getAvailableGroups(): array` ⭐ NEW
Возвращает список доступных групп (папок в helpers/).

```php
$groups = $loader->getAvailableGroups();
// ['app', 'environment', 'debug', 'profiler', 'database', 'context']
```

---

### Управление

#### `reload(string $name): bool`
Перезагружает файл хелпера.

```php
$loader->reload('custom_helper');
```

⚠️ **Примечание:** Из-за `require_once` функции не будут переопределены в текущем процессе.

---

#### `reset(): self`
Очищает список загруженных хелперов.

```php
$loader->reset();
```

---

### Статические методы

Для удобства доступны статические обёртки:

```php
// Одиночные файлы
HelperLoader::loadHelper('name');
HelperLoader::loadHelpers(['name1', 'name2']);
HelperLoader::isHelperLoaded('name');

// Группы
HelperLoader::loadHelperGroup('app');
HelperLoader::loadHelperGroups(['app', 'debug']);

// Загрузить всё
HelperLoader::loadAllHelpers();  // ⭐ NEW
```

---

## Примеры использования

### Пример 1: Минимальная настройка

```php
// Самый простой способ - загрузить всё
\Core\HelperLoader::loadAllHelpers();

// Готово! Все функции доступны
config('app.name');
dump($data);
timer_start('request');
```

---

### Пример 2: Контролируемая загрузка

```php
// Загрузить только необходимое
\Core\HelperLoader::loadHelperGroups([
    'app',          // config(), env(), view()
    'environment',  // is_debug(), is_dev()
    'debug',        // dd(), dump()
]);

// Условная загрузка
if (is_dev()) {
    \Core\HelperLoader::loadHelperGroups(['profiler', 'database']);
}
```

---

### Пример 3: Динамическая загрузка

```php
$loader = \Core\HelperLoader::getInstance();

// Проверить доступные группы
$available = $loader->getAvailableGroups();
echo "Доступные группы: " . implode(', ', $available) . "\n";

// Загрузить только определенные
foreach (['app', 'debug'] as $group) {
    if (in_array($group, $available)) {
        $loader->loadGroup($group);
    }
}

// Проверить что загружено
$loaded = $loader->getLoaded();
echo "Загружено: " . implode(', ', $loaded) . "\n";
```

---

### Пример 4: Ленивая загрузка

```php
// В bootstrap.php - только основное
\Core\HelperLoader::loadHelperGroups(['app', 'environment']);

// В контроллере - специфичные функции
class ApiController
{
    public function __construct()
    {
        // Загружаем профайлер только для API
        \Core\HelperLoader::loadHelperGroup('profiler');
    }
    
    public function handle()
    {
        timer_start('api_request');
        // ...
        timer_stop('api_request');
    }
}
```

---

### Пример 5: Проверка перед использованием

```php
$loader = \Core\HelperLoader::getInstance();

// Безопасная загрузка
if (!$loader->isLoaded('group:profiler')) {
    $loader->loadGroup('profiler');
}

// Проверка функции
if (function_exists('timer_start')) {
    timer_start('operation');
}
```

---

## Сравнение подходов

### loadAllHelpers() vs loadHelperGroups()

| Аспект | loadAllHelpers() | loadHelperGroups() |
|--------|------------------|---------------------|
| **Простота** | ⭐⭐⭐⭐⭐ Максимально просто | ⭐⭐⭐ Требует перечисления |
| **Контроль** | ⭐⭐ Загружает всё | ⭐⭐⭐⭐⭐ Полный контроль |
| **Производительность** | ⭐⭐⭐ Загружает все группы | ⭐⭐⭐⭐ Только нужные |
| **Гибкость** | ⭐⭐ Всё или ничего | ⭐⭐⭐⭐⭐ Условная загрузка |
| **Подходит для** | Небольшие проекты | Большие проекты |

---

## Рекомендации

### ✅ Используйте loadAllHelpers() если:
- Небольшой проект (< 10 000 строк)
- Нужны все функции везде
- Простота важнее оптимизации
- Быстрый старт/прототипирование

### ✅ Используйте loadHelperGroups() если:
- Большой проект
- Нужен контроль над загружаемым кодом
- Важна производительность
- Хотите условную загрузку (dev vs prod)

### ✅ Комбинированный подход:
```php
// Основные группы всегда
\Core\HelperLoader::loadHelperGroups(['app', 'environment']);

// В dev - всё остальное
if (is_dev()) {
    \Core\HelperLoader::loadAllHelpers();
}
```

---

## Диагностика

### Проверка загруженных групп

```php
$loader = \Core\HelperLoader::getInstance();

echo "=== Загруженные группы ===\n";
foreach ($loader->getLoaded() as $item) {
    echo "✅ {$item}\n";
}

echo "\n=== Доступные группы ===\n";
foreach ($loader->getAvailableGroups() as $group) {
    $status = $loader->isLoaded("group:{$group}") ? "✅" : "⏳";
    echo "{$status} {$group}\n";
}
```

### Подсчет функций

```php
$functions = [
    'app' => ['config', 'env', 'view'],
    'debug' => ['dd', 'dump', 'collect'],
    // ...
];

foreach ($functions as $group => $funcs) {
    $available = array_filter($funcs, 'function_exists');
    $percent = count($available) / count($funcs) * 100;
    echo "{$group}: {$percent}% доступно\n";
}
```

---

## См. также

- [Helpers.md](Helpers.md) - Документация по функциям хелперов
- [HelperLoadingFlow.md](HelperLoadingFlow.md) - Как работает загрузка
- [core/helpers/README.md](../core/helpers/README.md) - Структура хелперов

