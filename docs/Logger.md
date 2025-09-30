# Система логирования

Мощная и гибкая система логирования с поддержкой множества драйверов и каналов.

## Содержание

- [Введение](#введение)
- [Быстрый старт](#быстрый-старт)
- [Конфигурация](#конфигурация)
- [Использование](#использование)
- [Драйверы](#драйверы)
- [Уровни логирования](#уровни-логирования)
- [Контекстные данные](#контекстные-данные)
- [Множественные каналы](#множественные-каналы)
- [Создание собственного драйвера](#создание-собственного-драйвера)

---

## Введение

Система логирования предоставляет унифицированный интерфейс для записи логов в различные источники (файлы, Slack, Telegram и т.д.). Она автоматически инициализируется из конфигурации и поддерживает:

- 📁 **Файловое логирование** - запись в локальные файлы
- 💬 **Slack** - отправка логов в Slack каналы
- 📱 **Telegram** - отправка логов в Telegram чаты
- 🔌 **Расширяемость** - легко добавить новые драйверы
- 🎯 **Фильтрация** - настройка минимальных уровней для каждого драйвера
- 📊 **Контекст** - передача дополнительных данных в логи

---

## Быстрый старт

### Базовое использование

```php
use Core\Logger;

// Логирование различных уровней
Logger::debug('Debug information');
Logger::info('User logged in');
Logger::warning('Low disk space');
Logger::error('Database connection failed');
Logger::critical('System is down!');
```

### С контекстными данными

```php
Logger::info('User {username} logged in from {ip}', [
    'username' => 'john_doe',
    'ip' => '192.168.1.1'
]);
// Результат: User john_doe logged in from 192.168.1.1
```

---

## Конфигурация

Система логирования настраивается в файле `config/logging.php`:

```php
return [
    // Драйвер по умолчанию
    'default' => env('LOG_CHANNEL', 'file'),
    
    // Минимальный уровень логирования
    'min_level' => env('LOG_LEVEL', 'debug'),
    
    // Активные каналы (можно несколько: 'file,slack,telegram')
    'channels' => env('LOG_CHANNELS', 'file'),
    
    // Настройки драйверов
    'drivers' => [
        'file' => [
            'driver' => 'file',
            'path' => env('LOG_FILE', LOG_DIR . '/app.log'),
            'min_level' => 'debug',
        ],
        
        'slack' => [
            'driver' => 'slack',
            'webhook_url' => env('LOG_SLACK_WEBHOOK_URL', ''),
            'channel' => '#logs',
            'username' => 'Logger Bot',
            'emoji' => ':robot_face:',
            'min_level' => 'error',
        ],
        
        'telegram' => [
            'driver' => 'telegram',
            'bot_token' => env('LOG_TELEGRAM_BOT_TOKEN', ''),
            'chat_id' => env('LOG_TELEGRAM_CHAT_ID', ''),
            'min_level' => 'error',
            'parse_mode' => 'HTML',
        ],
    ],
];
```

### Переменные окружения

Добавьте в ваш `.env` файл:

```env
# Базовые настройки
LOG_CHANNEL=file
LOG_LEVEL=debug
LOG_CHANNELS=file

# Файловое логирование
LOG_FILE=storage/logs/app.log

# Slack (опционально)
LOG_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL
LOG_SLACK_CHANNEL=#logs
LOG_SLACK_LEVEL=error

# Telegram (опционально)
LOG_TELEGRAM_BOT_TOKEN=123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11
LOG_TELEGRAM_CHAT_ID=123456789
LOG_TELEGRAM_LEVEL=error
```

---

## Использование

### Базовые методы

```php
// Debug - детальная отладочная информация
Logger::debug('Variable value: ' . $value);

// Info - информационные сообщения
Logger::info('User registered successfully');

// Warning - предупреждения
Logger::warning('Cache miss for key: ' . $key);

// Error - ошибки выполнения
Logger::error('Failed to connect to database');

// Critical - критические ошибки
Logger::critical('Application crashed!');
```

### Установка минимального уровня

```php
// Установить минимальный уровень программно
Logger::setMinLevel('error');

// Теперь логируются только error и critical
Logger::debug('Not logged'); // Не будет залогировано
Logger::error('Logged!');     // Будет залогировано
```

---

## Драйверы

### File Driver (Файлы)

Записывает логи в файл на диске.

**Формат записи:**
```
[2025-09-30 14:23:45] [INFO] User logged in
[2025-09-30 14:23:46] [ERROR] Database connection failed
```

**Конфигурация:**
```php
'file' => [
    'driver' => 'file',
    'path' => '/path/to/logs/app.log',
    'min_level' => 'debug',
]
```

**Особенности:**
- ✅ Автоматически создает директорию если не существует
- ✅ Добавляет записи в конец файла
- ✅ Включает timestamp и уровень

---

### Slack Driver

Отправляет логи в Slack через Incoming Webhooks.

**Настройка:**

1. Создайте Incoming Webhook в Slack:
   - Перейдите на https://api.slack.com/messaging/webhooks
   - Создайте новый Webhook для вашего канала
   - Скопируйте URL

2. Добавьте в конфигурацию:
```php
'slack' => [
    'driver' => 'slack',
    'webhook_url' => 'https://hooks.slack.com/services/YOUR/WEBHOOK/URL',
    'channel' => '#logs',
    'username' => 'Logger Bot',
    'emoji' => ':robot_face:',
    'min_level' => 'error', // Только ошибки в Slack
]
```

**Особенности:**
- 🎨 Цветные сообщения (красный для ошибок, желтый для warning и т.д.)
- 😀 Эмодзи индикаторы уровней
- 📊 Structured attachments
- ⏱️ Timestamp включен
- 🌐 Отображает источник (hostname или CLI)

**Цвета уровней:**
- `debug` - Серый (#6c757d)
- `info` - Голубой (#17a2b8)
- `warning` - Желтый (#ffc107)
- `error` - Красный (#dc3545)
- `critical` - Темно-красный (#721c24)

---

### Telegram Driver

Отправляет логи в Telegram через Bot API.

**Настройка:**

1. Создайте бота через @BotFather:
   - Напишите `/newbot` в @BotFather
   - Следуйте инструкциям
   - Получите Bot Token

2. Получите Chat ID:
   - Напишите что-нибудь вашему боту
   - Или используйте @userinfobot / @getmyid_bot

3. Добавьте в конфигурацию:
```php
'telegram' => [
    'driver' => 'telegram',
    'bot_token' => '123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11',
    'chat_id' => '123456789',
    'min_level' => 'error',
    'parse_mode' => 'HTML', // HTML или Markdown
]
```

**Особенности:**
- 📱 Мгновенные уведомления
- 😀 Эмодзи индикаторы
- 🎨 Форматированный текст (HTML/Markdown)
- ⏱️ Timestamp включен
- 🔒 Автоматическое экранирование спецсимволов

**Форматы:**

HTML (по умолчанию):
```
🔥 CRITICAL

Database server crashed!

example.com | 2025-09-30 14:23:45
```

Markdown:
```
🔥 *CRITICAL*

Database server crashed\!

_example.com | 2025-09-30 14:23:45_
```

---

## Уровни логирования

Система поддерживает 5 уровней логирования по возрастанию серьезности:

| Уровень | Метод | Описание | Эмодзи |
|---------|-------|----------|--------|
| `debug` | `Logger::debug()` | Детальная отладочная информация | 🐛 |
| `info` | `Logger::info()` | Информационные сообщения | ℹ️ |
| `warning` | `Logger::warning()` | Предупреждения | ⚠️ |
| `error` | `Logger::error()` | Ошибки выполнения | ❌ |
| `critical` | `Logger::critical()` | Критические ошибки | 🔥 |

**Фильтрация:**

При установке минимального уровня, логируются только сообщения этого уровня и выше:

```php
Logger::setMinLevel('warning');

// Не логируются:
Logger::debug('...');  
Logger::info('...');   

// Логируются:
Logger::warning('...');  
Logger::error('...');    
Logger::critical('...');  
```

---

## Контекстные данные

Передавайте дополнительные данные вторым параметром:

### Простые значения

```php
Logger::info('User {username} performed action {action}', [
    'username' => 'john_doe',
    'action' => 'login'
]);
```

### Массивы и объекты

Автоматически преобразуются в JSON:

```php
Logger::error('API request failed', [
    'endpoint' => '/api/users',
    'params' => ['id' => 123, 'include' => ['posts', 'comments']],
    'response' => ['error' => 'Not found', 'code' => 404]
]);

// Результат:
// API request failed {"endpoint":"\/api\/users","params":{"id":123,...},...}
```

---

## Множественные каналы

Отправляйте логи одновременно в несколько мест:

### В конфигурации

```php
'channels' => 'file,slack,telegram',
```

или

```php
'channels' => ['file', 'slack', 'telegram'],
```

### Программно

```php
use Core\Logger;
use Core\Logger\FileHandler;
use Core\Logger\SlackHandler;

Logger::addHandler(new FileHandler('/path/to/app.log'));
Logger::addHandler(new SlackHandler('webhook_url', '#logs'));

Logger::error('This goes to both file and Slack');
```

### Стратегии использования

**Development:**
```php
'channels' => 'file',
'min_level' => 'debug',
```

**Staging:**
```php
'channels' => 'file,slack',
'min_level' => 'info',
'drivers' => [
    'slack' => ['min_level' => 'error'] // Только ошибки в Slack
]
```

**Production:**
```php
'channels' => 'file,slack,telegram',
'min_level' => 'warning',
'drivers' => [
    'file' => ['min_level' => 'warning'],
    'slack' => ['min_level' => 'error'],
    'telegram' => ['min_level' => 'critical'] // Только критические в Telegram
]
```

---

## Создание собственного драйвера

### 1. Создайте класс драйвера

```php
<?php

namespace Core\Logger;

class CustomHandler implements LogHandlerInterface
{
    protected string $endpoint;
    protected string $minLevel;
    
    public function __construct(string $endpoint, string $minLevel = 'debug')
    {
        $this->endpoint = $endpoint;
        $this->minLevel = $minLevel;
    }
    
    public function handle(string $level, string $message): void
    {
        // Проверка минимального уровня
        if (!$this->shouldLog($level)) {
            return;
        }
        
        // Ваша логика отправки
        $this->sendLog($level, $message);
    }
    
    protected function shouldLog(string $level): bool
    {
        $levels = ['debug', 'info', 'warning', 'error', 'critical'];
        $currentIndex = array_search($level, $levels);
        $minIndex = array_search($this->minLevel, $levels);
        
        return $currentIndex >= $minIndex;
    }
    
    protected function sendLog(string $level, string $message): void
    {
        // Ваша реализация
        // Например: HTTP запрос к API, запись в базу данных и т.д.
    }
}
```

### 2. Добавьте в Logger::createDriver()

В файле `core/Logger.php`:

```php
protected static function createDriver(string $name, array $config): ?LogHandlerInterface
{
    $driver = $config['driver'] ?? $name;

    try {
        switch ($driver) {
            // ... существующие драйверы ...
            
            case 'custom':
                return new CustomHandler(
                    $config['endpoint'] ?? '',
                    $config['min_level'] ?? 'debug'
                );
            
            default:
                return null;
        }
    } catch (\Exception $e) {
        error_log("Failed to create logger driver '$name': " . $e->getMessage());
        return null;
    }
}
```

### 3. Добавьте в конфигурацию

```php
'drivers' => [
    // ...
    
    'custom' => [
        'driver' => 'custom',
        'endpoint' => env('LOG_CUSTOM_ENDPOINT', ''),
        'min_level' => 'info',
    ],
],
```

### 4. Используйте

```php
'channels' => 'file,custom',
```

---

## Примеры использования

### Логирование исключений

```php
try {
    // Ваш код
} catch (\Exception $e) {
    Logger::error('Exception caught: {message}', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}
```

### Логирование SQL запросов

```php
Logger::debug('SQL Query: {query}', [
    'query' => $sql,
    'bindings' => $bindings,
    'time' => $executionTime . 'ms'
]);
```

### Логирование HTTP запросов

```php
Logger::info('HTTP {method} {url}', [
    'method' => $_SERVER['REQUEST_METHOD'],
    'url' => $_SERVER['REQUEST_URI'],
    'ip' => $_SERVER['REMOTE_ADDR'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT']
]);
```

### Условное логирование

```php
if (Logger::getMinLevel() === 'debug') {
    Logger::debug('Detailed debug info', $debugData);
}
```

---

## Тестирование

Система логирования полностью покрыта тестами. Запустите:

```bash
php vendor/bin/pest tests/Unit/Core/Logger
```

### Тесты включают:

- ✅ Базовое логирование всех уровней
- ✅ Фильтрация по минимальному уровню
- ✅ Контекстные данные и интерполяция
- ✅ Множественные обработчики
- ✅ Инициализация из конфигурации
- ✅ Все драйверы (File, Slack, Telegram)
- ✅ Форматирование сообщений
- ✅ Обработка ошибок

---

## Best Practices

### 1. Используйте правильные уровни

```php
// ❌ Неправильно
Logger::error('User logged in');

// ✅ Правильно
Logger::info('User logged in');
```

### 2. Добавляйте контекст

```php
// ❌ Неправильно
Logger::error('Database error');

// ✅ Правильно
Logger::error('Database connection failed: {error}', [
    'error' => $e->getMessage(),
    'host' => $dbHost,
    'database' => $dbName
]);
```

### 3. Не логируйте чувствительные данные

```php
// ❌ НИКОГДА не логируйте пароли, токены и т.д.
Logger::debug('Login attempt', [
    'username' => $username,
    'password' => $password  // ❌ ОПАСНО!
]);

// ✅ Правильно
Logger::debug('Login attempt', [
    'username' => $username
]);
```

### 4. Настраивайте уровни для production

```php
// Development
'min_level' => 'debug',

// Production
'min_level' => 'warning',
```

### 5. Используйте разные каналы для разных уровней

```php
'drivers' => [
    'file' => ['min_level' => 'debug'],     // Все в файл
    'slack' => ['min_level' => 'error'],    // Ошибки в Slack
    'telegram' => ['min_level' => 'critical'] // Критические в Telegram
]
```

---

## Troubleshooting

### Логи не записываются

1. Проверьте права доступа к директории логов:
```bash
chmod 755 storage/logs
```

2. Проверьте минимальный уровень:
```php
Logger::setMinLevel('debug');
```

3. Проверьте что обработчики добавлены:
```php
var_dump(Logger::getHandlers());
```

### Slack/Telegram не работает

1. Проверьте наличие curl:
```php
var_dump(function_exists('curl_init'));
```

2. Проверьте webhook URL / bot token:
```php
// Проверьте .env файл
echo env('LOG_SLACK_WEBHOOK_URL');
echo env('LOG_TELEGRAM_BOT_TOKEN');
```

3. Проверьте минимальный уровень драйвера:
```php
'slack' => [
    'min_level' => 'debug', // Временно для тестирования
]
```

---

## FAQ

**Q: Можно ли использовать несколько file handlers с разными файлами?**

A: Да! Добавьте их программно:
```php
Logger::addHandler(new FileHandler('/path/to/app.log'));
Logger::addHandler(new FileHandler('/path/to/errors.log'));
```

**Q: Как логировать только определенные уровни в Slack?**

A: Установите `min_level` для драйвера. Например, только `critical`:
```php
'slack' => ['min_level' => 'critical']
```

**Q: Можно ли отключить логирование временно?**

A: Да, установите очень высокий минимальный уровень:
```php
Logger::setMinLevel('critical'); // Или выше
```

**Q: Как очистить обработчики?**

A: Используйте метод clearHandlers():
```php
Logger::clearHandlers();
```

---

## Заключение

Система логирования предоставляет мощный и гибкий инструмент для мониторинга вашего приложения. Используйте правильные уровни, добавляйте контекст и настраивайте каналы под ваши нужды!

Дополнительная информация:
- [Debug System](./Debug.md)
- [Error Handler](./Debug.md#error-handler)
- [Environment](./Env.md)
