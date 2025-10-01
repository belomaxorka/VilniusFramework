# Templates Collector - Коллектор отрендеренных шаблонов

## Обзор

**TemplatesCollector** отображает информацию обо всех отрендеренных шаблонах во время текущего запроса.

## Возможности

### 📊 Что показывает:
- Список всех отрендеренных шаблонов
- Время рендеринга каждого шаблона
- Использование памяти
- Размер output
- Список переданных переменных
- **Undefined переменные** ⚠️
- Использование кэша

### 🎨 Визуализация:
- **Статистика** - общая информация
- **Undefined Variables** - предупреждения
- **Детальная информация** по каждому шаблону
- **Цветовая кодировка** по времени рендеринга

## Использование

### Базовый рендеринг

```php
use Core\TemplateEngine;

$engine = TemplateEngine::getInstance();

// Рендеринг шаблона
$engine->render('welcome.twig', [
    'title' => 'Welcome',
    'user' => $userData,
    'posts' => $posts
]);
```

### С display()

```php
// Рендеринг и вывод
$engine->display('welcome.twig', [
    'title' => 'Welcome',
    'message' => 'Hello World'
]);
```

## Визуальное отображение

### Статистика
```
┌────────────┬─────────────┬──────────────┬────────────────┐
│ TEMPLATES  │ TOTAL TIME  │ OUTPUT SIZE  │ FROM CACHE     │
│     3      │   18.5 ms   │    15.4 KB   │     67%        │
└────────────┴─────────────┴──────────────┴────────────────┘
```

### Undefined Variables (если есть)
```
⚠️ Undefined Variables (2)

$username (3 times)
$email (1 times)
```

### Детальная информация о шаблоне
```
📄 welcome.twig                           [5.2 ms] [🗃️ CACHED]

Variables: 5    Memory: 2.1 KB    Size: 8.3 KB

View Variables (5) ▼
  title, message, user, posts, meta
```

## Цветовая кодировка времени

- 🟢 **< 20ms** - быстро, зеленый
- 🟠 **20-50ms** - средне, оранжевый
- 🔴 **> 50ms** - медленно, красный

## Undefined Variables

Templates Collector автоматически отслеживает undefined переменные в шаблонах:

```tpl
<!-- welcome.twig -->
<h1>{{ title }}</h1>
<p>Hello, {{ username }}</p>  <!-- username не передан! -->
```

В toolbar вы увидите предупреждение:
```
⚠️ Undefined Variables (1)
$username (1 times)
```

## Header Stats

В заголовке toolbar:
- **При undefined vars:** `🎨 2 undefined vars` (оранжевый)
- **Без проблем:** `🎨 3 templates (18.5ms)` (зеленый/оранжевый/красный)

## Badge

На вкладке:
- Количество undefined переменных (приоритет)
- Количество шаблонов (если нет undefined)

## API

### TemplateEngine::getRenderedTemplates()
```php
$templates = TemplateEngine::getRenderedTemplates();
// [
//     [
//         'template' => 'welcome.twig',
//         'path' => '/path/to/welcome.twig',
//         'variables' => ['title', 'message'],
//         'variables_count' => 2,
//         'time' => 15.2,  // ms
//         'memory' => 2048,  // bytes
//         'size' => 8192,  // bytes
//         'from_cache' => true
//     ],
//     ...
// ]
```

### TemplateEngine::getRenderStats()
```php
$stats = TemplateEngine::getRenderStats();
// [
//     'total' => 3,
//     'total_time' => 45.5,  // ms
//     'total_memory' => 6144,
//     'total_size' => 24576,
//     'from_cache' => 2,
//     'compiled' => 1
// ]
```

### TemplateEngine::getUndefinedVars()
```php
$undefined = TemplateEngine::getUndefinedVars();
// [
//     'username' => [
//         'count' => 3,
//         'message' => 'Undefined variable $username',
//         'file' => 'welcome.twig',
//         'line' => 15
//     ]
// ]
```

## Примеры использования

### Оптимизация рендеринга

Используйте Templates Collector для поиска медленных шаблонов:

```php
// Если шаблон медленный (> 50ms), оптимизируйте:
// 1. Используйте кэш
// 2. Упростите логику в шаблоне
// 3. Минимизируйте количество циклов
```

### Отладка undefined переменных

```php
// В контроллере
$engine->display('user-profile.twig', [
    'user' => $user,
    'posts' => $posts,
    // 'stats' => $stats  // Забыли передать!
]);

// Templates Collector покажет:
// ⚠️ Undefined Variables (1): $stats
```

### Мониторинг кэша

Следите за процентом использования кэша:
- **< 50%** - плохо, кэш не используется
- **50-80%** - нормально
- **> 80%** - отлично, большинство шаблонов из кэша

## Интеграция

TemplatesCollector автоматически регистрируется в Debug Toolbar.

Приоритет: **72** (между Cache и Timers)

## Производительность

- ✅ Минимальное влияние (~0.5-1ms overhead)
- ✅ Данные собираются только в debug режиме
- ✅ Не влияет на production

## Кэширование

TemplateEngine кэширует скомпилированные шаблоны:

```php
// Включить кэш (по умолчанию включен)
$engine->setCacheEnabled(true);

// Установить время жизни
$engine->setCacheLifetime(3600); // 1 час

// Очистить кэш
$engine->clearCache();
```

## Советы

1. **Следите за undefined переменными:**
   - Всегда передавайте все необходимые переменные
   - Используйте default values в шаблонах

2. **Оптимизируйте медленные шаблоны:**
   - Выносите сложную логику в контроллер
   - Минимизируйте вложенные циклы
   - Используйте кэш

3. **Проверяйте размер output:**
   - Большие шаблоны (> 100KB) - кандидаты на оптимизацию
   - Рассмотрите pagination или lazy loading

4. **Используйте кэш:**
   - Включайте кэш в production
   - Очищайте кэш при изменении шаблонов

## Что дальше?

Следующие возможности:
- Waterfall chart для nested templates
- Детальный анализ undefined переменных
- Сравнение производительности шаблонов
- Рекомендации по оптимизации


