# Новые коллекторы Debug Toolbar

## Обзор

В Debug Toolbar добавлены 4 новых коллектора для расширенной отладки приложения:

1. **Session Collector** 🔐 - информация о сессии
2. **Environment Collector** ⚙️ - конфигурация PHP и окружение
3. **Files Collector** 📁 - список подключенных файлов
4. **Logs Collector** 📝 - логи приложения

---

## 1. Session Collector 🔐

### Возможности

- **Session Details** - ID, название, статус сессии
- **Cookie Parameters** - настройки cookie (lifetime, secure, httponly, samesite)
- **Session Configuration** - конфигурация PHP сессий
- **Session Data** - содержимое $_SESSION с типами и красивым форматированием

### Когда активен

Коллектор активен только когда сессия запущена (`session_status() === PHP_SESSION_ACTIVE`)

### Отображение

- Badge показывает количество переменных в сессии
- Статистика в header: количество session переменных
- Полная информация в панели с раскрывающимися секциями

### Особенности

- Вложенные массивы отображаются в JSON формате
- Boolean значения показываются как цветные badges (Yes/No)
- Типы данных выделены цветом
- Чувствительные данные НЕ маскируются (только в debug режиме)

### Пример использования

```php
// В контроллере или где угодно
session_start();
$_SESSION['user_id'] = 123;
$_SESSION['username'] = 'john_doe';
$_SESSION['settings'] = [
    'theme' => 'dark',
    'language' => 'en'
];

// Session Collector автоматически соберет эти данные
// и отобразит в Debug Toolbar
```

---

## 2. Environment Collector ⚙️

### Возможности

- **Framework Info** - окружение, debug режим, путь к фреймворку
- **PHP Info** - версия PHP, SAPI, OS, архитектура, Zend Engine
- **Environment Variables** - переменные из .env файла (с маскировкой чувствительных)
- **PHP Configuration** - важные настройки PHP (memory_limit, upload_max_filesize, error_reporting, etc.)
- **Loaded Extensions** - список загруженных расширений PHP

### Безопасность

#### В Development режиме:
- ✅ Показываются все переменные окружения
- ⚠️ Чувствительные данные (PASSWORD, SECRET, TOKEN, KEY, API) маскируются как `***HIDDEN***`

#### В Production режиме:
- ❌ Переменные окружения полностью скрыты
- ✅ Показывается предупреждение о production режиме

### Отображение

- Header статистика: окружение и версия PHP с цветовой индикацией
- Цвета окружений:
  - 🔴 Production - красный
  - 🟢 Development - зеленый
  - 🟠 Testing - оранжевый
  - 🔵 Staging - синий

### Пример

```php
// .env файл
APP_ENV=development
APP_DEBUG=true
DB_HOST=localhost
DB_PASSWORD=secret123  # Будет показан как ***HIDDEN***
API_KEY=xyz789         # Будет показан как ***HIDDEN***
```

---

## 3. Files Collector 📁

### Возможности

- **Statistics** - количество файлов, общий размер, количество директорий, средний размер файла
- **Files by Directory** - группировка файлов по директориям с статистикой
- **All Files** - полный список всех подключенных файлов

### Отображение

- Badge: количество подключенных файлов
- Header: количество файлов и общий размер
- Таблицы с сортировкой и процентами

### Метрики

Для каждой директории показывается:
- Количество файлов
- Общий размер
- Процент от всех файлов

Для каждого файла:
- Номер
- Относительный путь
- Расширение (с badge)
- Размер

### Особенности

- Файлы показываются в порядке подключения
- Пути относительно корня проекта
- Директории сортируются по количеству файлов
- Таблицы раскрываются по клику

### Зачем нужно?

- Обнаружение лишних/ненужных подключений
- Анализ размера подключаемых библиотек
- Оптимизация autoload
- Debugging включаемых файлов

---

## 4. Logs Collector 📝

### Возможности

- **Statistics** - общее количество логов и по уровням
- **Logs List** - список всех логов запроса с подробностями
- **Context Data** - контекстные данные для каждого лога

### Интеграция с Logger

Коллектор автоматически интегрируется с `Core\Logger`:

```php
use Core\Logger;

// Все эти логи попадут в Debug Toolbar
Logger::debug('Debug message');
Logger::info('User logged in', ['user_id' => 123]);
Logger::warning('High memory usage');
Logger::error('Database connection failed', ['host' => 'localhost']);
Logger::critical('Critical error occurred');
```

### Уровни логов

| Уровень | Цвет | Фон | Использование |
|---------|------|-----|---------------|
| emergency | 🔴 Красный | Светло-красный | Система неработоспособна |
| alert | 🔴 Красный | Светло-красный | Требуется немедленное действие |
| critical | 🔴 Красный | Светло-красный | Критические условия |
| error | 🔴 Красный | Светло-красный | Ошибки выполнения |
| warning | 🟠 Оранжевый | Светло-оранжевый | Предупреждения |
| notice | 🔵 Синий | Светло-синий | Нормальные но важные события |
| info | 🟢 Зеленый | Светло-зеленый | Информационные сообщения |
| debug | ⚫ Серый | Светло-серый | Отладочные сообщения |

### Отображение

Каждый лог показывает:
- **Level badge** - уровень с цветом
- **Relative time** - время относительно начала запроса (в мс)
- **Memory** - использование памяти на момент лога
- **Message** - текст сообщения
- **Context** - контекстные данные (раскрываются по клику)

### Header статистика

- Показывает общее количество логов
- Красным выделяет если есть error/critical логи
- Показывает количество errors в скобках

### Особенности

- Логи работают только в debug режиме
- Автоматическое форматирование JSON контекста
- Относительное время помогает найти узкие места
- Группировка по уровням в статистике

---

## Приоритеты коллекторов

Коллекторы отображаются в порядке приоритета (больше = раньше):

| Коллектор | Приоритет | Позиция |
|-----------|-----------|---------|
| Overview | 100 | 1 |
| Request | 90 | 2 |
| Queries | 80 | 3 |
| Session | 75 | 4 |
| Logs | 70 | 5 |
| Environment | 60 | 6 |
| Files | 55 | 7 |
| ... другие | | |

---

## Примеры использования

### Полный пример с всеми коллекторами

```php
<?php
// Запуск сессии
session_start();
$_SESSION['user_id'] = 123;
$_SESSION['role'] = 'admin';

// Логирование
Logger::info('User session started', ['user_id' => 123]);
Logger::debug('Processing request');

// Ваш код приложения
$users = $db->query('SELECT * FROM users');
Logger::debug('Loaded users', ['count' => count($users)]);

// Обновление сессии
$_SESSION['last_activity'] = time();

// Финальный лог
Logger::info('Request completed successfully');

// Debug Toolbar автоматически:
// 1. Соберет данные сессии (SessionCollector)
// 2. Покажет PHP конфигурацию (EnvironmentCollector)
// 3. Список всех загруженных файлов (FilesCollector)
// 4. Все логи запроса (LogsCollector)
// 5. SQL запросы (QueriesCollector)
// 6. И все остальные метрики
```

### Отладка сессионных проблем

```php
session_start();

// Дебаг сессии
$_SESSION['test'] = 'value';
Logger::debug('Session data', $_SESSION);

// Проверка cookie параметров через Session Collector
// Все настройки видны в toolbar
```

### Анализ производительности

```php
// Смотрим через Files Collector сколько файлов подключается
Logger::info('Request started');

// ... код ...

// Смотрим логи с временными метками в Logs Collector
Logger::debug('Processing step 1 completed');

// ... код ...

Logger::debug('Processing step 2 completed');

// Logs Collector покажет точное время каждого этапа
```

---

## Конфигурация

### Отключение конкретных коллекторов

```php
use Core\DebugToolbar;

// Получить коллектор
$sessionCollector = DebugToolbar::getCollector('session');

// Отключить
if ($sessionCollector) {
    $sessionCollector->setEnabled(false);
}
```

### Изменение приоритета

```php
// Сделать Files Collector более приоритетным
$filesCollector = DebugToolbar::getCollector('files');
if ($filesCollector) {
    $filesCollector->setPriority(95); // Будет выше Request
}
```

---

## Производительность

Все коллекторы:
- ✅ Работают только в debug режиме
- ✅ Используют lazy loading
- ✅ Минимальный overhead (<5ms)
- ✅ Не влияют на production

### Рекомендации

1. **Session Collector** - легковесный, всегда можно включать
2. **Environment Collector** - легковесный, один раз читает конфигурацию
3. **Files Collector** - средний overhead (~2-3ms для 100+ файлов)
4. **Logs Collector** - overhead зависит от количества логов

---

## Troubleshooting

### Session Collector не показывается

**Причина:** Сессия не запущена

**Решение:**
```php
// Убедитесь что сессия запущена
session_start();
```

### Environment Variables не видны

**Причина:** Production режим или .env файл не найден

**Решение:**
```php
// Проверьте окружение
echo Environment::get(); // должно быть 'development'

// Проверьте путь к .env
echo dirname(__DIR__, 2) . '/.env';
```

### Logs не появляются в коллекторе

**Причина:** Logger не инициализирован или не вызывается

**Решение:**
```php
// Инициализируйте Logger
Logger::init();

// Убедитесь что используете Logger, а не error_log
Logger::debug('Test message'); // ✅ Правильно
error_log('Test message');     // ❌ Не попадет в коллектор
```

### Files Collector показывает много лишних файлов

**Причина:** Vendor файлы включаются в список

**Решение:**
Это нормально. Все подключенные файлы через `require`/`include`/`autoload` показываются.
Можно добавить фильтрацию, если нужно.

---

## Что дальше?

После внедрения этих коллекторов, можно добавить:

- **Views Collector** - список отрендеренных шаблонов
- **Events Collector** - события приложения
- **Exceptions Collector** - перехваченные исключения
- **Cache Collector** - статистика кэша

Все коллекторы следуют единому паттерну и легко расширяются!

