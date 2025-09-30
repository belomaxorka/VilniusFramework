# Debug Contexts - Группировка debug данных

## Обзор

Debug Contexts - система для организации и группировки debug информации по логическим контекстам.

### Преимущества:
- 📁 **Организация** - группируйте debug данные по категориям
- 🎨 **Визуализация** - цветовая индикация разных контекстов
- 🔍 **Фильтрация** - показывайте только нужные контексты
- 📊 **Статистика** - метрики по каждому контексту
- 🎯 **Вложенность** - поддержка вложенных контекстов

## Быстрый старт

### Базовое использование

```php
// Начать контекст
context_start('database');

// Ваш код с debug
dump($query, 'SQL Query');
timer_start('query_time');
// execute query
timer_stop('query_time');

// Закончить контекст
context_end('database');

// Вывести все контексты
context_dump();
```

**Вывод:**
```
📁 Debug Contexts

🗄️ Database                                     12.45ms
┌─────────────────────────────────────────────────┐
│ Items: 2                                        │
│ ┌─────────────────────────────────────────────┐ │
│ │ dump: SQL Query                             │ │
│ │ timer: query_time                           │ │
│ └─────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────┘
```

### Context Run (автоматический)

```php
context_run('api', function() {
    // Весь код внутри будет в контексте 'api'
    dump($request, 'API Request');
    $response = makeApiCall();
    dump($response, 'API Response');
});
// Контекст автоматически закрывается
```

## Предустановленные контексты

Система включает готовые контексты с иконками и цветами:

| Контекст | Иконка | Цвет | Назначение |
|----------|--------|------|------------|
| **database** | 🗄️ | Синий | Запросы к БД |
| **cache** | 💾 | Оранжевый | Операции с кешем |
| **api** | 🌐 | Зеленый | API запросы |
| **queue** | 📬 | Фиолетовый | Работа с очередями |
| **email** | 📧 | Красный | Отправка email |
| **security** | 🔒 | Темно-красный | Безопасность |
| **performance** | ⚡ | Голубой | Производительность |
| **validation** | ✓ | Розовый | Валидация |
| **business** | 💼 | Индиго | Бизнес-логика |
| **general** | 📝 | Серый | Общий контекст |

### Использование предустановленных:

```php
context_run('database', function() {
    dump($sql, 'Query');
});

context_run('cache', function() {
    dump($cacheKey, 'Cache Key');
});
```

## API Reference

### Основные функции

#### context_start(string $name, ?array $config = null)
Начинает новый контекст.

```php
// Предустановленный контекст
context_start('database');

// Кастомный контекст
context_start('my_context', [
    'color' => '#ff6b6b',
    'icon' => '🔥',
    'label' => 'My Custom Context'
]);
```

#### context_end(?string $name = null)
Заканчивает контекст.

```php
context_end('database'); // конкретный контекст
context_end();           // текущий контекст
```

#### context_run(string $name, callable $callback, ?array $config = null): mixed
Выполняет код в контексте (автоматически закрывает).

```php
$result = context_run('api', function() {
    return callApi();
});
```

#### context_dump(?array $contexts = null)
Выводит контексты.

```php
context_dump();              // все контексты
context_dump(['database']);  // только database
```

#### context_clear(?string $name = null)
Очищает контексты.

```php
context_clear('database');  // один контекст
context_clear();            // все контексты
```

#### context_current(): ?string
Получает текущий активный контекст.

```php
$current = context_current();
echo "Current context: $current";
```

#### context_filter(array $contexts)
Включает фильтрацию - показывает только указанные контексты.

```php
// Показывать только database и cache
context_filter(['database', 'cache']);

context_dump(); // выведет только database и cache
```

## Продвинутое использование

### Вложенные контексты

```php
context_start('api');

dump($request, 'API Request');

// Вложенный контекст
context_run('database', function() {
    dump($query, 'Query from API');
    // execute query
});

// Вернулись в контекст api
dump($response, 'API Response');

context_end('api');
```

### Автоматическое добавление в контекст

Все debug функции автоматически добавляются в текущий контекст:

```php
context_start('database');

dump($query);              // -> добавится в 'database'
timer_start('db');         // -> добавится в 'database'
memory_snapshot('before'); // -> добавится в 'database'

context_end('database');
```

### Кастомные контексты

```php
// Регистрация кастомного preset
DebugContext::register('payment', [
    'color' => '#4caf50',
    'icon' => '💳',
    'label' => 'Payment Processing'
]);

// Использование
context_run('payment', function() {
    dump($transaction, 'Transaction');
});
```

### Фильтрация контекстов

```php
// Создаем разные контексты
context_run('database', fn() => dump('DB query'));
context_run('cache', fn() => dump('Cache hit'));
context_run('api', fn() => dump('API call'));

// Показываем только database и api
context_filter(['database', 'api']);
context_dump();

// Отключаем фильтр
DebugContext::disableFilter();
context_dump(); // все контексты
```

## Класс DebugContext

Для прямого использования:

```php
use Core\DebugContext;

// Старт контекста
DebugContext::start('name', $config);

// Добавить элемент
DebugContext::add('type', 'data');
DebugContext::add('query', $sql, 'database'); // в конкретный контекст

// Получить контекст
$context = DebugContext::get('database');

// Проверить существование
if (DebugContext::exists('database')) {
    // контекст существует
}

// Текущий контекст
$current = DebugContext::current();

// Все контексты
$all = DebugContext::getAll();

// Статистика
$stats = DebugContext::getStats();
// ['database' => ['items' => 5, 'duration' => 12.5, 'label' => 'Database']]

// Количество
$count = DebugContext::count();

// Фильтрация
DebugContext::enableFilter(['database', 'cache']);
DebugContext::disableFilter();
DebugContext::isEnabled('database'); // true/false

// Presets
$presets = DebugContext::getPresets();
DebugContext::register('custom', $config);

// Dump
DebugContext::dump();
DebugContext::dump(['database']);

// Очистка
DebugContext::clear();
DebugContext::clear('database');
```

## Примеры использования

### Пример 1: API Controller

```php
class ApiController 
{
    public function handleRequest($request) 
    {
        return context_run('api', function() use ($request) {
            
            // Валидация
            context_run('validation', function() use ($request) {
                dump($request->all(), 'Request Data');
                $validated = $this->validate($request);
            });
            
            // База данных
            context_run('database', function() use ($validated) {
                dump($validated, 'Validated Data');
                $data = $this->fetchData($validated);
            });
            
            // Кеш
            context_run('cache', function() use ($data) {
                $cached = $this->cacheResult($data);
            });
            
            context_dump();
            
            return $this->response($data);
        });
    }
}
```

### Пример 2: Service Layer

```php
class UserService 
{
    public function createUser(array $data) 
    {
        context_start('business');
        
        dump($data, 'User Data');
        
        // Валидация
        context_run('validation', function() use ($data) {
            $this->validateUserData($data);
        });
        
        // База данных
        context_run('database', function() use ($data) {
            timer_measure('create_user', function() use ($data) {
                $user = User::create($data);
            });
        });
        
        // Email
        context_run('email', function() use ($user) {
            $this->sendWelcomeEmail($user);
        });
        
        context_end('business');
        context_dump();
        
        return $user;
    }
}
```

### Пример 3: Background Job

```php
class ImportJob 
{
    public function handle() 
    {
        context_run('queue', function() {
            
            memory_start();
            timer_start('import');
            
            dump('Starting import', 'Status');
            
            foreach ($this->batches as $batch) {
                context_run('database', function() use ($batch) {
                    memory_measure('batch', function() use ($batch) {
                        $this->importBatch($batch);
                    });
                });
                
                memory_snapshot('after_batch');
            }
            
            timer_stop('import');
            memory_dump();
            
            context_dump();
        });
    }
}
```

### Пример 4: Debugging Complex Flow

```php
context_start('performance');

// Step 1: Load data
context_run('database', function() {
    timer_measure('load_users', function() {
        $users = User::with('posts')->get();
    });
    dump(count($users), 'Users loaded');
});

// Step 2: Process
context_run('business', function() use ($users) {
    memory_measure('process', function() use ($users) {
        $processed = processUsers($users);
    });
});

// Step 3: Cache
context_run('cache', function() use ($processed) {
    Cache::put('users', $processed, 3600);
    dump('Cached', 'Status');
});

context_end('performance');

// Показать только performance и database
context_filter(['performance', 'database']);
context_dump();
```

## Интеграция с другими инструментами

### С Timer Profiler

```php
context_run('api', function() {
    timer_start('api_call');
    
    $response = makeApiCall();
    
    timer_stop('api_call');
    timer_dump('api_call');
});

context_dump();
// Покажет и таймер и контекст
```

### С Memory Profiler

```php
context_run('database', function() {
    memory_start();
    
    $data = loadHugeDataset();
    memory_snapshot('loaded');
    
    processData($data);
    memory_snapshot('processed');
    
    memory_dump();
});

context_dump();
```

### Комбинированный пример

```php
context_run('performance', function() {
    timer_start('total');
    memory_start();
    
    context_run('database', function() {
        timer_measure('query', fn() => executeQuery());
        memory_snapshot('after_query');
    });
    
    context_run('cache', function() {
        timer_measure('cache', fn() => saveToCache());
        memory_snapshot('after_cache');
    });
    
    timer_stop('total');
    
    // Вывести всё
    memory_dump();
    timer_dump('total');
    context_dump();
});
```

## Советы и Best Practices

### 1. Используйте предустановленные контексты

```php
// ✅ Хорошо
context_run('database', fn() => executeQuery());

// ❌ Не нужно
context_run('db', fn() => executeQuery());
```

### 2. Закрывайте контексты

```php
// ✅ Хорошо - автоматически закрывается
context_run('api', function() {
    // код
});

// ⚠️ Требует ручного закрытия
context_start('api');
// код
context_end('api');
```

### 3. Используйте вложенность для детализации

```php
context_run('business', function() {
    // Бизнес-логика
    
    context_run('database', function() {
        // DB операции внутри бизнес-логики
    });
});
```

### 4. Фильтруйте для фокуса

```php
// При отладке конкретной части
context_filter(['database', 'cache']);
context_dump();
```

### 5. Регистрируйте свои контексты

```php
// В bootstrap или service provider
DebugContext::register('payment', [
    'color' => '#4caf50',
    'icon' => '💳',
    'label' => 'Payment'
]);
```

## Production Mode

В production режиме контексты **отключены**:

```php
// В production
context_start('test');  // ничего не делает
context_run('test', function() {
    return 'result'; // выполняется, но контекст не создается
});

// НО dump, timer, memory всё равно отключены
dump($data); // не выведет
```

## Troubleshooting

### Контексты не отображаются

**Проблема:** `context_dump()` ничего не показывает

**Решение:**
```php
// 1. Проверьте режим
var_dump(Environment::isDevelopment()); // true?

// 2. Проверьте что контексты созданы
var_dump(DebugContext::count()); // > 0?

// 3. Проверьте фильтр
DebugContext::disableFilter();
context_dump();
```

### Вложенные контексты не работают

**Проблема:** Контексты не вкладываются правильно

**Решение:**
```php
// Используйте context_run для автоматического управления
context_run('outer', function() {
    context_run('inner', function() {
        // безопасно
    });
});
```

### Items не попадают в контекст

**Проблема:** dump() не добавляется в контекст

**Решение:**
```php
// Убедитесь что контекст активен
context_start('test');
dump($data); // теперь попадет в test
```

## FAQ

**Q: Можно ли использовать один контекст несколько раз?**

A: Да, контекст можно открывать многократно:
```php
context_start('database');
// код
context_end('database');

// позже
context_start('database'); // тот же контекст
```

**Q: Что происходит с вложенными контекстами?**

A: Они сохраняются в стеке и восстанавливаются при закрытии:
```php
context_start('outer');
context_start('inner');
context_end(); // вернется к outer
```

**Q: Как узнать текущий контекст?**

A: `context_current()` или `DebugContext::current()`

**Q: Можно ли добавить item в закрытый контекст?**

A: Да, укажите имя контекста:
```php
DebugContext::add('data', $value, 'closed_context');
```

**Q: Сколько контекстов можно создать?**

A: Неограниченно, но для удобства рекомендуется до 10-15 на запрос.

## Заключение

Debug Contexts - мощный инструмент для:

- ✅ Организации debug информации
- ✅ Группировки по логическим категориям
- ✅ Визуального разделения данных
- ✅ Фильтрации и фокусировки
- ✅ Анализа сложных флоу

Используйте контексты для структурированной отладки! 📁🚀
