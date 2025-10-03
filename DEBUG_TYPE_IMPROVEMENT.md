# ✨ Улучшение: Типизация debug выводов

## Что сделано

Добавлен параметр `$type` в метод `Debug::addOutput()` для классификации выводов.

---

## Изменения

### Сигнатура метода

**Было:**
```php
public static function addOutput(string $output, ?string $label = null, ?string $rawText = null): void
```

**Стало:**
```php
public static function addOutput(string $output, string $type = 'custom', ?string $label = null, ?string $rawText = null): void
```

---

## Доступные типы

| Тип | Используется | Описание |
|-----|-------------|----------|
| `dump` | `Debug::dump()` | Простой дамп переменной |
| `dump_pretty` | `Debug::dumpPretty()` | Красивый дамп с подсветкой |
| `trace` | `Debug::trace()` | Stack trace (backtrace) |
| `dump_all` | `Debug::dumpAll()` | Коллекция собранных данных |
| `custom` | По умолчанию | Кастомный вывод |

---

## Использование

### В методах Debug

```php
// trace() передает свой тип
Debug::addOutput($output, 'trace', $label, $rawText);

// dumpAll() передает свой тип
Debug::addOutput($output, 'dump_all', 'Debug Collection', $rawText);
```

### В пользовательском коде

```php
// Простой вызов (тип = 'custom')
Debug::addOutput('<div>My debug</div>');

// С указанием типа
Debug::addOutput($html, 'my_type', 'My Label', $text);

// Со всеми параметрами
Debug::addOutput(
    $htmlOutput,      // HTML для браузера
    'error',          // Тип
    'Critical Error', // Метка для логов
    $textOutput       // Текст для логов
);
```

---

## Преимущества

### 1. Классификация
Теперь каждый вывод имеет тип:
```php
[
    'type' => 'trace',  // ← Знаем, что это trace
    'output' => '...',
    'die' => false
]
```

### 2. Фильтрация (будущее)
Можно будет фильтровать по типам:
```php
// Не показывать trace в продакшене
if ($type === 'trace' && !Environment::isDebug()) {
    return;
}

// Показывать только ошибки
if (!in_array($type, ['error', 'critical'])) {
    return;
}
```

### 3. Разная обработка (будущее)
Разные типы - разная обработка:
```php
match ($type) {
    'trace' => Logger::debug($rawText),
    'dump' => Logger::info($rawText),
    'error' => Logger::error($rawText),
    'critical' => $this->sendToSentry($rawText),
    default => Logger::debug($rawText)
};
```

### 4. Статистика (будущее)
Можно собирать статистику:
```php
// Сколько было trace вызовов?
// Сколько dump'ов?
// Есть ли critical ошибки?
```

---

## Обратная совместимость

✅ **Полностью совместимо!**

Старый код продолжит работать:
```php
// Тип будет 'custom' по умолчанию
Debug::addOutput('<div>Old code</div>');
```

Новый код может использовать типы:
```php
// Явно указываем тип
Debug::addOutput('<div>New code</div>', 'my_type');
```

---

## Примеры

### Кастомный error вывод

```php
function debugError(string $message, \Throwable $e): void
{
    $html = sprintf(
        '<div style="background: #ff0000; color: white; padding: 10px;">
            <strong>Error:</strong> %s<br>
            <small>%s</small>
        </div>',
        htmlspecialchars($message),
        htmlspecialchars($e->getMessage())
    );
    
    $text = sprintf(
        "Error: %s\nException: %s\nFile: %s:%d",
        $message,
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    );
    
    Debug::addOutput($html, 'error', 'Error Handler', $text);
}
```

### Кастомный performance вывод

```php
function debugPerformance(float $time, string $operation): void
{
    $html = sprintf(
        '<div style="background: %s; padding: 10px;">
            ⏱️ %s: %.2fms
        </div>',
        $time > 100 ? '#ff9800' : '#4caf50',
        htmlspecialchars($operation),
        $time
    );
    
    $text = sprintf("%s took %.2fms", $operation, $time);
    
    Debug::addOutput($html, 'performance', 'Performance', $text);
}
```

---

## Файлы изменены

### Код
1. ✅ `core/Debug.php` - параметр `$type` в `addOutput()`
2. ✅ `core/Debug.php` - `trace()` передает `'trace'`
3. ✅ `core/Debug.php` - `dumpAll()` передает `'dump_all'`

### Документация
4. ✅ `docs/DebugAPI.md` - обновлена сигнатура `addOutput()`
5. ✅ `docs/DebugAPI.md` - добавлена секция "Типы вывода"
6. ✅ `docs/DebugArchitectureImprovement.md` - обновлены примеры
7. ✅ `DEBUG_TYPE_IMPROVEMENT.md` - этот файл

---

## Итоги

✅ **Типизация добавлена** - каждый вывод имеет тип  
✅ **Обратная совместимость** - старый код работает  
✅ **Расширяемость** - легко добавлять новые типы  
✅ **Гибкость** - можно применять разную обработку  
✅ **Документировано** - все описано в документации  

**Debug стал еще лучше! ✨**

---

_Улучшено: 2025-10-03_  
_Версия: 1.1_

