# Исправление системы приоритетов Debug Toolbar

## Проблема

Первоначальная реализация имела **контр-интуитивную систему приоритетов**:
- Меньшее число (10) = отображается ПЕРВЫМ
- Большее число (100) = отображается ПОСЛЕДНИМ

Это противоречит естественному восприятию, где:
- **Высокий приоритет** должен означать "более важный"
- **Более важный** должен отображаться первым (левее)

## Решение

Изменена логика сортировки на **естественную**:
- **Больше число = выше приоритет = отображается первым (левее)**
- Меньше число = ниже приоритет = отображается позже (правее)

## Изменения в коде

### 1. Сортировка (DebugToolbar.php)

**Было:**
```php
uasort($collectors, fn($a, $b) => $a->getPriority() <=> $b->getPriority());
// 10 < 50 < 100 → 10 первый
```

**Стало:**
```php
uasort($collectors, fn($a, $b) => $b->getPriority() <=> $a->getPriority());
// 100 > 50 > 10 → 100 первый
```

### 2. Приоритеты коллекторов

| Коллектор         | Было | Стало | Позиция       |
|-------------------|------|-------|---------------|
| OverviewCollector | 10   | 100   | 1-й (первый)  |
| DumpsCollector    | 20   | 90    | 2-й           |
| QueriesCollector  | 30   | 80    | 3-й           |
| CacheCollector    | 35   | 75    | 4-й           |
| TimersCollector   | 40   | 70    | 5-й           |
| MemoryCollector   | 50   | 60    | 6-й           |
| ContextsCollector | 60   | 50    | 7-й (последний)|

### 3. Обновлена документация

- `core/DebugToolbar/README.md`
- `docs/DebugToolbarCollectors.md`
- `docs/DebugToolbarUpgrade.md`
- `docs/examples/CustomCollectorExample.php`

### 4. Обновлены тесты

- `tests/Unit/Core/DebugToolbarPriorityTest.php`

## Как использовать

### Создание коллектора с высоким приоритетом (отображается первым)

```php
class ImportantCollector extends AbstractCollector
{
    public function __construct()
    {
        $this->priority = 95; // Высокий приоритет, между Dumps (90) и Overview (100)
    }
}
```

### Создание коллектора с низким приоритетом (отображается последним)

```php
class LessImportantCollector extends AbstractCollector
{
    public function __construct()
    {
        $this->priority = 30; // Низкий приоритет, отобразится после Contexts (50)
    }
}
```

## Правило запоминания

**📊 Чем БОЛЬШЕ число, тем ВАЖНЕЕ коллектор, тем РАНЬШЕ (ЛЕВЕЕ) он отображается**

- 100 = самый важный, первый слева
- 50 = средний приоритет, в середине  
- 10 = низкий приоритет, последний справа

## Примеры порядка

### Пример 1: Стандартные коллекторы

```
100 (Overview) | 90 (Dumps) | 80 (Queries) | 75 (Cache) | 70 (Timers) | 60 (Memory) | 50 (Contexts)
   ← первый                                                                              последний →
```

### Пример 2: С кастомным коллектором

```php
// Ваш коллектор с приоритетом 85
class MyCollector { 
    priority = 85; 
}

// Порядок:
100 (Overview) | 90 (Dumps) | 85 (My) | 80 (Queries) | ...
```

## Обратная совместимость

⚠️ **ВНИМАНИЕ:** Это breaking change для тех, кто уже создал свои коллекторы!

Если вы создали кастомные коллекторы с старой логикой приоритетов, вам нужно **инвертировать** их значения:

```php
// Было (старая логика): приоритет 15 = отображается рано
class MyCollector {
    priority = 15;
}

// Стало (новая логика): нужно поменять на 85
class MyCollector {
    priority = 85; // (100 - 15 = 85) или выбрать подходящее значение
}
```

## Проверка корректности

Запустите тесты:

```bash
vendor/bin/pest tests/Unit/Core/DebugToolbarPriorityTest.php
```

Все 3 теста должны пройти успешно:
- ✅ collectors are sorted by priority (higher = earlier)
- ✅ header stats are sorted by priority (higher = earlier)  
- ✅ priority can be changed dynamically

## Дата изменения

30 сентября 2025
