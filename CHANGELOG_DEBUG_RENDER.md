# Changelog: Debug Render On Page

## Изменения

### Добавлено

#### Новый параметр `renderOnPage` в классе `Debug`

- **По умолчанию:** `false` (данные только в Debug Toolbar)
- **Назначение:** Контролирует, выводятся ли debug данные напрямую на страницу или только собираются для Debug Toolbar

#### Новые методы

1. **`Debug::setRenderOnPage(bool $renderOnPage): void`**
   - Включить/выключить рендеринг на странице
   
2. **`Debug::isRenderOnPage(): bool`**
   - Получить текущее состояние рендеринга

#### Новая helper функция

**`debug_render_on_page(bool $enabled = true): void`**
```php
// Включить вывод на странице
debug_render_on_page(true);

// Выключить (по умолчанию)
debug_render_on_page(false);
```

### Изменено

#### Shutdown Handler

`Debug::registerShutdownHandler()` теперь проверяет настройку `renderOnPage`:
- Если `renderOnPage = false`: данные НЕ выводятся на страницу, остаются в буфере для toolbar
- Если `renderOnPage = true`: данные выводятся на страницу И доступны в toolbar

### Улучшено

#### Предотвращение дублирования вывода

- **Раньше:** `dump()` выводился и на странице, и в Debug Toolbar (дублирование)
- **Теперь:** `dump()` по умолчанию только в Debug Toolbar (без дублирования)

#### Интеграция с Debug Toolbar

Debug Toolbar всегда получает все dumps через `Debug::getOutput()`, независимо от `renderOnPage`.

## Обратная совместимость

### Изменения в поведении по умолчанию

⚠️ **BREAKING CHANGE:** По умолчанию `renderOnPage = false`

**Старое поведение:**
```php
dump($data); // Выводилось на странице
```

**Новое поведение:**
```php
dump($data); // Только в Debug Toolbar

// Для вывода на странице:
debug_render_on_page(true);
dump($data); // Теперь на странице и в toolbar
```

### Миграция

Если вы хотите вернуть старое поведение (вывод на странице):

**Вариант 1: Глобально в `Core::init()`**
```php
Debug::setRenderOnPage(true);
```

**Вариант 2: Через .env**
```env
DEBUG_RENDER_ON_PAGE=true
```

```php
Debug::setRenderOnPage(env('DEBUG_RENDER_ON_PAGE', false));
```

**Вариант 3: Локально в коде**
```php
debug_render_on_page(true);
// Ваши dumps
debug_render_on_page(false);
```

## Обновленная документация

- `docs/Debug.md` - добавлена секция "Управление выводом"
- `docs/DebugToolbar.md` - добавлена секция "Управление выводом на странице"
- `docs/DebugRenderOnPage.md` - новый подробный гайд

## Тесты

Добавлены тесты в `tests/Unit/Core/Debug/DebugTest.php`:
- Проверка значения по умолчанию (`false`)
- Изменение настройки через `setRenderOnPage()`
- Работа helper функции `debug_render_on_page()`
- Доступность данных для toolbar независимо от настройки

## Примеры использования

### По умолчанию (рекомендуется)

```php
public function index() 
{
    dump($user, 'User Data');
    dump_pretty($config, 'Config');
    
    // Страница чистая, все в Debug Toolbar
    return view('index');
}
```

### С выводом на странице

```php
public function debug() 
{
    debug_render_on_page(true);
    
    dump($debug_data, 'Debug Info');
    
    debug_render_on_page(false);
    
    return view('debug');
}
```

### Условный вывод

```php
if ($request->has('debug')) {
    debug_render_on_page(true);
}

dump($data, 'Request Data');
```

## Преимущества

✅ Нет дублирования вывода  
✅ Чистая страница по умолчанию  
✅ Все данные в интерактивном Debug Toolbar  
✅ Гибкое управление выводом  
✅ Обратная совместимость через настройку

## Что делать

### Если вы используете Debug Toolbar

✅ **Ничего не делайте** - всё работает лучше по умолчанию

### Если вам нужен вывод на странице

```php
debug_render_on_page(true);
```

### Если вы хотите старое поведение везде

Добавьте в `Core::init()`:
```php
Debug::setRenderOnPage(true);
```

## Дата изменений

**Версия:** 2.0  
**Дата:** 2025-09-30  
**Автор:** Development Team
