# 🏗️ Улучшение архитектуры Debug

## Проблема: Дублирование кода

### Было

В каждом методе Debug класса дублировалась логика:

```php
public static function trace(?string $label = null): void
{
    // ... формирование вывода ...
    
    if (Environment::isDebug()) {
        // Добавляем в буфер для вывода
        self::$debugOutput[] = [...];
    } else {
        // Логируем в файл
        Logger::debug($output);
    }
}

public static function dumpAll(bool $die = false): void
{
    // ... формирование вывода ...
    
    if (Environment::isDebug()) {
        self::addOutput($output);
    } else {
        Logger::debug($output);
    }
}
```

**Проблемы:**
- ❌ Дублирование логики проверки debug режима
- ❌ Дублирование логики логирования
- ❌ Каждый метод решает сам, куда отправлять данные
- ❌ Сложнее поддерживать - изменения нужно вносить везде

---

## Решение: Централизация в addOutput()

### Стало

Метод `addOutput()` стал универсальным - он сам решает, куда отправить данные:

```php
/**
 * Добавить вывод в буфер или залогировать
 * 
 * @param string $output HTML вывод для браузера
 * @param string $type Тип вывода (dump, trace, collect, etc.)
 * @param string|null $label Метка для логирования
 * @param string|null $rawText Текстовая версия для логов
 */
public static function addOutput(string $output, string $type = 'custom', ?string $label = null, ?string $rawText = null): void
{
    if (!Environment::isDebug()) {
        // В продакшене логируем
        if ($rawText !== null) {
            $logOutput = $label ? "[{$label}] {$rawText}" : $rawText;
            Logger::debug($logOutput);
        }
        return;
    }

    // В debug режиме добавляем в буфер для вывода
    self::$debugOutput[] = [
        'type' => $type,
        'output' => $output,
        'die' => false
    ];
}
```

---

## Как теперь работает

### trace() - упрощен

```php
public static function trace(?string $label = null): void
{
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    array_shift($backtrace);
    
    // Формируем текстовую версию для логов
    $rawText = "Backtrace:\n";
    foreach ($backtrace as $index => $trace) {
        // ... формирование текста ...
    }
    
    // Формируем HTML для браузера
    $output = '<div>...</div>';
    
    // Используем универсальный addOutput - он сам решит, куда отправить
    self::addOutput($output, 'trace', $label, $rawText);
}
```

**Преимущества:**
- ✅ Логика проверки только в одном месте
- ✅ Проще код метода
- ✅ HTML и текст формируются отдельно

### dumpAll() - тоже упрощен

```php
public static function dumpAll(bool $die = false): void
{
    // ... формирование HTML ...
    $output .= '</div>';
    
    // Формируем текстовую версию для логов
    $rawText = "Debug Collection:\n";
    foreach (self::$debugData as $index => $item) {
        // ... формирование текста ...
    }

    // Используем универсальный addOutput
    self::addOutput($output, 'dump_all', 'Debug Collection', $rawText);
    
    // ...
}
```

---

## Архитектура

### Поток данных

```
┌─────────────────┐
│  Debug::trace() │
│  Debug::dump()  │
│ Debug::dumpAll()│
└────────┬────────┘
         │
         │ Формируют:
         │ - HTML для браузера
         │ - Текст для логов
         │ - Label
         │
         v
┌────────────────────┐
│ Debug::addOutput() │◄───── Единая точка принятия решения
└────────┬───────────┘
         │
         │ Проверяет Environment::isDebug()
         │
    ┌────┴────┐
    │         │
    v         v
┌─────┐   ┌────────┐
│Debug│   │  Prod  │
│ on  │   │  off   │
└──┬──┘   └───┬────┘
   │          │
   v          v
┌──────┐   ┌──────┐
│Buffer│   │Logger│
│ для  │   │ файл │
│вывода│   │      │
└──────┘   └──────┘
```

---

## Преимущества нового подхода

### 1. Single Responsibility
Каждый метод отвечает только за свою задачу:
- `trace()` - формирует backtrace
- `dump()` - формирует дамп переменной
- `addOutput()` - решает, куда отправить

### 2. DRY (Don't Repeat Yourself)
Логика проверки и логирования в одном месте.

### 3. Легче тестировать
Можно тестировать формирование данных отдельно от логики вывода.

### 4. Легче расширять
Добавление нового канала вывода (например, в Sentry) - изменения только в `addOutput()`.

### 5. Унификация
Все методы работают одинаково:
```php
// Формируем данные
$html = '...';
$text = '...';
$type = 'trace'; // или 'dump', 'dump_all', etc.
$label = '...';

// Отправляем
self::addOutput($html, $type, $label, $text);
```

### 6. Типизация выводов
Каждый вывод имеет свой тип, что позволяет:
- Фильтровать по типам
- Различать виды вывода
- Применять разную обработку

---

## Использование

### В коде фреймворка

```php
// trace() - автоматически логируется в prod
Debug::trace('Current Location');

// dumpAll() - автоматически логируется в prod
Debug::collect($data1, 'Data 1');
Debug::collect($data2, 'Data 2');
Debug::dumpAll();

// Кастомный вывод
$html = '<div>Custom HTML</div>';
$text = 'Custom text for logs';
Debug::addOutput($html, 'custom', 'Custom', $text);
```

### В развитии (dev)
- Все выводится в браузер
- Красивое HTML форматирование
- Debug Toolbar

### В продакшене (prod)
- Все логируется в файлы
- Текстовый формат в логах
- Безопасно - не выводит на страницу

---

## Миграция

### Старый код (если где-то есть)

```php
if (Environment::isDebug()) {
    self::$debugOutput[] = [
        'type' => 'custom',
        'output' => $output,
        'die' => false
    ];
} else {
    Logger::debug($output);
}
```

### Новый код

```php
self::addOutput($htmlOutput, 'trace', $label, $textOutput);
```

---

## Планы на будущее

С такой архитектурой легко добавить:

1. **Разные уровни логирования**
   ```php
   self::addOutput($html, $label, $text, 'error');
   ```

2. **Разные каналы вывода**
   ```php
   // В config
   'debug' => [
       'channels' => ['browser', 'file', 'sentry']
   ]
   ```

3. **Фильтрация по типам**
   ```php
   // Уже реализовано! Теперь можно:
   
   // Не логировать определенные типы в продакшене
   if (in_array($type, ['trace', 'dump']) && !Environment::isDebug()) {
       return;
   }
   
   // Или применять разную обработку
   match ($type) {
       'trace' => Logger::debug($rawText),
       'dump' => Logger::info($rawText),
       'error' => Logger::error($rawText),
       default => Logger::debug($rawText)
   };
   ```

---

## Итоги

✅ **Код стал чище** - нет дублирования  
✅ **Легче поддерживать** - изменения в одном месте  
✅ **Легче тестировать** - разделение ответственности  
✅ **Легче расширять** - единая точка входа  
✅ **Унифицировано** - все методы работают одинаково  

**Архитектура Debug стала лучше! 🏗️✨**

---

_Улучшено: 2025-10-03_

