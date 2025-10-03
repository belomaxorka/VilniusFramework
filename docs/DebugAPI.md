# Core\Debug - API Reference

Полная документация по API класса Debug.

## 🎯 Основные методы

### Простой дамп

#### `Debug::dump($var, $label = null)`
Вывести переменную без остановки выполнения.

```php
use Core\Debug;

$user = ['name' => 'John', 'age' => 30];
Debug::dump($user, 'User Data');
```

#### `Debug::dd($var, $label = null): never`
Вывести переменную и остановить выполнение (dump and die).

```php
Debug::dd($user, 'User Data'); // Останавливает выполнение
```

---

### Красивый дамп

#### `Debug::dumpPretty($var, $label = null)`
Вывести переменную с красивым форматированием (темная тема).

```php
$complex = [
    'user' => [
        'name' => 'John',
        'settings' => [
            'theme' => 'dark',
            'notifications' => true
        ]
    ]
];

Debug::dumpPretty($complex, 'User Settings');
```

#### `Debug::ddPretty($var, $label = null): never`
Красивый вывод с остановкой выполнения.

```php
Debug::ddPretty($complex, 'Complex Data'); // Останавливает выполнение
```

---

### Stack Trace

#### `Debug::trace($label = null)`
Вывести backtrace (стек вызовов) с красивым форматированием.

```php
function deepFunction() {
    Debug::trace('Deep in the call stack');
}

function middleFunction() {
    deepFunction();
}

middleFunction();
```

**Вывод включает:**
- Номер в стеке (#0, #1, #2...)
- Класс и метод (если есть)
- Имя функции
- Файл и строку
- Темная тема в стиле VSCode
- Цветовое кодирование элементов
- Прокручиваемый контейнер для больших трейсов

**В продакшене:**
- Автоматически логируется в файл через `Logger::debug()`
- Безопасно - не выводит данные на страницу

---

## 📦 Сбор данных

### `Debug::collect($var, $label = null)`
Собрать данные без немедленного вывода.

```php
Debug::collect($userData, 'User Info');
Debug::collect($configData, 'Config');
Debug::collect($requestData, 'Request');

// Позже вывести все собранные данные
Debug::dumpAll();
```

### `Debug::dumpAll($die = false)`
Вывести все собранные данные.

```php
Debug::collect($data1, 'Data 1');
Debug::collect($data2, 'Data 2');
Debug::dumpAll(); // Покажет все собранные данные
Debug::dumpAll(true); // Покажет и остановит выполнение
```

### `Debug::clear()`
Очистить собранные данные.

```php
Debug::collect($data);
Debug::clear(); // Удалить все собранные данные
```

---

## 🎛️ Управление буфером

### `Debug::getOutput($raw = false)`
Получить накопленный debug вывод.

```php
Debug::dump($data);
$output = Debug::getOutput(); // Строка с HTML
$raw = Debug::getOutput(true); // Массив элементов
```

### `Debug::hasOutput()`
Проверить наличие debug вывода в буфере.

```php
if (Debug::hasOutput()) {
    echo Debug::getOutput();
}
```

### `Debug::flush()`
Вывести накопленные debug данные и очистить буфер.

```php
Debug::dump($data1);
Debug::dump($data2);
Debug::flush(); // Выводит все и очищает
```

### `Debug::clearOutput()`
Очистить буфер вывода без отображения.

```php
Debug::dump($data);
Debug::clearOutput(); // Удалить из буфера без вывода
```

### `Debug::addOutput($output, $type = 'custom', $label = null, $rawText = null)`
Добавить собственный вывод в буфер или залогировать.

**Параметры:**
- `$output` - HTML для браузера
- `$type` - тип вывода ('dump', 'trace', 'custom', etc.)
- `$label` - метка для логов
- `$rawText` - текстовая версия для логов

```php
// Простой кастомный вывод
Debug::addOutput('<div style="color: red;">Custom debug message</div>');

// С типом и логированием
$html = '<div class="debug">My debug info</div>';
$text = 'My debug info (plain text)';
Debug::addOutput($html, 'custom', 'My Debug', $text);
```

**Автоматическое поведение:**
- В debug режиме - добавляет в буфер для вывода в браузер
- В продакшене - логирует текстовую версию в файл

---

## 🏷️ Типы вывода

Debug система поддерживает различные типы вывода для классификации:

| Тип | Описание | Используется в |
|-----|----------|----------------|
| `dump` | Простой дамп переменной | `Debug::dump()` |
| `dump_pretty` | Красивый дамп | `Debug::dumpPretty()` |
| `trace` | Stack trace | `Debug::trace()` |
| `dump_all` | Коллекция собранных данных | `Debug::dumpAll()` |
| `custom` | Кастомный вывод | `Debug::addOutput()` |

**Зачем нужны типы:**
- Различать источник вывода
- Фильтровать в будущем
- Применять разную обработку
- Статистика по типам

**Пример использования:**
```php
// Внутренне Debug::trace() делает так:
Debug::addOutput($htmlOutput, 'trace', $label, $textOutput);

// Внутренне Debug::dumpAll() делает так:
Debug::addOutput($htmlOutput, 'dump_all', 'Debug Collection', $textOutput);

// Вы можете использовать свои типы:
Debug::addOutput($html, 'my_custom_type', 'My Label', $text);
```

---

## ⚙️ Настройки

### `Debug::setAutoDisplay($auto)`
Включить/выключить автоматический вывод в конце выполнения.

```php
Debug::setAutoDisplay(true);  // По умолчанию
Debug::setAutoDisplay(false); // Отключить авто-вывод
```

### `Debug::isAutoDisplay()`
Проверить состояние автоматического вывода.

```php
if (Debug::isAutoDisplay()) {
    // Авто-вывод включен
}
```

### `Debug::setRenderOnPage($enabled)`
Включить/выключить рендеринг на странице.

**По умолчанию:** `false` - вывод только в Debug Toolbar.

```php
Debug::setRenderOnPage(true);  // Выводить на странице И в toolbar
Debug::setRenderOnPage(false); // Только в toolbar (по умолчанию)
```

### `Debug::isRenderOnPage()`
Проверить, включен ли рендеринг на странице.

```php
if (Debug::isRenderOnPage()) {
    // Рендеринг на странице включен
}
```

### `Debug::setMaxDepth($depth)`
Установить максимальную глубину рекурсии для dump.

```php
Debug::setMaxDepth(10); // По умолчанию
Debug::setMaxDepth(20); // Для глубоко вложенных структур
```

### `Debug::setShowBacktrace($show)`
Включить/выключить показ backtrace в dump.

```php
Debug::setShowBacktrace(true);  // По умолчанию
Debug::setShowBacktrace(false); // Скрыть backtrace
```

---

## 📊 Примеры использования

### Базовая отладка

```php
use Core\Debug;

// Простой вывод
$user = User::find(1);
Debug::dump($user, 'Current User');

// Остановить выполнение
Debug::dd($user, 'Stop here!');
```

### Красивый вывод

```php
$config = [
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'connections' => [
            'mysql' => [...],
            'sqlite' => [...]
        ]
    ]
];

Debug::dumpPretty($config, 'App Configuration');
```

### Stack trace

```php
class UserController {
    public function show($id) {
        $user = $this->findUser($id);
        
        if (!$user) {
            Debug::trace('User not found');
            return $this->error('Not found');
        }
        
        return $this->view('user.show', compact('user'));
    }
}
```

### Сбор данных

```php
// В разных частях кода
Debug::collect($request->all(), 'Request Data');
Debug::collect($config->get('app'), 'App Config');
Debug::collect($session->all(), 'Session Data');

// В конце вывести все
Debug::dumpAll();
```

### Условная отладка

```php
use Core\Environment;

if (Environment::isDebug()) {
    Debug::dump($queryResult, 'Query Result');
    Debug::trace('Query execution path');
}
```

---

## 🔒 Безопасность

### В режиме разработки (APP_ENV=development)
- ✅ Все методы активны
- ✅ Вывод на страницу или в toolbar
- ✅ Детальная информация

### В режиме продакшена (APP_ENV=production)
- ❌ Вывод отключен
- ✅ Данные логируются в файлы
- ✅ Безопасность сохраняется

```php
// В продакшене dump() не выводит ничего на страницу
// но логирует в файл storage/logs/debug.log
Debug::dump($sensitiveData); // Безопасно в prod
```

---

## 🎨 Форматирование

### Dump
- Простой текстовый вывод
- Компактный формат
- Быстрый

### DumpPretty
- Темная тема (стиль VSCode)
- Подсветка синтаксиса
- Структурированный вывод
- Красивое оформление

### Trace
- Темная тема в стиле VSCode
- Номера стеков с цветовым кодированием
- Файлы и строки
- Классы и методы с подсветкой
- Прокручиваемый контейнер

---

## 🚀 Рекомендации

### DO ✅
```php
// Используйте метки для идентификации
Debug::dump($data, 'User Data');

// Используйте trace для понимания flow
Debug::trace('After authentication');

// Собирайте данные для анализа
Debug::collect($state, 'State before change');
```

### DON'T ❌
```php
// Не оставляйте dd() в продакшен коде
Debug::dd($data); // ❌ Остановит выполнение

// Не дампите огромные объекты без необходимости
Debug::dump($hugeObject); // ❌ Может быть медленно

// Не используйте в циклах с большим количеством итераций
foreach ($items as $item) {
    Debug::dump($item); // ❌ Много вывода
}
```

---

## 📖 См. также

- [Debug Quick Start](DebugQuickStart.md)
- [Debug Contexts](DebugContexts.md)
- [Debug Toolbar](DebugToolbar.md)
- [Deprecated Helpers](DeprecatedHelpers.md)

---

**Все debug методы работают только в режиме разработки! 🔒**

