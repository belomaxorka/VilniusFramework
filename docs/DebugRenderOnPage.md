# Debug Render On Page - Управление выводом

## Обзор

По умолчанию все debug функции (`dump()`, `dd()`, `dump_pretty()`, и т.д.) собирают данные **только для Debug Toolbar** и **не выводят их напрямую на страницу**. Это предотвращает дублирование вывода, так как все данные уже доступны в интерактивной панели toolbar.

## Проблема, которую решает эта функция

**До изменений:**
```
┌─────────────────────────────────┐
│ Страница                        │
├─────────────────────────────────┤
│                                 │
│  [Dump: User Data]  ← Вывод 1  │
│  [Dump: Config]     ← Вывод 1  │
│                                 │
├─────────────────────────────────┤
│ Debug Toolbar                   │
│ ┌─ Dumps Tab ─────────────────┐│
│ │ [Dump: User Data] ← Вывод 2 ││
│ │ [Dump: Config]    ← Вывод 2 ││
│ └─────────────────────────────┘│
└─────────────────────────────────┘
```
**Дублирование:** Данные выводились и на странице, и в toolbar.

**После изменений (по умолчанию):**
```
┌─────────────────────────────────┐
│ Страница                        │
├─────────────────────────────────┤
│                                 │
│  (Без вывода на странице)       │
│                                 │
├─────────────────────────────────┤
│ Debug Toolbar                   │
│ ┌─ Dumps Tab ─────────────────┐│
│ │ [Dump: User Data]           ││
│ │ [Dump: Config]              ││
│ └─────────────────────────────┘│
└─────────────────────────────────┘
```
**Чисто:** Данные только в toolbar, страница не замусорена.

## Использование

### Вариант 1: Только в Toolbar (по умолчанию)

```php
// В контроллере
public function index() 
{
    $user = User::find(1);
    $config = Config::all();
    
    // Данные будут ТОЛЬКО в Debug Toolbar
    dump($user, 'User Data');
    dump_pretty($config, 'App Config');
    
    return view('index');
}
```

**Результат:** Чистая страница, все dumps в интерактивной панели toolbar.

### Вариант 2: На странице И в Toolbar

```php
// Включить вывод на странице
debug_render_on_page(true);

// Теперь dumps будут и на странице, и в toolbar
dump($user, 'User Data');
dump_pretty($config, 'App Config');

// Выключить обратно
debug_render_on_page(false);
```

**Результат:** Dumps выводятся на странице в том месте, где вызваны, И в toolbar.

### Вариант 3: Программное управление

```php
use Core\Debug;

// Через класс Debug
Debug::setRenderOnPage(true);   // Включить
Debug::setRenderOnPage(false);  // Выключить

// Проверка текущего состояния
if (Debug::isRenderOnPage()) {
    // Вывод на странице включен
}
```

## API

### Helper функции

#### `debug_render_on_page(bool $enabled = true): void`
Включить/выключить рендеринг на странице.

```php
debug_render_on_page(true);   // Включить
debug_render_on_page(false);  // Выключить
```

### Методы класса Debug

#### `Debug::setRenderOnPage(bool $renderOnPage): void`
Установить рендеринг на странице.

```php
Debug::setRenderOnPage(true);
```

#### `Debug::isRenderOnPage(): bool`
Получить текущее состояние рендеринга на странице.

```php
$isEnabled = Debug::isRenderOnPage();
```

## Примеры использования

### Пример 1: Обычная разработка (рекомендуется)

```php
public function dashboard() 
{
    // Просто используйте dump() как обычно
    dump($stats, 'Stats');
    dump($notifications, 'Notifications');
    
    // Все данные в toolbar, страница чистая
    return view('dashboard');
}
```

### Пример 2: Отладка определенного места на странице

```php
public function complexView() 
{
    $data = prepareData();
    
    // В определенном месте нужен вывод на странице
    debug_render_on_page(true);
    dump($data, 'Data before rendering');
    debug_render_on_page(false);
    
    // Остальные dumps только в toolbar
    dump($otherData, 'Other Data');
    
    return view('complex');
}
```

### Пример 3: Условный вывод

```php
public function apiEndpoint(Request $request) 
{
    // Включаем вывод на странице для API запросов
    if ($request->has('debug')) {
        debug_render_on_page(true);
    }
    
    dump($request->all(), 'Request Data');
    
    $result = processRequest($request);
    
    dump($result, 'Result');
    
    return response()->json($result);
}
```

### Пример 4: Глобальная настройка в bootstrap

```php
// bootstrap.php или Core::init()

use Core\Debug;

// Для всех режимов - только в toolbar
Debug::setRenderOnPage(false);

// Или для определенных условий
if (env('DEBUG_RENDER_ON_PAGE', false)) {
    Debug::setRenderOnPage(true);
}
```

## Рекомендации

### ✅ Рекомендуется

1. **Оставить по умолчанию (false)** - использовать только toolbar
2. **Использовать toolbar** для просмотра всех debug данных
3. **Включать `renderOnPage` временно** - только для отладки конкретного места

### ❌ Не рекомендуется

1. Глобально включать `renderOnPage(true)` - это замусорит страницу
2. Оставлять `renderOnPage(true)` в production коде
3. Использовать `renderOnPage(true)` когда toolbar достаточно

## Совместимость

### С Debug Toolbar

Debug Toolbar **всегда** получает все dumps независимо от настройки `renderOnPage`:

- `renderOnPage = false`: dumps только в toolbar ✓
- `renderOnPage = true`: dumps на странице И в toolbar ✓

### С функциями дебага

Все debug функции поддерживают эту настройку:

- `dump()` / `dd()`
- `dump_pretty()` / `dd_pretty()`
- `dump_all()`
- `trace()`
- `benchmark()`
- И другие

### С окружениями

- **Development**: работает как описано
- **Production**: все debug функции отключены (независимо от настройки)
- **Testing**: работает как описано

## Миграция с предыдущей версии

### Старый код (выводил на страницу)

```php
dump($data); // Выводилось на странице
```

### Новый код (по умолчанию)

```php
dump($data); // Только в toolbar

// Если нужно на странице:
debug_render_on_page(true);
dump($data); // Теперь и на странице
```

## Конфигурация через .env

```env
# .env
DEBUG_RENDER_ON_PAGE=false  # По умолчанию
```

В bootstrap:

```php
Debug::setRenderOnPage(env('DEBUG_RENDER_ON_PAGE', false));
```

## FAQ

**Q: Почему по умолчанию `renderOnPage = false`?**  
A: Чтобы избежать дублирования вывода. Debug Toolbar уже показывает все dumps в удобном интерактивном виде.

**Q: Как вернуть старое поведение (вывод на странице)?**  
A: Вызовите `debug_render_on_page(true)` в начале скрипта или в `Core::init()`.

**Q: Влияет ли это на `dd()` (die)?**  
A: Нет, `dd()` всегда выводит и останавливает выполнение, независимо от `renderOnPage`.

**Q: Работает ли это с dump server?**  
A: Да, `server_dump()` работает независимо от этой настройки.

**Q: Можно ли настроить для отдельных dump'ов?**  
A: Да, включайте/выключайте `renderOnPage` до конкретных вызовов:

```php
debug_render_on_page(true);
dump($important); // На странице
debug_render_on_page(false);
dump($other); // Только в toolbar
```
