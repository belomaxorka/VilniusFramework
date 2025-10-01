# Helper Loading Flow

Как работает загрузка и объединение групп хелперов.

## 🔄 Схема загрузки

```
┌─────────────────────────────────────────────────────────────┐
│                    HelperLoader (Singleton)                  │
│                                                               │
│  private static $instance = null    ◄─── Один на приложение │
│  private array $loadedHelpers = []  ◄─── Общий реестр       │
└─────────────────────────────────────────────────────────────┘
                            ▲
                            │
        ┌───────────────────┼───────────────────┐
        │                   │                   │
        │                   │                   │
┌───────▼───────┐   ┌───────▼───────┐   ┌──────▼──────┐
│  bootstrap.php │   │ controller.php │   │ custom.php  │
│                │   │                │   │             │
│ loadGroups([   │   │ loadGroup(     │   │ loadGroup(  │
│   'app',       │   │   'profiler'   │   │   'app'     │
│   'debug'      │   │ )              │   │ )           │
│ ])             │   │                │   │             │
└────────────────┘   └────────────────┘   └─────────────┘
```

## 📊 Пример последовательности

### Шаг 1: Загрузка в bootstrap.php
```php
// core/bootstrap.php
\Core\HelperLoader::loadHelperGroups(['app', 'environment', 'debug']);

// Результат в $loadedHelpers:
[
    'group:app' => true,
    'group:environment' => true,
    'group:debug' => true,
]

// Доступные функции:
config(), env(), view(), is_debug(), is_dev(), dump(), dd(), etc.
```

### Шаг 2: Попытка загрузки в контроллере
```php
// app/Controllers/HomeController.php
\Core\HelperLoader::loadHelperGroup('app');  // ← Вернет false (уже загружена)

// Результат в $loadedHelpers:
[
    'group:app' => true,          // ← Без изменений
    'group:environment' => true,
    'group:debug' => true,
]

// Функции всё равно доступны!
config('app.name');  // ✅ Работает
```

### Шаг 3: Загрузка дополнительной группы
```php
// Где-то в приложении
\Core\HelperLoader::loadHelperGroup('profiler');  // ← Загрузит новую группу

// Результат в $loadedHelpers:
[
    'group:app' => true,
    'group:environment' => true,
    'group:debug' => true,
    'group:profiler' => true,      // ← Добавлена!
]

// Теперь доступны ВСЕ функции:
config(), env(), dump(), timer_start(), benchmark(), etc.  // ✅ Всё работает
```

## 🔐 Механизмы объединения

### 1. Singleton гарантирует единое состояние
```php
// В любом месте приложения
$loader1 = \Core\HelperLoader::getInstance();
$loader2 = \Core\HelperLoader::getInstance();

var_dump($loader1 === $loader2);  // bool(true) ← Один и тот же объект!
```

### 2. Глобальная область видимости функций
```php
// После загрузки группы 'app'
// core/helpers/app/config.php определяет:
function config(string $key, mixed $default = null): mixed { ... }

// Эта функция доступна ВЕЗДЕ:
// - В контроллерах
// - В моделях
// - В шаблонах
// - В тестах
// - В любом файле

config('app.name');  // ✅ Работает отовсюду
```

### 3. Защита от дублирования
```php
// Даже если вызвать много раз:
\Core\HelperLoader::loadHelperGroup('app');  // true  - загрузилась
\Core\HelperLoader::loadHelperGroup('app');  // false - уже загружена
\Core\HelperLoader::loadHelperGroup('app');  // false - уже загружена

// require_once предотвращает переопределение:
// PHP не загрузит файл повторно
// Функции не будут переопределены
// Не будет ошибки "Cannot redeclare function"
```

## 💡 Практические кейсы

### Кейс 1: Ленивая загрузка в контроллере
```php
class MyController
{
    public function __construct()
    {
        // Загружаем profiler только для этого контроллера
        \Core\HelperLoader::loadHelperGroup('profiler');
    }
    
    public function action()
    {
        timer_start('action');
        
        // Используем также функции из основных групп
        $data = config('app.data');
        
        timer_stop('action');
    }
}
```

### Кейс 2: Условная загрузка
```php
// Загружаем debug инструменты только в dev
if (is_dev()) {
    \Core\HelperLoader::loadHelperGroup('profiler');
    \Core\HelperLoader::loadHelperGroup('context');
}

// Функции будут доступны только если загрузились
if (function_exists('timer_start')) {
    timer_start('request');
}
```

### Кейс 3: Модульная загрузка
```php
// В разных модулях приложения
// module1/init.php
\Core\HelperLoader::loadHelperGroups(['app', 'database']);

// module2/init.php
\Core\HelperLoader::loadHelperGroups(['app', 'profiler']);

// Результат: объединение всех групп
// Доступны: app, database, profiler
```

## ✅ Итого

### Что происходит при загрузке в разных местах:

1. **Первая загрузка группы** → Группа загружается, функции становятся доступны
2. **Повторная загрузка той же группы** → Пропускается (вернет `false`)
3. **Загрузка новой группы** → Добавляется к уже загруженным
4. **Все функции доступны глобально** → Из любой части приложения

### Ключевые преимущества:

✅ **Безопасно** - нет переопределения функций  
✅ **Эффективно** - группа загружается только один раз  
✅ **Гибко** - можно загружать в любом месте  
✅ **Прозрачно** - все функции доступны везде после загрузки  

### Проверка загруженных групп:

```php
$loader = \Core\HelperLoader::getInstance();

// Проверить конкретную группу
if ($loader->isLoaded('group:profiler')) {
    echo "Profiler загружен";
}

// Получить список всех загруженных
$loaded = $loader->getLoaded();
print_r($loaded);
// [
//     'group:app',
//     'group:environment',
//     'group:debug',
//     'group:profiler'
// ]
```

## 🎯 Best Practices

### ✅ Хорошо

```php
// Загрузить основные группы в bootstrap
\Core\HelperLoader::loadHelperGroups(['app', 'environment', 'debug']);

// Загрузить специфичные группы по требованию
if ($needsProfiling) {
    \Core\HelperLoader::loadHelperGroup('profiler');
}

// Проверить доступность перед использованием (опционально)
if (function_exists('timer_start')) {
    timer_start('operation');
}
```

### ❌ Избегайте

```php
// Не надо загружать одну и ту же группу много раз
\Core\HelperLoader::loadHelperGroup('app');
// ... 100 строк кода ...
\Core\HelperLoader::loadHelperGroup('app');  // Бесполезно

// Не надо полагаться на порядок загрузки
// (хотя это и не вызовет ошибок, но код становится менее понятным)
```

## 🔍 Отладка

```php
// Проверить что загружено
$loader = \Core\HelperLoader::getInstance();
dump($loader->getLoaded());

// Проверить доступность функции
dump([
    'config' => function_exists('config'),
    'timer_start' => function_exists('timer_start'),
    'is_debug' => function_exists('is_debug'),
]);
```

