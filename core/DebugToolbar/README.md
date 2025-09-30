# Debug Toolbar - Collectors System

Расширяемая система коллекторов для Debug Toolbar.

## Быстрый старт

### 1. Создайте класс коллектора

```php
namespace Core\DebugToolbar\Collectors;

use Core\DebugToolbar\AbstractCollector;

class MyCollector extends AbstractCollector
{
    private static array $data = [];
    
    public function __construct()
    {
        $this->priority = 50;
    }

    public function getName(): string { return 'my_collector'; }
    public function getTitle(): string { return 'My Data'; }
    public function getIcon(): string { return '🎯'; }

    public function collect(): void
    {
        $this->data = ['items' => self::$data];
    }

    public function render(): string
    {
        // Ваш HTML
        return '<div>...</div>';
    }

    public static function log($data): void
    {
        self::$data[] = $data;
    }
}
```

### 2. Зарегистрируйте коллектор

```php
use Core\DebugToolbar;
use Core\DebugToolbar\Collectors\MyCollector;

DebugToolbar::addCollector(new MyCollector());
```

### 3. Используйте

```php
MyCollector::log('Some data');
```

## Встроенные коллекторы

| Коллектор         | Приоритет | Описание         |
|-------------------|-----------|------------------|
| OverviewCollector | 100       | Общая статистика |
| DumpsCollector    | 90        | Debug dumps      |
| QueriesCollector  | 80        | SQL запросы      |
| CacheCollector    | 75        | Операции с кэшем |
| TimersCollector   | 70        | Таймеры          |
| MemoryCollector   | 60        | Память           |
| ContextsCollector | 50        | Контексты        |

**Приоритет:** чем больше число, тем важнее коллектор и раньше он отображается.

## Интерфейс CollectorInterface

```php
interface CollectorInterface
{
    public function getName(): string;        // Уникальное имя
    public function getTitle(): string;       // Название вкладки
    public function getIcon(): string;        // Иконка (emoji)
    public function getBadge(): ?string;      // Badge (опционально)
    public function getPriority(): int;       // Приоритет (порядок)
    public function collect(): void;          // Сбор данных
    public function render(): string;         // HTML вкладки
    public function getHeaderStats(): array;  // Статистика в header
    public function isEnabled(): bool;        // Включен ли
}
```

## AbstractCollector

Базовый класс предоставляет:

### Свойства

- `$enabled` - включен/выключен
- `$priority` - приоритет отображения
- `$data` - собранные данные

### Методы

- `formatBytes(int $bytes)` - форматирование байт
- `formatTime(float $time)` - форматирование времени
- `getColorByThreshold($value, $warning, $critical)` - цвет по порогу

## Примеры

### Минимальный коллектор

```php
class SimpleCollector extends AbstractCollector
{
    private static int $counter = 0;
    
    public function getName(): string { return 'simple'; }
    public function getTitle(): string { return 'Simple'; }
    public function getIcon(): string { return '📝'; }
    
    public function collect(): void
    {
        $this->data = ['count' => self::$counter];
    }
    
    public function render(): string
    {
        return '<div>Count: ' . $this->data['count'] . '</div>';
    }
    
    public static function increment(): void
    {
        self::$counter++;
    }
}
```

### С badge и header stats

```php
class FullCollector extends AbstractCollector
{
    // ... getName, getTitle, getIcon, collect, render ...
    
    public function getBadge(): ?string
    {
        $count = $this->data['count'] ?? 0;
        return $count > 0 ? (string)$count : null;
    }
    
    public function getHeaderStats(): array
    {
        if (empty($this->data)) return [];
        
        return [[
            'icon' => '🎯',
            'value' => $this->data['count'] . ' items',
            'color' => '#66bb6a',
        ]];
    }
}
```

## Управление коллекторами

```php
// Добавить
DebugToolbar::addCollector(new MyCollector());

// Получить
$collector = DebugToolbar::getCollector('my_collector');

// Удалить
DebugToolbar::removeCollector('my_collector');

// Отключить
$collector->setEnabled(false);

// Изменить приоритет
$collector->setPriority(25);
```

## Best Practices

1. **Используйте статические свойства** для сбора данных во время выполнения
2. **Проверяйте наличие данных** перед отображением
3. **Экранируйте HTML** для безопасности
4. **Возвращайте null** в `getBadge()` если нет данных
5. **Возвращайте []** в `getHeaderStats()` если нет данных
6. **Используйте приоритет** для управления порядком вкладок

## Полная документация

См. `/docs/DebugToolbarCollectors.md` для подробного руководства.
