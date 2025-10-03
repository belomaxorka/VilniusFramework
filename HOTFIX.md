# 🔧 Hotfix: Исправления после удаления хелперов

## Проблема 1: view() в Response.php

После удаления хелпера `view()` возникла ошибка:
```
Call to undefined function Core\view() in core/Response.php:334
```

### Решение

Заменен вызов хелпера на прямой вызов `TemplateEngine`:

```php
// Было
$content = view($template, $data);

// Стало
$content = \Core\TemplateEngine::getInstance()->render($template, $data);
```

**Файл:** `core/Response.php` - строка 334

---

## Проблема 2: Отсутствие методов trace() и dd() в Debug

Функции `trace()`, `dd()`, `ddPretty()` были реализованы в хелперах и имели полноценную логику, а не просто обертки.

### Решение

Добавлены методы в класс `Core\Debug`:

```php
// Dump and die
Debug::dd($var, $label);              // Вывести и остановить выполнение
Debug::ddPretty($var, $label);        // Красивый вывод и остановка

// Stack trace с красивым форматированием
Debug::trace($label);                 // Вывести backtrace
```

**Файл:** `core/Debug.php`

**Добавленные методы:**
- `public static function dd(mixed $var, ?string $label = null): never`
- `public static function ddPretty(mixed $var, ?string $label = null): never`
- `public static function trace(?string $label = null): void`

---

## Что изменилось

### Было (удаленные хелперы):
```php
dd($variable);
trace('Current Location');
dump_pretty($data);
```

### Стало (методы класса Debug):
```php
use Core\Debug;

Debug::dd($variable);
Debug::trace('Current Location');
Debug::dumpPretty($data);
```

---

## Проблема 3: Отсутствие normalize_path()

Функция `normalize_path()` была в хелперах и нормализовала пути (заменяла `\` на `/`).

### Решение

Создан класс-утилита `Core\Path` с методами для работы с путями:

```php
// Было
$path = normalize_path($filePath);

// Стало
use Core\Path;

$path = Path::normalize($filePath);
$relative = Path::relative($absolutePath);
$joined = Path::join('app', 'Controllers', 'HomeController.php');
```

**Новый класс:** `core/Path.php`  
**Обновлены файлы:** `core/DumpClient.php`, `core/DumpServer.php`

**Преимущества:**
- ✅ Больше методов (normalize, relative, join, extension, и др.)
- ✅ Явный класс вместо глобальной функции
- ✅ IDE автодополнение
- ✅ Соответствует философии фреймворка

---

## Файлы изменены

### Созданы новые
1. ✅ `core/Path.php` - новый класс-утилита для работы с путями
2. ✅ `docs/Path.md` - документация класса Path
3. ✅ `docs/DebugAPI.md` - полная документация Debug API

### Обновлены
4. ✅ `core/Response.php` - исправлен вызов view()
5. ✅ `core/Debug.php` - добавлены методы dd(), ddPretty(), trace()
6. ✅ `core/DumpClient.php` - использует Path::normalize() и Path::relative()
7. ✅ `core/DumpServer.php` - использует Path::relative()
8. ✅ `docs/DeprecatedHelpers.md` - обновлена документация

---

## Проверка

- ✅ Линтер-ошибок нет
- ✅ Все debug функции доступны через класс Debug
- ✅ Stack trace имеет красивое форматирование (темная тема VSCode)
- ✅ Документация обновлена

---

## Статус

**ВСЕ ИСПРАВЛЕНО ✅**

Приложение должно работать корректно.

