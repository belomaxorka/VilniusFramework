# Debug Toolbar - Визуальная панель отладки

## Обзор

Debug Toolbar - интерактивная панель отладки, объединяющая все инструменты debug системы в едином визуальном интерфейсе.

### Возможности:
- 📊 **Статистика в реальном времени** - время, память, запросы
- 📑 **Вкладки** - организованный доступ к разным типам debug данных
- 🎨 **Современный UI** - красивый интерфейс с темной темой
- 🔄 **Интерактивность** - сворачивание, переключение вкладок
- 📱 **Адаптивность** - работает на всех устройствах
- 🚀 **Легкая интеграция** - одна строка кода

## Быстрый старт

### Автоматическая работа

Debug Toolbar автоматически добавляется на все HTML страницы через `DebugToolbarMiddleware`.

**Ничего делать не нужно!** Toolbar автоматически появится внизу страницы в development режиме.

### Что вы увидите

**Панель показывает:**
- ⏱️ **Время выполнения** - общее время запроса
- 💾 **Память** - текущее и пиковое использование
- 🗄️ **SQL запросы** - количество и время
- 📁 **Контексты** - активные debug контексты
- 🔍 **Dumps** - количество debug выводов

### Вкладки

1. **📥 Request** - данные входящего запроса
2. **📤 Response** - данные исходящего ответа
3. **🛣️ Routes** - информация о маршрутизации
4. **🔍 Dumps** - все dump() и dump_pretty() выводы
5. **🗄️ Queries** - SQL запросы с временем выполнения
6. **🗃️ Cache** - операции с кэшем
7. **🎨 Templates** - отрендеренные шаблоны
8. **⏱️ Timers** - время выполнения запроса
9. **📝 Logs** - логи приложения
10. **💾 Memory** - профиль памяти
11. **📁 Contexts** - debug контексты

## Использование

### Автоматическая интеграция

Debug Toolbar автоматически интегрируется через `TemplateEngine::display()`:

```php
// В контроллере
public function index() 
{
    dump($data, 'User Data'); // Будет в toolbar
    dump_pretty($config, 'Config'); // Будет в toolbar
    
    return view('index', compact('data'));
    // Toolbar автоматически добавится перед </body>
}
```

**По умолчанию:** все вызовы `dump()`, `dd()`, и т.д. собираются только для toolbar и НЕ выводятся на странице.

### Как это работает

Debug Toolbar автоматически инъектируется через `DebugToolbarMiddleware`:

1. **Middleware перехватывает вывод** через output buffering
2. **Проверяет условия**: debug режим, HTML Content-Type, наличие `</body>`
3. **Инъектирует toolbar** перед закрывающим тегом `</body>`

```php
// В контроллере - просто возвращайте response
public function index(): Response
{
    return $this->view('home');
    // Toolbar добавится автоматически!
}
```

### Ручная инъекция (не рекомендуется)

Если по каким-то причинам вам нужно вручную добавить toolbar:

```php
use Core\DebugToolbar;

echo DebugToolbar::render();
```

**Примечание:** В 99% случаев автоматическая инъекция через middleware - правильное решение.

## Настройка

### Включение/выключение

```php
use Core\DebugToolbar;

// Отключить toolbar
DebugToolbar::enable(false);

// Включить обратно
DebugToolbar::enable(true);
```

### Позиция (top | bottom)

```php
// Вверху страницы
DebugToolbar::setPosition('top');

// Внизу страницы (по умолчанию)
DebugToolbar::setPosition('bottom');
```

### Свернуть по умолчанию

```php
// Toolbar будет свернут при загрузке
DebugToolbar::setCollapsed(true);

// Развернут (по умолчанию)
DebugToolbar::setCollapsed(false);
```

### Управление выводом на странице

```php
use Core\Debug;

// По умолчанию: dumps только в toolbar
dump($data); // Только в toolbar

// Включить вывод на странице И в toolbar
Debug::setRenderOnPage(true);
// или
debug_render_on_page(true);

dump($data); // Теперь и на странице, и в toolbar

// Отключить обратно
Debug::setRenderOnPage(false);
```

## Интерактивность

### Сворачивание/разворачивание

Кликните на заголовок toolbar чтобы свернуть/развернуть панель.

### Переключение вкладок

Кликните на вкладку чтобы переключиться на соответствующую панель.

### Бейджи

Вкладки с данными показывают количество элементов в badge:
- 🔍 Dumps **[5]** - 5 dump выводов
- 🗄️ Queries **[12]** - 12 SQL запросов
- 📁 Contexts **[3]** - 3 контекста

## Вкладки в деталях

### 📊 Overview

Общая статистика запроса:

```
⚡ Performance
- Total Time: 125.45ms
- Query Time: 75.20ms

💾 Memory  
- Current: 8.5 MB
- Peak: 12.3 MB

🗄️ Database
- Queries: 15
- Slow: 2

🐛 Debug
- Dumps: 5
- Contexts: 3
```

### 🔍 Dumps

Показывает все `dump()` и `dump_pretty()` выводы:

```php
dump($user, 'User Data');
dump_pretty($config, 'Config');

// Отобразится в Dumps вкладке
```

### 🗄️ Queries

SQL запросы с деталями:

- Номер запроса
- SQL с подсветкой синтаксиса
- Время выполнения (цветовая индикация)
- Количество строк
- Bindings
- Источник (файл:строка)

**Цвета:**
- 🟢 Зеленый - быстрый запрос
- 🔴 Красный - медленный запрос

### ⏱️ Timers

Информация о таймерах (используйте `timer_dump()` для вывода).

### 💾 Memory

Профиль памяти (используйте `memory_dump()` для вывода).

### 📁 Contexts

Активные debug контексты с:
- Иконкой и названием
- Цветовым индикатором
- Количеством items

## API Reference

### DebugToolbar::render(): string
Рендерит toolbar HTML.

```php
$html = DebugToolbar::render();
echo $html;
```

### DebugToolbar::enable(bool $enabled = true): void
Включает/выключает toolbar.

```php
DebugToolbar::enable(false);
```

### DebugToolbar::setPosition(string $position): void
Устанавливает позицию ('top' | 'bottom').

```php
DebugToolbar::setPosition('top');
```

### DebugToolbar::setCollapsed(bool $collapsed): void
Устанавливает начальное состояние (свернут/развернут).

```php
DebugToolbar::setCollapsed(true);
```

### render_debug_toolbar(): string
Helper функция для рендеринга.

```php
echo render_debug_toolbar();
```

## Примеры

### Пример 1: Базовая интеграция

```php
// layout/main.php
<!DOCTYPE html>
<html>
<head>
    <title>My App</title>
</head>
<body>
    <header>...</header>
    <main><?= $content ?></main>
    <footer>...</footer>
    
    <?= render_debug_toolbar() ?>
</body>
</html>
```

### Пример 2: С настройками

```php
// bootstrap.php
use Core\DebugToolbar;

if (is_dev()) {
    DebugToolbar::enable(true);
    DebugToolbar::setPosition('bottom');
    DebugToolbar::setCollapsed(false);
}
```

### Пример 3: Условный вывод

```php
// В template
<?php if (is_dev()): ?>
    <?= render_debug_toolbar() ?>
<?php endif; ?>
```

### Пример 4: Анализ страницы

```php
// Controller
public function index() 
{
    // Debug запросы
    context_run('database', function() {
        $users = query_measure(fn() => User::all(), 'Load Users');
        $posts = query_measure(fn() => Post::all(), 'Load Posts');
    });
    
    // Debug память
    memory_snapshot('before_render');
    $view = view('index', compact('users', 'posts'));
    memory_snapshot('after_render');
    
    // Все данные автоматически в toolbar!
    return $view;
}
```

**Toolbar покажет:**
- Общее время запроса
- 2 SQL запроса в вкладке Queries
- 2 memory snapshots
- Database context

### Пример 5: Performance Monitoring

```php
context_run('performance', function() {
    timer_start('total');
    memory_start();
    
    // Ваш код
    processHeavyTask();
    
    timer_stop('total');
    timer_dump();
    memory_dump();
});

// Toolbar автоматически покажет:
// - Время выполнения в Timers
// - Использование памяти в Memory
// - Контекст performance в Contexts
```

## Визуальные индикаторы

### Цветовая схема

**Время выполнения:**
- 🟢 < 1000ms - нормально
- 🔴 > 1000ms - медленно

**Память:**
- 🟢 < 50% лимита - нормально
- 🟠 50-75% лимита - предупреждение
- 🔴 > 75% лимита - критично

**SQL запросы:**
- 🟢 Нет медленных - хорошо
- 🔴 Есть медленные - нужна оптимизация

### Предупреждения

Toolbar показывает предупреждения в заголовке:

```
🗄️ 15 queries (3 slow)  ← ⚠️ Медленные запросы
```

## Расширяемая система коллекторов

Debug Toolbar теперь поддерживает **расширяемую архитектуру коллекторов**, которая позволяет легко добавлять новые вкладки и функциональность.

### Встроенные коллекторы

- **RequestCollector** - данные входящего запроса
- **ResponseCollector** - данные исходящего ответа
- **RoutesCollector** - информация о маршрутизации
- **DumpsCollector** - дебаг дампы
- **QueriesCollector** - SQL запросы
- **CacheCollector** - операции с кэшем
- **TemplatesCollector** - отрендеренные шаблоны и undefined переменные
- **TimersCollector** - таймеры и время выполнения
- **LogsCollector** - логи приложения (debug, info, warning, error, critical)
- **MemoryCollector** - использование памяти
- **ContextsCollector** - контексты отладки

### Создание своего коллектора

Вы можете легко создать свой коллектор для отображения специфичных данных вашего приложения:

```php
<?php

namespace Core\DebugToolbar\Collectors;

use Core\DebugToolbar\AbstractCollector;

class MyCollector extends AbstractCollector
{
    private static array $data = [];
    
    public function __construct()
    {
        $this->priority = 45; // Порядок отображения
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
        // HTML для вкладки
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

    // Метод для сбора данных
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

// В bootstrap или config
DebugToolbar::addCollector(new MyCollector());

// Использование
MyCollector::log('Some data');
MyCollector::log('Another data');

// Данные автоматически появятся в новой вкладке!
```

### Пример: Cache Collector

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

    public function set(string $key, $value, int $ttl = 3600)
    {
        $start = microtime(true);
        // ... сохраняем в кэш
        $time = (microtime(true) - $start) * 1000;
        
        CacheCollector::logWrite($key, $value, $time);
    }
}

// Toolbar автоматически покажет:
// - 🗃️ Cache вкладку с операциями
// - Статистику hits/misses
// - Hit rate
```

### Управление коллекторами

```php
// Добавить коллектор
DebugToolbar::addCollector(new MyCollector());

// Получить коллектор
$collector = DebugToolbar::getCollector('cache');

// Удалить коллектор
DebugToolbar::removeCollector('cache');

// Включить/выключить
$collector->setEnabled(false);

// Изменить приоритет (порядок вкладок)
$collector->setPriority(15);
```

📖 **Полная документация:** См. [DebugToolbarCollectors.md](./DebugToolbarCollectors.md) для детального руководства по созданию коллекторов.

## Интеграция с другими инструментами

### С Query Debugger

```php
query_log('SELECT * FROM users', [], 25.0, 100);
query_log('SELECT * FROM posts', [], 15.0, 50);

// Toolbar автоматически покажет в Queries вкладке
```

### С Debug Contexts

```php
context_run('api', function() {
    dump('API Request');
});

// Toolbar покажет:
// - 1 dump в Dumps
// - 1 context в Contexts
```

### С Timers и Memory

```php
timer_start('task');
memory_start();

// ... выполнение

timer_stop('task');
timer_dump(); // → отобразится в Timers вкладке

memory_dump(); // → отобразится в Memory вкладке
```

### Комплексный пример

```php
context_run('request', function() {
    timer_start('total');
    memory_start();
    
    // Database
    context_run('database', function() {
        $data = query_measure(fn() => loadData(), 'Load Data');
    });
    
    // Processing
    context_run('business', function() use ($data) {
        dump($data, 'Loaded Data');
        processData($data);
        memory_snapshot('after_process');
    });
    
    timer_stop('total');
    memory_dump();
});

// Toolbar покажет ВСЕ:
// - Overview: время, память, запросы
// - Dumps: загруженные данные
// - Queries: SQL запрос
// - Timers: время выполнения
// - Memory: профиль памяти
// - Contexts: request, database, business
```

## Советы и Best Practices

### 1. Всегда добавляйте toolbar в layout

```php
<!-- В базовом layout -->
<?= render_debug_toolbar() ?>
```

### 2. Используйте только в development

```php
// Автоматически отключается в production
Environment::set(Environment::PRODUCTION);
render_debug_toolbar(); // вернет ''
```

### 3. Комбинируйте с контекстами

```php
context_run('page_load', function() {
    // весь код страницы
});

// Toolbar покажет контекст с метриками
```

### 4. Анализируйте SQL в Queries вкладке

Проверяйте:
- Количество запросов
- Медленные запросы (красным)
- Дублирующиеся паттерны (N+1)

### 5. Мониторьте память в Overview

Следите за:
- Peak Memory
- Процент от лимита
- Резкие скачки (memory leaks)

## Production Mode

В production режиме Toolbar **полностью отключен**:

```php
// В production
Environment::set(Environment::PRODUCTION);

render_debug_toolbar(); // вернет ''
DebugToolbar::render();  // вернет ''
```

Это гарантирует:
- ⚡ Ноль оверхеда
- 🔒 Безопасность (нет утечки данных)
- 🎨 Чистый интерфейс

## Troubleshooting

### Toolbar не отображается

**Проблема:** Toolbar не виден на странице

**Решение:**
```php
// 1. Проверьте режим
var_dump(Environment::isDevelopment()); // true?

// 2. Проверьте что включен
DebugToolbar::enable(true);

// 3. Проверьте вызов
echo render_debug_toolbar();
```

### Toolbar перекрывает контент

**Проблема:** Toolbar закрывает часть страницы

**Решение:**
```php
// Переместите наверх
DebugToolbar::setPosition('top');

// Или сверните по умолчанию
DebugToolbar::setCollapsed(true);
```

### Вкладки пустые

**Проблема:** В вкладках "No data"

**Решение:**
```php
// Убедитесь что используете debug функции ДО рендера toolbar

dump($data);           // ДО
query_log(...);        // ДО
render_debug_toolbar(); // ПОСЛЕ
```

### JavaScript не работает

**Проблема:** Клики не работают

**Решение:**
Убедитесь что toolbar рендерится полностью (со скриптами):
```php
// НЕ экранируйте вывод!
<?= render_debug_toolbar() ?> // ✅

<?= htmlspecialchars(render_debug_toolbar()) ?> // ❌
```

## FAQ

**Q: Могу ли я кастомизировать внешний вид?**

A: Да, toolbar генерирует inline styles. Вы можете добавить CSS после рендера:
```php
<?= render_debug_toolbar() ?>
<style>
#debug-toolbar { /* ваши стили */ }
</style>
```

**Q: Toolbar работает с AJAX?**

A: Toolbar показывает данные текущего запроса. Для AJAX используйте отдельный вывод или Dump Server.

**Q: Можно ли использовать на API endpoints?**

A: Не рекомендуется. Для API лучше использовать логирование или отдельный debug endpoint.

**Q: Toolbar замедляет приложение?**

A: Минимально, только в dev режиме. В production полностью отключен (0 оверхеда).

**Q: Как скрыть конкретную вкладку?**

A: Используйте систему коллекторов:
```php
// Отключить коллектор
$collector = DebugToolbar::getCollector('queries');
$collector->setEnabled(false);

// Или удалить полностью
DebugToolbar::removeCollector('queries');
```

**Q: Toolbar конфликтует с другими панелями?**

A: Используйте setPosition() чтобы разместить в удобном месте.

**Q: Могу ли я добавить свою вкладку в toolbar?**

A: Да! Используйте систему коллекторов. См. [DebugToolbarCollectors.md](./DebugToolbarCollectors.md) для подробностей.

## Заключение

Debug Toolbar - центральный инструмент отладки, объединяющий:

- ✅ Все debug данные в одном месте
- ✅ Красивый и удобный интерфейс
- ✅ Интерактивные элементы
- ✅ Автоматический сбор метрик
- ✅ Легкую интеграцию

Используйте Debug Toolbar для эффективной разработки и отладки! 🐛🎨
