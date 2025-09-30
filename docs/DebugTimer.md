# Debug Timer - Измерение времени выполнения

## Обзор

Debug Timer - это мощная система для точного измерения времени выполнения различных участков кода с поддержкой:

- ⏱️ **Множественные таймеры** - запускайте несколько независимых таймеров одновременно
- 📊 **Lap Times** - промежуточные замеры времени
- 🎯 **Микросекундная точность** - точность до микросекунд
- 📈 **Автоматический вывод** - красивое отображение результатов
- 🚀 **Простой API** - удобные helper функции

## Базовое использование

### Простой таймер

```php
// Запуск таймера
timer_start('operation');

// Ваш код
performOperation();

// Остановка и вывод
timer_stop('operation');
timer_dump('operation');
```

**Вывод:**
```
⏱️ Timer: operation (Stopped)
Total Time: 45.23ms
```

### Таймер с автоматическим выводом

```php
timer_measure('database', function() {
    return Database::query('SELECT * FROM users');
});
```

Автоматически выведет время выполнения после завершения.

## API Reference

### Основные функции

#### timer_start(string $name = 'default')
Запускает таймер с указанным именем.

```php
timer_start('api_call');
timer_start('db_query');
timer_start(); // использует 'default'
```

#### timer_stop(string $name = 'default'): float
Останавливает таймер и возвращает прошедшее время в миллисекундах.

```php
timer_start('task');
// код
$elapsed = timer_stop('task');
echo "Task took: {$elapsed}ms";
```

#### timer_lap(string $name = 'default', ?string $label = null): float
Делает промежуточный замер времени без остановки таймера.

```php
timer_start('process');

timer_lap('process', 'Step 1');
// код шага 1

timer_lap('process', 'Step 2');
// код шага 2

timer_stop('process');
```

#### timer_elapsed(string $name = 'default'): float
Получает текущее время таймера без остановки.

```php
timer_start('long_task');

// Проверяем время во время выполнения
if (timer_elapsed('long_task') > 1000) {
    echo "Task is taking too long!";
}
```

#### timer_dump(?string $name = null)
Выводит информацию о таймере(ах).

```php
timer_dump('specific');  // один таймер
timer_dump();            // все таймеры
```

#### timer_clear(?string $name = null)
Очищает таймер(ы).

```php
timer_clear('old');  // один таймер
timer_clear();       // все таймеры
```

#### timer_measure(string $name, callable $callback): mixed
Измеряет время выполнения функции и автоматически выводит результат.

```php
$result = timer_measure('calculation', function() {
    return heavyCalculation();
});
```

## Продвинутое использование

### Множественные таймеры

Запускайте несколько независимых таймеров одновременно:

```php
timer_start('total');
timer_start('database');

$users = User::all();

timer_stop('database');
timer_start('processing');

processUsers($users);

timer_stop('processing');
timer_start('rendering');

renderView($users);

timer_stop('rendering');
timer_stop('total');

// Вывести все таймеры
timer_dump();
```

**Вывод:**
```
⏱️ Timer: total (Stopped)
Total Time: 152.45ms

⏱️ Timer: database (Stopped)
Total Time: 45.32ms

⏱️ Timer: processing (Stopped)
Total Time: 78.91ms

⏱️ Timer: rendering (Stopped)
Total Time: 28.22ms
```

### Lap Times (промежуточные замеры)

Отслеживайте прогресс длительных операций:

```php
timer_start('batch_import');

foreach ($batches as $i => $batch) {
    processBatch($batch);
    timer_lap('batch_import', "Batch " . ($i + 1));
}

timer_stop('batch_import');
timer_dump('batch_import');
```

**Вывод:**
```
⏱️ Timer: batch_import (Stopped)
Total Time: 234.56ms

Lap Times:
┌─────┬──────────┬──────────┬──────────┐
│ Lap │ Label    │ Time     │ Interval │
├─────┼──────────┼──────────┼──────────┤
│ #1  │ Batch 1  │ 45.23ms  │ +45.23ms │
│ #2  │ Batch 2  │ 89.45ms  │ +44.22ms │
│ #3  │ Batch 3  │ 135.78ms │ +46.33ms │
│ #4  │ Batch 4  │ 189.12ms │ +53.34ms │
│ #5  │ Batch 5  │ 234.56ms │ +45.44ms │
└─────┴──────────┴──────────┴──────────┘
```

### Вложенные таймеры

```php
timer_start('controller');

timer_measure('validation', function() use ($request) {
    return validateRequest($request);
});

timer_measure('business_logic', function() {
    return executeBusinessLogic();
});

timer_measure('response', function() use ($data) {
    return formatResponse($data);
});

timer_stop('controller');
timer_dump();
```

### Проверка времени во время выполнения

```php
timer_start('import');

foreach ($items as $item) {
    processItem($item);
    
    // Прерываем если слишком долго
    if (timer_elapsed('import') > 5000) {
        echo "Timeout reached, stopping import";
        break;
    }
}

timer_stop('import');
```

## Примеры использования

### Пример 1: API Performance Monitoring

```php
class ApiController 
{
    public function handleRequest($request) 
    {
        timer_start('api_request');
        
        timer_lap('api_request', 'Request received');
        
        $validated = $this->validate($request);
        timer_lap('api_request', 'Validation complete');
        
        $result = $this->processRequest($validated);
        timer_lap('api_request', 'Processing complete');
        
        $response = $this->formatResponse($result);
        timer_lap('api_request', 'Response formatted');
        
        timer_stop('api_request');
        timer_dump('api_request');
        
        return $response;
    }
}
```

### Пример 2: Database Query Profiling

```php
function getUsersWithPosts() 
{
    timer_measure('query:users', function() {
        return Database::query('SELECT * FROM users');
    });
    
    timer_measure('query:posts', function() {
        return Database::query('SELECT * FROM posts');
    });
    
    timer_measure('merge_data', function() use ($users, $posts) {
        return mergeUsersWithPosts($users, $posts);
    });
    
    timer_dump(); // все таймеры
}
```

### Пример 3: Batch Processing

```php
timer_start('batch_process');

$batches = array_chunk($data, 100);

foreach ($batches as $index => $batch) {
    timer_measure("batch_{$index}", function() use ($batch) {
        return processBatch($batch);
    });
    
    timer_lap('batch_process', "Completed batch {$index}");
}

timer_stop('batch_process');
timer_dump('batch_process');
```

### Пример 4: Сравнение производительности

```php
// Вариант 1
timer_measure('approach_1', function() {
    return implementationA();
});

// Вариант 2
timer_measure('approach_2', function() {
    return implementationB();
});

timer_dump(); // сравните результаты
```

## Интеграция с Debug системой

Timer автоматически интегрируется с остальной debug системой:

```php
timer_start('operation');

dump($data, 'Input data');

timer_lap('operation', 'After dump');

dump_pretty($result, 'Result');

timer_stop('operation');

// Весь вывод (dump + timer) появится вместе
timer_dump('operation');
```

## Класс DebugTimer

Для прямого использования класса:

```php
use Core\DebugTimer;

// Запуск
DebugTimer::start('timer_name');

// Lap
DebugTimer::lap('timer_name', 'Checkpoint');

// Получить время
$elapsed = DebugTimer::getElapsed('timer_name');

// Остановка
DebugTimer::stop('timer_name');

// Проверка статуса
if (DebugTimer::isRunning('timer_name')) {
    // timer работает
}

// Получить все таймеры
$all = DebugTimer::getAll();

// Количество таймеров
$count = DebugTimer::count();

// Measure
$result = DebugTimer::measure('name', fn() => code());
```

## Production Mode

В production режиме таймеры автоматически отключаются:

```php
// В production
timer_start('task');
// ... код ...
timer_stop('task'); // вернет 0.0

// Но measure все равно выполнит код
$result = timer_measure('task', fn() => code()); // работает, но без вывода
```

## Точность и производительность

### Точность
- **Микросекундная точность** через `microtime(true)`
- Точность до 0.001ms (1 микросекунда)

### Производительность
- Минимальный оверхед (~0.01ms на операцию)
- Оптимизировано для production (полностью отключается)

```php
// Тест оверхеда
timer_start('test');
// пустой код
timer_stop('test'); // ~0.01ms
```

## Советы и Best Practices

### 1. Используйте понятные имена

```php
// ❌ Плохо
timer_start('t1');
timer_start('x');

// ✅ Хорошо
timer_start('database_query');
timer_start('image_processing');
```

### 2. Группируйте связанные операции

```php
timer_start('user_registration');

timer_lap('user_registration', 'Validation');
timer_lap('user_registration', 'Create User');
timer_lap('user_registration', 'Send Email');

timer_stop('user_registration');
```

### 3. Используйте measure для изолированных операций

```php
// ✅ Хорошо
timer_measure('send_email', fn() => sendEmail($user));
timer_measure('log_activity', fn() => logActivity($user));
```

### 4. Очищайте таймеры после использования

```php
timer_start('temp');
// код
timer_dump('temp');
timer_clear('temp'); // освобождаем память
```

### 5. Проверяйте время для оптимизации

```php
timer_start('query');
$result = expensiveQuery();
$time = timer_stop('query');

if ($time > 100) {
    Logger::warning("Slow query detected: {$time}ms");
}
```

## Troubleshooting

### Таймер не выводится

**Проблема:** `timer_dump()` ничего не показывает

**Решение:**
1. Убедитесь что в development mode:
```php
var_dump(Environment::isDevelopment()); // должно быть true
```

2. Проверьте что таймер был создан:
```php
var_dump(DebugTimer::count()); // > 0
```

3. Вызовите `debug_flush()` в конце:
```php
timer_dump();
debug_flush();
```

### Неточные измерения

**Проблема:** Время выполнения кажется неправильным

**Решение:**
1. Учитывайте оверхед системы (~0.01ms)
2. Используйте несколько измерений для усреднения
3. Отключите XDebug для точных измерений

### Таймер не останавливается

**Проблема:** `isRunning()` всегда true

**Решение:**
```php
// Убедитесь что вызвали stop()
timer_start('task');
// код
timer_stop('task'); // не забывайте!

// Или используйте measure (автоматически останавливает)
timer_measure('task', fn() => code());
```

## Сравнение с benchmark()

### benchmark()
- Измеряет время **callback функции**
- Автоматически выводит результат
- Простой API

```php
benchmark(fn() => code(), 'Label');
```

### timer_*()
- Измеряет время **между точками кода**
- Поддержка lap times
- Множественные таймеры
- Больше контроля

```php
timer_start('complex');
// много кода
timer_lap('complex', 'Step 1');
// еще код
timer_stop('complex');
```

**Рекомендация:** Используйте `benchmark()` для простых случаев, `timer_*()` для сложных сценариев.

## FAQ

**Q: Можно ли использовать один таймер несколько раз?**

A: Да, просто перезапустите его:
```php
timer_start('reusable');
timer_stop('reusable');
// позже
timer_start('reusable'); // перезапуск
```

**Q: Сколько таймеров можно запустить одновременно?**

A: Неограниченно! Но рекомендуется держать разумное количество для читаемости.

**Q: Lap times сбрасываются при перезапуске?**

A: Да, при новом `start()` все lap times теряются.

**Q: Можно ли получить lap times программно?**

A: Да:
```php
$all = DebugTimer::getAll();
$laps = $all['timer_name']['laps'];
```

**Q: Работает ли в production?**

A: Технически да, но весь вывод отключается. Используйте для измерений в dev режиме.

## Заключение

Debug Timer - мощный инструмент для:

- ✅ Профилирования производительности
- ✅ Оптимизации узких мест
- ✅ Мониторинга времени выполнения
- ✅ Отладки медленных операций
- ✅ Сравнения разных подходов

Используйте его для создания быстрых и эффективных приложений! 🚀
