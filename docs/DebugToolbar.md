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

### Базовое использование

Добавьте в конец вашего layout/template:

```php
<!-- В конце body, перед </body> -->
<?= render_debug_toolbar() ?>
```

**Готово!** Toolbar автоматически появится внизу страницы.

### Что вы увидите

**Панель показывает:**
- ⏱️ **Время выполнения** - общее время запроса
- 💾 **Память** - текущее и пиковое использование
- 🗄️ **SQL запросы** - количество и время
- 📁 **Контексты** - активные debug контексты
- 🔍 **Dumps** - количество debug выводов

### Вкладки

1. **📊 Overview** - общая статистика и метрики
2. **🔍 Dumps** - все dump() и dump_pretty() выводы
3. **🗄️ Queries** - SQL запросы с временем выполнения
4. **⏱️ Timers** - измерения времени
5. **💾 Memory** - профиль памяти
6. **📁 Contexts** - debug контексты

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

### В шаблонах (Templates)

Если используете свой шаблонизатор (не TemplateEngine):

```php
<!-- layout.php -->
<!DOCTYPE html>
<html>
<head>
    <title><?= $title ?></title>
</head>
<body>
    <?= $content ?>
    
    <!-- Debug Toolbar в самом конце -->
    <?= render_debug_toolbar() ?>
</body>
</html>
```

### Программная установка

```php
use Core\DebugToolbar;

// В конце вашего скрипта
echo DebugToolbar::render();
```

### С автоматическим выводом

```php
// В bootstrap или Core::init()
\Core\Debug::registerShutdownHandler();

// Toolbar автоматически отобразится в конце
```

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

A: Сейчас нельзя, но можно не использовать соответствующий инструмент (напр. не вызывать query_log).

**Q: Toolbar конфликтует с другими панелями?**

A: Используйте setPosition() чтобы разместить в удобном месте.

## Заключение

Debug Toolbar - центральный инструмент отладки, объединяющий:

- ✅ Все debug данные в одном месте
- ✅ Красивый и удобный интерфейс
- ✅ Интерактивные элементы
- ✅ Автоматический сбор метрик
- ✅ Легкую интеграцию

Используйте Debug Toolbar для эффективной разработки и отладки! 🐛🎨
