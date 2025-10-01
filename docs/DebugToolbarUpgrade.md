# Debug Toolbar - Обновление до расширяемой архитектуры

## Что изменилось

Debug Toolbar был полностью переработан с монолитной архитектуры на **расширяемую систему коллекторов**.

### До (монолитная архитектура)
- ❌ Жестко закодированные вкладки в `collectStats()` и `collectTabs()`
- ❌ Невозможно добавить новые вкладки без изменения ядра
- ❌ Вся логика сбора и отображения в одном файле
- ❌ Нет стандартного API для расширения

### После (расширяемая архитектура)
- ✅ Система коллекторов с единым интерфейсом
- ✅ Легкое добавление новых вкладок без изменения ядра
- ✅ Разделение логики по отдельным классам
- ✅ Стандартный API через `CollectorInterface`

## Новые компоненты

### 1. Интерфейс CollectorInterface
`core/DebugToolbar/CollectorInterface.php`

Определяет контракт для всех коллекторов:
- `getName()` - уникальное имя
- `getTitle()` - название вкладки
- `getIcon()` - иконка
- `collect()` - сбор данных
- `render()` - отрисовка
- И др.

### 2. Абстрактный класс AbstractCollector
`core/DebugToolbar/AbstractCollector.php`

Базовая реализация с общей функциональностью:
- Управление приоритетом
- Включение/выключение
- Хелперы форматирования (bytes, time, colors)

### 3. Встроенные коллекторы
`core/DebugToolbar/Collectors/`

- **RequestCollector** - данные входящего запроса (приоритет: 90)
- **ResponseCollector** - данные исходящего ответа (приоритет: 88)
- **RoutesCollector** - информация о маршрутизации (приоритет: 85)
- **DumpsCollector** - debug dumps (приоритет: 90)
- **QueriesCollector** - SQL запросы (приоритет: 80)
- **CacheCollector** - операции с кэшем (приоритет: 75)
- **TimersCollector** - таймеры и время выполнения (приоритет: 70)
- **MemoryCollector** - использование памяти (приоритет: 60)
- **ContextsCollector** - контексты отладки (приоритет: 50)

### 4. Обновленный DebugToolbar
`core/DebugToolbar.php`

Новые методы для работы с коллекторами:
- `addCollector(CollectorInterface $collector)` - добавить коллектор
- `getCollector(string $name)` - получить коллектор
- `removeCollector(string $name)` - удалить коллектор
- `getCollectors()` - получить все коллекторы

## Обратная совместимость

✅ **Полная обратная совместимость** - все существующие функции работают как прежде:
- `DebugToolbar::render()`
- `DebugToolbar::enable()`
- `DebugToolbar::setPosition()`
- `DebugToolbar::setCollapsed()`
- `render_debug_toolbar()` helper

## Как использовать

### Создание своего коллектора

```php
<?php

namespace Core\DebugToolbar\Collectors;

use Core\DebugToolbar\AbstractCollector;

class MyCollector extends AbstractCollector
{
    private static array $data = [];
    
    public function __construct()
    {
        $this->priority = 85; // Чем больше, тем важнее (раньше отображается)
    }

    public function getName(): string
    {
        return 'my_feature';
    }

    public function getTitle(): string
    {
        return 'My Feature';
    }

    public function getIcon(): string
    {
        return '🎯';
    }

    public function collect(): void
    {
        $this->data = ['items' => self::$data];
    }

    public function render(): string
    {
        if (empty($this->data['items'])) {
            return '<div style="padding: 20px;">No data</div>';
        }

        $html = '<div style="padding: 10px;">';
        foreach ($this->data['items'] as $item) {
            $html .= '<div>' . htmlspecialchars($item) . '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    public function getBadge(): ?string
    {
        return count(self::$data) > 0 ? (string)count(self::$data) : null;
    }

    public function getHeaderStats(): array
    {
        $count = count(self::$data);
        if ($count === 0) return [];

        return [[
            'icon' => '🎯',
            'value' => $count . ' items',
            'color' => '#66bb6a',
        ]];
    }

    public static function log(string $data): void
    {
        self::$data[] = $data;
    }
}
```

### Регистрация коллектора

```php
use Core\DebugToolbar;
use Core\DebugToolbar\Collectors\MyCollector;

// В bootstrap.php или config
DebugToolbar::addCollector(new MyCollector());
```

### Использование

```php
// В любом месте приложения
MyCollector::log('Some data');
MyCollector::log('Another data');

// Данные автоматически появятся в новой вкладке Debug Toolbar!
```

## Примеры реальных коллекторов

### Cache Collector (уже включен)

```php
use Core\DebugToolbar\Collectors\CacheCollector;

// В вашем классе кэша
class Cache
{
    public function get(string $key)
    {
        $start = microtime(true);
        $value = // ... получаем из кэша
        $time = (microtime(true) - $start) * 1000;
        
        if ($value !== null) {
            CacheCollector::logHit($key, $value, $time);
        } else {
            CacheCollector::logMiss($key, $time);
        }
        
        return $value;
    }
}

// Toolbar покажет:
// - 🗃️ Cache вкладку
// - Статистику hits/misses/writes
// - Hit rate
// - Список всех операций
```

### HTTP Requests Collector (пример)

```php
class HttpCollector extends AbstractCollector
{
    private static array $requests = [];

    public static function logRequest(string $method, string $url, int $statusCode, float $time): void
    {
        self::$requests[] = compact('method', 'url', 'statusCode', 'time');
    }
}

// Использование
HttpCollector::logRequest('GET', 'https://api.example.com/users', 200, 125.5);

// Toolbar покажет HTTP вкладку с запросами
```

### Events Collector (пример)

```php
class EventsCollector extends AbstractCollector
{
    private static array $events = [];

    public static function logEvent(string $name, array $payload): void
    {
        self::$events[] = [
            'name' => $name,
            'payload' => $payload,
            'time' => microtime(true),
        ];
    }
}

// Использование
EventsCollector::logEvent('user.registered', ['id' => 123]);

// Toolbar покажет Events вкладку
```

## Управление коллекторами

```php
// Отключить встроенный коллектор
$collector = DebugToolbar::getCollector('queries');
$collector->setEnabled(false);

// Удалить коллектор
DebugToolbar::removeCollector('timers');

// Изменить приоритет (порядок вкладок)
$collector = DebugToolbar::getCollector('cache');
$collector->setPriority(15);
```

## Преимущества новой архитектуры

### 1. Расширяемость
- Легко добавлять новые коллекторы без изменения ядра
- Каждый коллектор - отдельный класс с четкой ответственностью

### 2. Модульность
- Встроенные коллекторы можно отключать/удалять
- Код разделен на логические компоненты

### 3. Переиспользование
- `AbstractCollector` предоставляет общую функциональность
- Хелперы для форматирования и стилизации

### 4. Стандартизация
- Единый интерфейс для всех коллекторов
- Предсказуемое API

### 5. Гибкость
- Управление приоритетами
- Динамическое включение/выключение
- Настраиваемое отображение

## Миграция существующего кода

**Не требуется!** Вся существующая функциональность работает без изменений:

```php
// До и После - работает одинаково
dump($data);
query_log('SELECT ...', [], 25.0, 100);
echo render_debug_toolbar();
```

## Файлы и документация

### Новые файлы
- `core/DebugToolbar/CollectorInterface.php`
- `core/DebugToolbar/AbstractCollector.php`
- `core/DebugToolbar/Collectors/RequestCollector.php`
- `core/DebugToolbar/Collectors/ResponseCollector.php`
- `core/DebugToolbar/Collectors/RoutesCollector.php`
- `core/DebugToolbar/Collectors/DumpsCollector.php`
- `core/DebugToolbar/Collectors/QueriesCollector.php`
- `core/DebugToolbar/Collectors/CacheCollector.php`
- `core/DebugToolbar/Collectors/TimersCollector.php`
- `core/DebugToolbar/Collectors/MemoryCollector.php`
- `core/DebugToolbar/Collectors/ContextsCollector.php`
- `core/DebugToolbar/README.md`

### Обновленные файлы
- `core/DebugToolbar.php` - переработан для работы с коллекторами

### Документация
- `docs/DebugToolbar.md` - обновлена с разделом о коллекторах
- `docs/DebugToolbarCollectors.md` - полное руководство по коллекторам
- `docs/DebugToolbarUpgrade.md` - этот файл
- `docs/examples/CustomCollectorExample.php` - пример HTTP коллектора

## Следующие шаги

1. **Попробуйте создать свой коллектор** для вашего функционала (кэш, API, события и т.д.)
2. **Используйте CacheCollector** как пример интеграции
3. **См. документацию** в `docs/DebugToolbarCollectors.md`
4. **Экспериментируйте** с управлением коллекторами

## Заключение

Debug Toolbar теперь стал **по-настоящему расширяемым**! 

Вы можете:
- ✅ Легко добавлять свои вкладки
- ✅ Интегрировать любую функциональность
- ✅ Отслеживать специфичные для вашего приложения метрики
- ✅ Все это без изменения кода фреймворка

**Пример использования:**
```php
// 1. Создали коллектор для кэша
// 2. Зарегистрировали: DebugToolbar::addCollector(new CacheCollector());
// 3. В коде кэша: CacheCollector::logHit($key, $value, $time);
// 4. Получили вкладку 🗃️ Cache в Debug Toolbar!
```

Наслаждайтесь улучшенным Debug Toolbar! 🚀🐛
