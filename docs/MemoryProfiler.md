# Memory Profiler - Отслеживание использования памяти

## Обзор

Memory Profiler - инструмент для мониторинга и анализа использования памяти в вашем приложении.

### Возможности:
- 💾 **Snapshots** - снимки памяти в разных точках кода
- 📊 **Tracking** - отслеживание роста/уменьшения памяти
- 🔝 **Peak Memory** - пиковое использование памяти
- 📈 **Visualization** - визуализация с прогресс-баром
- 🎯 **Measure** - измерение памяти для функций
- 🚨 **Alerts** - предупреждения при превышении лимитов

## Быстрый старт

### Базовое профилирование

```php
// Начать профилирование
memory_start();

// Ваш код
$users = loadUsers();
memory_snapshot('users_loaded', 'After loading users');

$processed = processUsers($users);
memory_snapshot('users_processed', 'After processing');

// Вывести результаты
memory_dump();
```

**Вывод:**
```
💾 Memory Profile
Current Memory: 8.45 MB
Peak Memory: 12.32 MB
Memory Limit: 128.00 MB
[████████░░░░░░░░░░░░] 9.6%

Memory Snapshots:
┌───┬─────────────────┬──────────────────┬──────────┬──────────┬────────────┐
│ # │ Name            │ Label            │ Memory   │ Diff     │ Total Diff │
├───┼─────────────────┼──────────────────┼──────────┼──────────┼────────────┤
│ 1 │ start           │ Started          │ 2.50 MB  │ 0 B      │ 0 B        │
│ 2 │ users_loaded    │ After loading... │ 6.20 MB  │ +3.70 MB │ +3.70 MB   │
│ 3 │ users_processed │ After process... │ 8.45 MB  │ +2.25 MB │ +5.95 MB   │
└───┴─────────────────┴──────────────────┴──────────┴──────────┴────────────┘
```

### Measure (автоматическое измерение)

```php
$result = memory_measure('load_data', function() {
    return Database::query('SELECT * FROM large_table');
});

// Автоматически покажет: 💾 Memory: load_data +2.5 MB
```

## API Reference

### Основные функции

#### memory_start()
Начинает профилирование памяти. Создает начальный snapshot.

```php
memory_start();
```

#### memory_snapshot(string $name, ?string $label = null): array
Создает снимок текущего состояния памяти.

```php
$snapshot = memory_snapshot('checkpoint', 'Important checkpoint');

// Возвращает массив с информацией:
// [
//     'name' => 'checkpoint',
//     'label' => 'Important checkpoint',
//     'memory' => 8388608,        // текущая память в байтах
//     'peak' => 10485760,         // пиковая память
//     'diff' => 2097152,          // разница с предыдущим snapshot
//     'diff_from_start' => 5242880, // разница от начала
//     'timestamp' => 1234567890.123
// ]
```

#### memory_current(): int
Получает текущее использование памяти в байтах.

```php
$current = memory_current();
echo "Using: " . memory_format($current);
// Using: 8.45 MB
```

#### memory_peak(): int
Получает пиковое использование памяти в байтах.

```php
$peak = memory_peak();
echo "Peak: " . memory_format($peak);
// Peak: 12.32 MB
```

#### memory_dump()
Выводит полный профиль памяти со всеми snapshots.

```php
memory_dump();
```

#### memory_clear()
Очищает все snapshots.

```php
memory_clear();
```

#### memory_measure(string $name, callable $callback): mixed
Измеряет использование памяти callback функцией.

```php
$data = memory_measure('fetch_users', function() {
    return User::all();
});
```

#### memory_format(int $bytes, int $precision = 2): string
Форматирует байты в читаемый вид (B, KB, MB, GB).

```php
echo memory_format(1024);        // 1.00 KB
echo memory_format(1048576);     // 1.00 MB
echo memory_format(1073741824);  // 1.00 GB
echo memory_format(1536, 1);     // 1.5 KB
```

## Продвинутое использование

### Отслеживание утечек памяти

```php
memory_start();

for ($i = 0; $i < 100; $i++) {
    processItem($i);
    
    if ($i % 10 === 0) {
        memory_snapshot("iteration_$i", "Iteration $i");
    }
}

memory_dump();
// Если видите постоянный рост - возможна утечка!
```

### Сравнение производительности

```php
memory_start();

// Вариант 1
memory_snapshot('before_v1');
$result1 = implementationA($data);
memory_snapshot('after_v1', 'Implementation A');

// Очищаем
unset($result1);
gc_collect_cycles();

// Вариант 2
memory_snapshot('before_v2');
$result2 = implementationB($data);
memory_snapshot('after_v2', 'Implementation B');

memory_dump();
// Сравните diff между вариантами
```

### Профилирование батч-обработки

```php
memory_start();

$batches = array_chunk($data, 1000);

foreach ($batches as $index => $batch) {
    memory_measure("batch_$index", function() use ($batch) {
        processBatch($batch);
    });
    
    memory_snapshot("batch_{$index}_done", "Batch $index complete");
    
    // Проверка лимита
    if (MemoryProfiler::isThresholdExceeded(80)) {
        echo "Memory threshold exceeded! Stopping.";
        break;
    }
}

memory_dump();
```

### Детальный анализ операций

```php
memory_start();

// Загрузка данных
memory_snapshot('before_load');
$data = loadLargeDataset();
memory_snapshot('after_load', 'Data loaded');

// Обработка
memory_snapshot('before_process');
$processed = processData($data);
memory_snapshot('after_process', 'Data processed');

// Сохранение
memory_snapshot('before_save');
saveResults($processed);
memory_snapshot('after_save', 'Results saved');

// Освобождение
unset($data, $processed);
gc_collect_cycles();
memory_snapshot('after_cleanup', 'Memory cleaned');

memory_dump();
```

## Класс MemoryProfiler

Для прямого использования класса:

```php
use Core\MemoryProfiler;

// Старт
MemoryProfiler::start();

// Snapshot
$snapshot = MemoryProfiler::snapshot('name', 'label');

// Текущая память
$current = MemoryProfiler::current();

// Пиковая память
$peak = MemoryProfiler::peak();

// Все snapshots
$snapshots = MemoryProfiler::getSnapshots();

// Количество snapshots
$count = MemoryProfiler::count();

// Лимит памяти
$limit = MemoryProfiler::getMemoryLimit();

// Процент использования
$percentage = MemoryProfiler::getUsagePercentage();

// Проверка порога
if (MemoryProfiler::isThresholdExceeded(80)) {
    // Используется > 80% лимита
}

// Форматирование
$formatted = MemoryProfiler::formatBytes(1048576);

// Measure
$result = MemoryProfiler::measure('operation', fn() => code());

// Вывод
MemoryProfiler::dump();

// Очистка
MemoryProfiler::clear();
```

## Интеграция с другими инструментами

### С Timer Profiler

```php
timer_start('full_process');
memory_start();

// Этап 1
timer_lap('full_process', 'Stage 1 start');
memory_snapshot('stage1_start');

processStage1();

timer_lap('full_process', 'Stage 1 done');
memory_snapshot('stage1_done', 'Stage 1 complete');

// Этап 2
timer_lap('full_process', 'Stage 2 start');
memory_snapshot('stage2_start');

processStage2();

timer_lap('full_process', 'Stage 2 done');
memory_snapshot('stage2_done', 'Stage 2 complete');

timer_stop('full_process');

// Вывод обоих профилей
memory_dump();
timer_dump('full_process');
```

### С Debug dump

```php
memory_start();

$data = loadData();
memory_snapshot('data_loaded');
dump($data, 'Loaded Data');

$result = process($data);
memory_snapshot('data_processed');
dump($result, 'Processed Result');

memory_dump();
// Весь вывод появится вместе
```

## Примеры использования

### Пример 1: API Endpoint Profiling

```php
class ApiController 
{
    public function getData(Request $request) 
    {
        memory_start();
        
        // Валидация
        $validated = $request->validate($rules);
        memory_snapshot('validated', 'Request validated');
        
        // Запрос в БД
        $data = memory_measure('database', function() use ($validated) {
            return Database::query($validated);
        });
        
        // Обработка
        $processed = memory_measure('processing', function() use ($data) {
            return processData($data);
        });
        
        // Форматирование
        $response = memory_measure('formatting', function() use ($processed) {
            return formatResponse($processed);
        });
        
        memory_dump();
        
        return $response;
    }
}
```

### Пример 2: Import Performance

```php
function importLargeFile($filepath) 
{
    memory_start();
    
    $file = fopen($filepath, 'r');
    memory_snapshot('file_opened', 'File opened');
    
    $imported = 0;
    $batch = [];
    
    while (($line = fgets($file)) !== false) {
        $batch[] = parseLine($line);
        
        if (count($batch) >= 1000) {
            memory_measure("import_batch_$imported", function() use ($batch) {
                importBatch($batch);
            });
            
            $batch = [];
            $imported++;
            
            memory_snapshot("batch_$imported", "Imported $imported batches");
            
            // Проверка памяти
            if (MemoryProfiler::getUsagePercentage() > 75) {
                echo "Memory usage high, taking a break...\n";
                gc_collect_cycles();
                sleep(1);
            }
        }
    }
    
    fclose($file);
    memory_snapshot('completed', 'Import completed');
    memory_dump();
}
```

### Пример 3: Image Processing

```php
function processImages(array $images) 
{
    memory_start();
    
    foreach ($images as $index => $imagePath) {
        $result = memory_measure("image_$index", function() use ($imagePath) {
            $img = loadImage($imagePath);
            $resized = resizeImage($img, 800, 600);
            $optimized = optimizeImage($resized);
            saveImage($optimized);
            
            // Освобождаем память
            unset($img, $resized, $optimized);
            
            return true;
        });
        
        if ($index % 10 === 0) {
            memory_snapshot("images_$index", "Processed $index images");
            gc_collect_cycles();
        }
    }
    
    memory_dump();
}
```

## Оптимизация на основе профилирования

### До оптимизации:
```php
memory_start();

$allUsers = User::all(); // 50k записей
memory_snapshot('loaded', 'All users loaded');
// Memory: +45 MB

$filtered = array_filter($allUsers, fn($u) => $u->active);
memory_snapshot('filtered', 'Users filtered');
// Memory: +22 MB (дубликат данных!)

memory_dump();
// Total: +67 MB
```

### После оптимизации:
```php
memory_start();

$activeUsers = User::where('active', true)->get(); // только нужные
memory_snapshot('loaded', 'Active users loaded');
// Memory: +22 MB

memory_dump();
// Total: +22 MB (экономия 45 MB!)
```

## Советы и Best Practices

### 1. Всегда начинайте с memory_start()

```php
// ✅ Хорошо
memory_start();
memory_snapshot('checkpoint');

// ❌ Плохо
memory_snapshot('checkpoint'); // нет базового snapshot
```

### 2. Используйте понятные метки

```php
// ✅ Хорошо
memory_snapshot('users_loaded', 'After loading 10k users from DB');
memory_snapshot('users_processed', 'After email validation');

// ❌ Плохо
memory_snapshot('s1');
memory_snapshot('s2');
```

### 3. Освобождайте память когда возможно

```php
memory_snapshot('before');

$largeData = processHugeFile();
memory_snapshot('after_process');

// Освобождаем
unset($largeData);
gc_collect_cycles();
memory_snapshot('after_cleanup');
```

### 4. Проверяйте пороги

```php
if (MemoryProfiler::isThresholdExceeded(80)) {
    Logger::warning('Memory usage high: ' . memory_format(memory_current()));
    gc_collect_cycles(); // принудительная сборка мусора
}
```

### 5. Используйте measure для изолированных операций

```php
// Автоматически покажет использование памяти
memory_measure('load_config', fn() => loadConfig());
memory_measure('init_cache', fn() => initializeCache());
```

## Production Mode

В production режиме профилирование **отключено**:

```php
// В production
memory_start();         // ничего не делает
memory_snapshot('test'); // ничего не делает
memory_dump();          // ничего не делает

// НО эти функции работают всегда:
memory_current();  // текущая память
memory_peak();     // пиковая память
memory_format();   // форматирование
```

Это сделано для:
- ⚡ Нулевой оверхед в production
- 🔒 Безопасность (не раскрывает внутреннюю информацию)
- 📊 Возможность получить базовую статистику

## Troubleshooting

### Профиль не отображается

**Проблема:** `memory_dump()` ничего не выводит

**Решение:**
```php
// 1. Проверьте режим
var_dump(Environment::isDevelopment()); // должно быть true

// 2. Проверьте что есть snapshots
var_dump(MemoryProfiler::count()); // > 0

// 3. Вызовите flush
memory_dump();
debug_flush();
```

### Неожиданное использование памяти

**Проблема:** Память растет больше ожидаемого

**Решение:**
1. Проверьте циклические ссылки
2. Вызовите `gc_collect_cycles()`
3. Используйте `unset()` для больших переменных
4. Проверьте буферизацию вывода

### Memory limit превышен

**Проблема:** Fatal error: Allowed memory size exhausted

**Решение:**
```php
// Увеличьте лимит в php.ini
memory_limit = 256M

// Или в коде (если разрешено)
ini_set('memory_limit', '256M');

// Проверьте текущий лимит
echo memory_format(MemoryProfiler::getMemoryLimit());
```

## FAQ

**Q: Какой оверхед у профилирования?**

A: Минимальный (~0.001-0.01ms на snapshot). В production полностью отключено.

**Q: Как часто делать snapshots?**

A: В критичных местах: перед/после больших операций, в циклах каждые N итераций.

**Q: Что значит "diff" в snapshot?**

A: Разница с **предыдущим** snapshot (может быть + или -)

**Q: Что значит "diff_from_start"?**

A: Общий прирост/уменьшение от **начала** профилирования

**Q: Когда вызывать gc_collect_cycles()?**

A: После освобождения больших объемов памяти или при превышении порога.

**Q: Работает ли в CLI скриптах?**

A: Да! Идеально для long-running процессов и крон-задач.

## Заключение

Memory Profiler - незаменимый инструмент для:

- ✅ Поиска утечек памяти
- ✅ Оптимизации производительности
- ✅ Анализа потребления ресурсов
- ✅ Отладки медленных операций
- ✅ Мониторинга production приложений

Используйте его для создания эффективных приложений! 💾🚀
