# Debug Toolbar Refactoring

Документация по рефакторингу Debug Toolbar для устранения дублирования кода.

## 📋 Обзор изменений

Был проведен масштабный рефакторинг системы Debug Toolbar для устранения дублирования кода и улучшения архитектуры. 

### Статистика
- **Удалено дублированного кода**: ~300-400 строк
- **Создано новых классов**: 2 (ColorPalette, HtmlRenderer)
- **Обновлено файлов**: 14
- **Сокращение кода**: ~20-30%

---

## 🎨 Новые компоненты

### 1. ColorPalette (core/DebugToolbar/ColorPalette.php)

Централизованное хранилище цветов Material Design для всех коллекторов.

**Ключевые особенности:**
- Константы для всех используемых цветов
- Методы для определения цвета по контексту
- Поддержка HTTP методов, статусов, уровней логов, cache операций

**Пример использования:**
```php
use Core\DebugToolbar\ColorPalette;

// Цвет для HTTP метода
$color = ColorPalette::getHttpMethodColor('GET'); // '#4caf50'

// Цвет для статус кода
$color = ColorPalette::getHttpStatusColor(404); // '#ffa726'

// Цвет по порогам
$color = ColorPalette::getThresholdColor(85, 50, 75); // '#ef5350' (red)

// Прямое использование констант
$color = ColorPalette::SUCCESS; // '#66bb6a'
```

**Константы:**
- `SUCCESS`, `WARNING`, `ERROR`, `INFO`, `CRITICAL`
- `PRIMARY`, `SECONDARY`, `ACCENT`, `LIGHT`, `DARK`
- `GREY`, `GREY_LIGHT`, `GREY_DARK`
- `HTTP_*` (для HTTP методов)
- `LOG_*` (для уровней логов)
- `CACHE_*` (для cache операций)

---

### 2. HtmlRenderer (core/DebugToolbar/HtmlRenderer.php)

Набор переиспользуемых методов для рендеринга HTML компонентов.

**Доступные методы:**

#### `renderEmptyState(string $message): string`
Отображение пустого состояния.
```php
return HtmlRenderer::renderEmptyState('No data available');
```

#### `renderSection(string $title, array $data): string`
Секция с заголовком и данными.
```php
HtmlRenderer::renderSection('Basic Info', [
    'Method' => 'GET',
    'URI' => '/api/users',
]);
```

#### `renderDataTable(string $title, array $data, bool $collapsible = false, ?string $warningMessage = null): string`
Таблица с данными (опционально сворачиваемая).
```php
HtmlRenderer::renderDataTable('Headers', $headers, true);
```

#### `renderBadge(string $text, string $color): string`
Badge (значок).
```php
HtmlRenderer::renderBadge('GET', ColorPalette::HTTP_GET);
```

#### `renderStatCard(string $title, string $value, string $color): string`
Статистическая карточка.
```php
HtmlRenderer::renderStatCard('Total', '42', ColorPalette::INFO);
```

#### `renderProgressBar(float $percent, ?string $color = null, int $height = 20): string`
Прогресс бар.
```php
HtmlRenderer::renderProgressBar(75.5); // автоматический цвет
```

#### `renderStatsGrid(array $stats, int $columns = 4): string`
Сетка со статистикой.
```php
HtmlRenderer::renderStatsGrid([
    'Total' => 100,
    'Success' => 95,
    'Failed' => 5,
]);
```

#### `renderHighlightBox(string $content, string $color, ?string $title = null): string`
Выделенный блок с рамкой.
```php
HtmlRenderer::renderHighlightBox(
    'Important message',
    ColorPalette::WARNING,
    'Warning'
);
```

---

## 🔧 Расширение AbstractCollector

Добавлены новые protected методы для всех коллекторов.

### Новые методы:

#### `formatValue(mixed $value, bool $truncate = true, int $maxLength = 50): string`
Универсальное форматирование любых значений.
```php
$this->formatValue(['key' => 'value']); // "Array (1 items)"
$this->formatValue($longString, true, 50); // обрезает до 50 символов
```

#### `getMethodColor(string $method): string`
Цвет для HTTP метода.
```php
$color = $this->getMethodColor('POST'); // '#2196f3'
```

#### `renderEmptyState(string $message): string`
Shortcut для HtmlRenderer::renderEmptyState.
```php
return $this->renderEmptyState('No queries executed');
```

#### `countBadge(string $dataKey): ?string`
Стандартная реализация badge на основе количества элементов.
```php
public function getBadge(): ?string
{
    return $this->countBadge('queries'); // подсчитывает $this->data['queries']
}
```

#### `getLevelColor(string $level): string`
Цвет для уровня лога (debug, info, warning, error, critical).
```php
$color = $this->getLevelColor('error'); // '#ef5350'
```

#### `getTimeColor(float $timeMs, float $fast = 100, float $medium = 500): string`
Цвет для времени выполнения (автоматически: зелёный/оранжевый/красный).
```php
$color = $this->getTimeColor(150); // '#ffa726' (orange)
```

---

## 📝 Обновленные коллекторы

Все 12 коллекторов были обновлены для использования новых утилит:

### 1. **EmailCollector** ✅ КРИТИЧНОЕ
- **Было**: Не соответствовал интерфейсу, использовал Tailwind классы
- **Стало**: Полностью переписан с использованием ColorPalette и HtmlRenderer
- Теперь использует inline стили
- Правильная реализация всех методов интерфейса

### 2. **CacheCollector** ✅
- Удален дублированный `formatValue()`
- Удален дублированный `getOperationColor()`
- Использует `ColorPalette::getCacheOperationColor()`
- Использует `countBadge()`

### 3. **QueriesCollector** ✅
- Использует `countBadge()`
- Использует `renderEmptyState()`
- Цвета заменены на константы ColorPalette

### 4. **LogsCollector** ✅
- Удален дублированный `getLevelColor()`
- Использует `ColorPalette::getLogLevelColor()`
- Использует `renderEmptyState()`

### 5. **RequestCollector** ✅
- Удалены методы: `getMethodColor()`, `renderBadge()`, `formatValue()`, `renderDataTable()`
- Использует `HtmlRenderer` для всех компонентов
- Значительное сокращение кода (~100 строк)

### 6. **ResponseCollector** ✅
- Удалены методы: `renderStatCard()`, `getTimeColor()`
- Использует `HtmlRenderer::renderStatCard()`
- Использует `getTimeColor()` из AbstractCollector

### 7. **RoutesCollector** ✅
- Удален дублированный `getMethodColor()`
- Использует метод из AbstractCollector

### 8. **TemplatesCollector** ✅
- Использует `renderEmptyState()`
- Использует `getTimeColor()` с правильными порогами

### 9. **TimersCollector** ✅
- Использует `getTimeColor()` с правильными порогами

### 10. **MemoryCollector** ✅
- Использует `ColorPalette::getThresholdColor()`
- Использует `ColorPalette::GREY_LIGHT`

### 11. **ContextsCollector** ✅
- Использует `countBadge()`
- Использует `renderEmptyState()`

### 12. **DumpsCollector** ✅
- Использует `countBadge()`
- Использует `renderEmptyState()`

---

## 🎯 Устраненные проблемы

### ❌ Было
1. **Дублирование `formatValue()`** в 2 коллекторах (разная логика)
2. **Дублирование `getMethodColor()`** в 2 коллекторах
3. **Дублирование `getLevelColor()`** в LogsCollector
4. **Дублирование HTML рендеринга** (8+ методов только в RequestCollector)
5. **Дублирование empty state** в ~8 коллекторах
6. **Дублирование паттерна `getBadge()`** в ~7 коллекторах
7. **Хардкод цветов** повторялся 30+ раз
8. **EmailCollector** не соответствовал интерфейсу

### ✅ Стало
1. **Один метод `formatValue()`** в AbstractCollector
2. **Один метод `getMethodColor()`** через ColorPalette
3. **Централизованные цвета** через ColorPalette
4. **Переиспользуемые компоненты** через HtmlRenderer
5. **Единый паттерн** для всех коллекторов
6. **Единый интерфейс** для всех коллекторов

---

## 📊 Преимущества

### 1. **Меньше дублирования**
- Код стал более DRY (Don't Repeat Yourself)
- Сокращение на 300-400 строк

### 2. **Улучшенная поддерживаемость**
- Изменения в одном месте применяются ко всем коллекторам
- Легче добавлять новые коллекторы

### 3. **Консистентность**
- Единый стиль оформления
- Единая цветовая схема
- Единые компоненты UI

### 4. **Расширяемость**
- Легко добавить новые цвета в ColorPalette
- Легко добавить новые компоненты в HtmlRenderer
- AbstractCollector предоставляет базовый функционал

### 5. **Типобезопасность**
- Все цвета в константах (нет опечаток)
- Автодополнение в IDE

---

## 🚀 Миграция существующих коллекторов

Если у вас есть кастомные коллекторы, вот как их обновить:

### Шаг 1: Добавить use statements
```php
use Core\DebugToolbar\ColorPalette;
use Core\DebugToolbar\HtmlRenderer;
```

### Шаг 2: Заменить хардкод цветов
```php
// Было
$color = '#66bb6a';

// Стало
$color = ColorPalette::SUCCESS;
```

### Шаг 3: Использовать новые методы
```php
// Было
public function getBadge(): ?string
{
    $count = count($this->data['items'] ?? []);
    return $count > 0 ? (string)$count : null;
}

// Стало
public function getBadge(): ?string
{
    return $this->countBadge('items');
}
```

### Шаг 4: Использовать HtmlRenderer
```php
// Было
if (empty($this->data['items'])) {
    return '<div style="padding: 20px; text-align: center; color: #757575;">No items</div>';
}

// Стало
if (empty($this->data['items'])) {
    return $this->renderEmptyState('No items');
}
```

---

## 🔍 Примеры использования

### Создание нового коллектора

```php
<?php declare(strict_types=1);

namespace Core\DebugToolbar\Collectors;

use Core\DebugToolbar\AbstractCollector;
use Core\DebugToolbar\ColorPalette;
use Core\DebugToolbar\HtmlRenderer;

class MyCollector extends AbstractCollector
{
    public function __construct()
    {
        $this->priority = 80;
    }

    public function getName(): string
    {
        return 'my_collector';
    }

    public function getTitle(): string
    {
        return 'My Data';
    }

    public function getIcon(): string
    {
        return '📦';
    }

    public function collect(): void
    {
        $this->data['items'] = $this->getItems();
        $this->data['stats'] = $this->calculateStats();
    }

    public function getBadge(): ?string
    {
        return $this->countBadge('items');
    }

    public function render(): string
    {
        if (empty($this->data['items'])) {
            return $this->renderEmptyState('No items found');
        }

        $html = '<div style="padding: 20px;">';
        
        // Статистика
        $html .= '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px;">';
        $html .= HtmlRenderer::renderStatCard(
            'Total Items',
            (string)count($this->data['items']),
            ColorPalette::INFO
        );
        $html .= '</div>';
        
        // Данные
        $html .= HtmlRenderer::renderDataTable('Items', $this->data['items']);
        
        $html .= '</div>';
        return $html;
    }

    public function getHeaderStats(): array
    {
        $count = count($this->data['items'] ?? []);
        if ($count === 0) {
            return [];
        }

        return [[
            'icon' => '📦',
            'value' => $count . ' items',
            'color' => ColorPalette::SUCCESS,
        ]];
    }

    private function getItems(): array
    {
        // Ваша логика получения данных
        return [];
    }

    private function calculateStats(): array
    {
        // Ваша логика расчета статистики
        return [];
    }
}
```

---

## 🎓 Best Practices

### 1. Всегда используйте ColorPalette
```php
// ❌ Плохо
$color = '#66bb6a';

// ✅ Хорошо
$color = ColorPalette::SUCCESS;
```

### 2. Используйте HtmlRenderer для стандартных компонентов
```php
// ❌ Плохо
$html = '<div style="padding: 20px; text-align: center; color: #757575;">No data</div>';

// ✅ Хорошо
$html = $this->renderEmptyState('No data');
```

### 3. Используйте countBadge() для стандартных badge
```php
// ❌ Плохо
public function getBadge(): ?string
{
    $count = count($this->data['items'] ?? []);
    return $count > 0 ? (string)$count : null;
}

// ✅ Хорошо
public function getBadge(): ?string
{
    return $this->countBadge('items');
}
```

### 4. Используйте методы из AbstractCollector
```php
// Форматирование значений
$formatted = $this->formatValue($value);

// Цвет HTTP метода
$color = $this->getMethodColor('GET');

// Цвет по времени
$color = $this->getTimeColor($timeMs);

// Цвет уровня лога
$color = $this->getLevelColor('error');
```

---

## ✅ Checklist для создания нового коллектора

- [ ] Наследуется от `AbstractCollector`
- [ ] Использует `ColorPalette` для всех цветов
- [ ] Использует `HtmlRenderer` для стандартных компонентов
- [ ] Использует `countBadge()` если применимо
- [ ] Использует `renderEmptyState()` для пустого состояния
- [ ] Реализует все методы интерфейса `CollectorInterface`
- [ ] Использует inline стили (не Tailwind классы)
- [ ] Имеет приоритет (priority) в конструкторе
- [ ] Проверяет `isEnabled()` в методе `collect()`
- [ ] Возвращает пустой массив из `getHeaderStats()` если нет данных

---

## 📚 Дополнительные ресурсы

- [CollectorInterface](../core/DebugToolbar/CollectorInterface.php) - Интерфейс коллектора
- [AbstractCollector](../core/DebugToolbar/AbstractCollector.php) - Базовый класс
- [ColorPalette](../core/DebugToolbar/ColorPalette.php) - Цветовая палитра
- [HtmlRenderer](../core/DebugToolbar/HtmlRenderer.php) - HTML компоненты
- [Примеры коллекторов](../core/DebugToolbar/Collectors/) - Все коллекторы

---

## 🐛 Troubleshooting

### Проблема: Цвет не определяется
```php
// Убедитесь что используете правильный метод
$color = ColorPalette::getHttpMethodColor('GET');
// не
$color = ColorPalette::HTTP_GET; // это константа, не метод
```

### Проблема: Badge не отображается
```php
// Убедитесь что collect() вызван и data заполнен
public function getBadge(): ?string
{
    // data должен быть заполнен в collect()
    return $this->countBadge('items');
}
```

### Проблема: Empty state не отображается
```php
// Проверьте что возвращаете HTML строку
public function render(): string
{
    if (empty($this->data['items'])) {
        return $this->renderEmptyState('No items'); // return, не echo!
    }
    // ...
}
```

---

**Дата обновления**: 2025-10-04  
**Версия**: 1.0.0

