# Helper Functions Structure

Хелперы организованы по группам в отдельных папках для лучшей организации и масштабируемости.

## 📁 Структура

```
core/helpers/
├── app/              # Основные функции приложения
│   ├── config.php    # config()
│   ├── lang.php      # __()
│   ├── env.php       # env()
│   └── view.php      # view(), display(), template()
│
├── environment/      # Проверки окружения
│   ├── checks.php    # is_debug(), is_dev(), is_prod(), is_testing(), is_staging(), app_env()
│   └── system.php    # is_cli(), is_windows(), is_unix()
│
├── debug/            # Отладочные функции
│   ├── dump.php      # dd(), dump(), dump_pretty(), dd_pretty()
│   ├── collect.php   # collect(), dump_all(), clear_debug()
│   ├── trace.php     # trace()
│   ├── log.php       # debug_log()
│   ├── output.php    # debug_flush(), debug_output(), render_debug(), render_debug_toolbar()
│   └── server.php    # server_dump(), dd_server(), dump_server_available()
│
├── profiler/         # Профилирование производительности
│   ├── benchmark.php # benchmark()
│   ├── timer.php     # timer_start(), timer_stop(), timer_lap(), timer_elapsed(), etc.
│   └── memory.php    # memory_start(), memory_snapshot(), memory_current(), etc.
│
├── database/         # Отладка базы данных
│   └── query.php     # query_log(), query_dump(), query_stats(), query_slow(), etc.
│
└── context/          # Контексты отладки
    └── context.php   # context_start(), context_end(), context_run(), etc.
```

## 🚀 Использование

### Автоматическая загрузка

Все группы автоматически загружаются в `core/bootstrap.php`:

```php
\Core\HelperLoader::loadHelperGroups([
    'app',          // Основные функции
    'environment',  // Проверки окружения
    'debug',        // Отладка
    'profiler',     // Профилирование
    'database',     // База данных
    'context',      // Контексты
]);
```

### Загрузка отдельной группы

```php
// Загрузить только одну группу
\Core\HelperLoader::loadHelperGroup('debug');

// Загрузить несколько групп
\Core\HelperLoader::loadHelperGroups(['app', 'debug']);
```

## ➕ Добавление своих хелперов

### Вариант 1: Добавить в существующую группу

Создайте файл в соответствующей папке:

```php
// core/helpers/app/custom.php
<?php declare(strict_types=1);

if (!function_exists('my_helper')) {
    function my_helper(): string
    {
        return 'Hello!';
    }
}
```

Файл автоматически загрузится при загрузке группы `app`.

### Вариант 2: Создать новую группу

1. Создайте папку: `core/helpers/my_group/`
2. Добавьте файлы с функциями
3. Загрузите группу в `bootstrap.php`:

```php
\Core\HelperLoader::loadHelperGroups([
    'app',
    'environment',
    'debug',
    'profiler',
    'database',
    'context',
    'my_group',  // Ваша группа
]);
```

## 📦 Преимущества структуры

✅ **Логическая группировка** - легко найти нужную функцию  
✅ **Масштабируемость** - легко добавлять новые функции  
✅ **Модульность** - можно загружать только нужные группы  
✅ **Читаемость** - каждый файл содержит связанные функции  
✅ **Поддерживаемость** - изменения изолированы в маленьких файлах  

## 🔍 Поиск функции

Не знаете, где находится функция? Посмотрите по категориям:

- **Работа с конфигом/переводами/шаблонами** → `app/`
- **Проверка окружения (dev/prod)** → `environment/`
- **Вывод данных для отладки** → `debug/`
- **Измерение времени/памяти** → `profiler/`
- **Отладка SQL запросов** → `database/`
- **Группировка отладочного вывода** → `context/`

## 📚 Документация

Полная документация по функциям: [docs/Helpers.md](../../docs/Helpers.md)

