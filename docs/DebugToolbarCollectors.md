# Debug Toolbar - Система коллекторов

Debug Toolbar теперь поддерживает расширяемую систему коллекторов, которая позволяет легко добавлять новые вкладки и функциональность.

## Архитектура

### Основные компоненты

1. **CollectorInterface** - интерфейс для всех коллекторов
2. **AbstractCollector** - базовый класс с общей функциональностью
3. **DebugToolbar** - главный класс с системой регистрации коллекторов

### Встроенные коллекторы

- `OverviewCollector` - общая статистика (приоритет: 10)
- `DumpsCollector` - дебаг дампы (приоритет: 20)
- `QueriesCollector` - SQL запросы (приоритет: 30)
- `CacheCollector` - операции с кэшем (приоритет: 35)
- `TimersCollector` - таймеры (приоритет: 40)
- `MemoryCollector` - использование памяти (приоритет: 50)
- `ContextsCollector` - контексты отладки (приоритет: 60)

## Создание своего коллектора

### Шаг 1: Создайте класс коллектора

```php
<?php

namespace Core\DebugToolbar\Collectors;

use Core\DebugToolbar\AbstractCollector;

class MyCustomCollector extends AbstractCollector
{
    private static array $data = [];
    
    public function __construct()
    {
        $this->priority = 45; // Определяет порядок вкладок
    }

    public function getName(): string
    {
        return 'my_custom'; // Уникальное имя
    }

    public function getTitle(): string
    {
        return 'My Feature'; // Название вкладки
    }

    public function getIcon(): string
    {
        return '🎯'; // Иконка (emoji или HTML)
    }

    public function collect(): void
    {
        // Собираем данные
        $this->data = [
            'items' => self::$data,
            'count' => count(self::$data),
        ];
    }

    public function render(): string
    {
        // Рендерим содержимое вкладки
        if (empty($this->data['items'])) {
            return '<div style="padding: 20px; text-align: center;">No data</div>';
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
        // Badge рядом с названием вкладки
        $count = $this->data['count'] ?? 0;
        return $count > 0 ? (string)$count : null;
    }

    public function getHeaderStats(): array
    {
        // Статистика в header toolbar
        $count = $this->data['count'] ?? 0;
        if ($count === 0) {
            return [];
        }

        return [[
            'icon' => '🎯',
            'value' => $count . ' items',
            'color' => '#66bb6a',
        ]];
    }

    // Статический метод для сбора данных
    public static function log(string $data): void
    {
        self::$data[] = $data;
    }
}
```

### Шаг 2: Зарегистрируйте коллектор

```php
use Core\DebugToolbar;
use Core\DebugToolbar\Collectors\MyCustomCollector;

// В файле инициализации приложения или bootstrap
DebugToolbar::addCollector(new MyCustomCollector());
```

### Шаг 3: Используйте коллектор

```php
use Core\DebugToolbar\Collectors\MyCustomCollector;

// В любом месте приложения
MyCustomCollector::log('Some data');
MyCustomCollector::log('Another data');
```

## Пример: Коллектор кэша

Вот полноценный пример коллектора для отслеживания операций с кэшем:

```php
<?php

namespace Core\DebugToolbar\Collectors;

use Core\DebugToolbar\AbstractCollector;

class CacheCollector extends AbstractCollector
{
    private static array $operations = [];

    public function __construct()
    {
        $this->priority = 35;
    }

    public function getName(): string
    {
        return 'cache';
    }

    public function getTitle(): string
    {
        return 'Cache';
    }

    public function getIcon(): string
    {
        return '🗃️';
    }

    public function collect(): void
    {
        $this->data = [
            'operations' => self::$operations,
            'stats' => $this->calculateStats(),
        ];
    }

    public function getBadge(): ?string
    {
        $count = count(self::$operations);
        return $count > 0 ? (string)$count : null;
    }

    public function render(): string
    {
        // ... HTML рендеринг
    }

    public function getHeaderStats(): array
    {
        $stats = $this->data['stats'] ?? $this->calculateStats();
        
        return [[
            'icon' => '🗃️',
            'value' => count(self::$operations) . ' ops (' . $stats['hits'] . ' hits)',
            'color' => '#66bb6a',
        ]];
    }

    // Публичные методы для логирования
    public static function logHit(string $key, $value = null, float $time = 0.0): void
    {
        self::$operations[] = [
            'type' => 'hit',
            'key' => $key,
            'value' => $value,
            'time' => $time,
        ];
    }

    public static function logMiss(string $key, float $time = 0.0): void
    {
        self::$operations[] = [
            'type' => 'miss',
            'key' => $key,
            'time' => $time,
        ];
    }

    public static function logWrite(string $key, $value = null, float $time = 0.0): void
    {
        self::$operations[] = [
            'type' => 'write',
            'key' => $key,
            'value' => $value,
            'time' => $time,
        ];
    }

    private function calculateStats(): array
    {
        // Подсчет статистики
    }
}
```

### Использование Cache Collector

```php
// Регистрируем коллектор
use Core\DebugToolbar;
use Core\DebugToolbar\Collectors\CacheCollector;

DebugToolbar::addCollector(new CacheCollector());

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

    public function set(string $key, $value, int $ttl = 3600)
    {
        $start = microtime(true);
        
        // ... сохраняем в кэш
        
        $time = (microtime(true) - $start) * 1000;
        CacheCollector::logWrite($key, $value, $time);
    }
}
```

## Управление коллекторами

### Добавление коллектора

```php
use Core\DebugToolbar;
use Core\DebugToolbar\Collectors\MyCollector;

DebugToolbar::addCollector(new MyCollector());
```

### Получение коллектора

```php
$collector = DebugToolbar::getCollector('cache');
if ($collector) {
    // Работаем с коллектором
}
```

### Удаление коллектора

```php
DebugToolbar::removeCollector('cache');
```

### Включение/выключение коллектора

```php
$collector = DebugToolbar::getCollector('cache');
$collector->setEnabled(false); // Выключить
```

### Изменение приоритета

```php
$collector = DebugToolbar::getCollector('cache');
$collector->setPriority(15); // Изменить порядок отображения
```

## CollectorInterface

Все коллекторы должны реализовывать интерфейс `CollectorInterface`:

```php
interface CollectorInterface
{
    public function getName(): string;
    public function getTitle(): string;
    public function getIcon(): string;
    public function getBadge(): ?string;
    public function getPriority(): int;
    public function collect(): void;
    public function render(): string;
    public function getHeaderStats(): array;
    public function isEnabled(): bool;
}
```

## AbstractCollector

Базовый класс предоставляет:

- Управление включением/выключением
- Управление приоритетом
- Хелперы для форматирования:
  - `formatBytes(int $bytes)` - форматирование байт
  - `formatTime(float $time)` - форматирование времени
  - `getColorByThreshold()` - получение цвета по порогу

## Приоритеты

Коллекторы отображаются в порядке приоритета (меньше = раньше):

- 10 - Overview (обзор)
- 20 - Dumps (дампы)
- 30 - Queries (запросы)
- 35 - Cache (кэш)
- 40 - Timers (таймеры)
- 50 - Memory (память)
- 60 - Contexts (контексты)
- 100+ - ваши кастомные коллекторы

## Рекомендации

1. **Используйте статические поля** для сбора данных во время выполнения
2. **Собирайте данные в `collect()`** - метод вызывается один раз при рендере
3. **Проверяйте `isEnabled()`** перед сбором данных
4. **Возвращайте `null` в `getBadge()`** если нет данных для badge
5. **Возвращайте `[]` в `getHeaderStats()`** если нет данных для header
6. **Экранируйте HTML** в методе `render()` для безопасности
7. **Используйте приоритет** для контроля порядка вкладок

## Примеры использования

### HTTP Requests Collector

```php
class HttpCollector extends AbstractCollector
{
    private static array $requests = [];

    public static function logRequest(string $url, string $method, int $statusCode, float $time): void
    {
        self::$requests[] = compact('url', 'method', 'statusCode', 'time');
    }
}
```

### Events Collector

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
```

### Mail Collector

```php
class MailCollector extends AbstractCollector
{
    private static array $emails = [];

    public static function logEmail(string $to, string $subject, string $body): void
    {
        self::$emails[] = compact('to', 'subject', 'body');
    }
}
```
