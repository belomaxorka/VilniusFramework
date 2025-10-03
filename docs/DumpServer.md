# Dump Server - Вывод debug данных в отдельное окно

## Обзор

Dump Server - система для вывода debug информации в отдельное окно/консоль, не засоряя основной вывод приложения. Работает аналогично Symfony VarDumper Server.

### Возможности:
- 🖥️ **Отдельная консоль** - debug данные выводятся в отдельном окне
- 📡 **TCP Socket** - быстрая передача через сокеты
- 🎨 **Форматированный вывод** - красивое отображение
- 🚀 **Не блокирует** - приложение работает без задержек
- ⏱️ **Real-time** - данные отображаются мгновенно

## Быстрый старт

### Шаг 1: Запустить сервер

Откройте отдельное окно терминала и запустите:

```bash
php vilnius dump-server
```

**Вывод:**
```
╔═══════════════════════════════════════════════════════════╗
║                                                           ║
║              🐛 DEBUG DUMP SERVER 🐛                     ║
║                                                           ║
╚═══════════════════════════════════════════════════════════╝

🚀 Dump Server started on 127.0.0.1:9912
Waiting for dumps...
```

### Шаг 2: Отправить данные из приложения

```php
// В вашем коде
server_dump($user, 'User Data');
server_dump($config, 'Configuration');
```

### Шаг 3: Смотреть результат в консоли сервера

```
────────────────────────────────────────────────────────────────────────────────
⏰ 14:23:45 📝 User Data 📍 UserController.php:25
────────────────────────────────────────────────────────────────────────────────
Array
(
    [id] => 1
    [name] => John Doe
    [email] => john@example.com
)
```

## Установка

### 1. Создать команду запуска

Файл уже создан: `bin/dump-server.php`

### 2. Добавить в composer.json (опционально)

```json
{
    "scripts": {
        "dump-server": "php bin/dump-server.php"
    }
}
```

Теперь можно запускать:
```bash
composer dump-server
```

## Использование

### Базовые функции

#### server_dump(mixed $data, ?string $label = null): bool
Отправляет данные на dump server.

```php
server_dump($variable);
server_dump($user, 'User Object');
server_dump(['key' => 'value'], 'Config');
```

#### dd_server(mixed $data, ?string $label = null): never
Dump to server and die.

```php
dd_server($data, 'Debug and Exit');
// Скрипт завершится после отправки
```

#### dump_server_available(): bool
Проверяет доступность сервера.

```php
if (dump_server_available()) {
    server_dump($data);
} else {
    dump($data); // fallback
}
```

### Класс DumpClient

Для прямого использования:

```php
use Core\DumpClient;

// Настройка
DumpClient::configure('127.0.0.1', 9912, 1);

// Отправка
DumpClient::dump($data, 'Label');
DumpClient::send($data, 'Label', 'custom_type');

// Проверка
if (DumpClient::isServerAvailable()) {
    DumpClient::dump($data);
}

// Включение/выключение
DumpClient::enable(false);
```

### Класс DumpServer

Для программного запуска:

```php
use Core\DumpServer;

// Настройка
DumpServer::configure('127.0.0.1', 9912);

// Запуск (блокирующий)
DumpServer::start();

// С кастомным обработчиком
DumpServer::start(function($data) {
    // Ваша обработка данных
    echo "Received: " . json_encode($data) . "\n";
});

// Остановка
DumpServer::stop();

// Проверка доступности
if (DumpServer::isAvailable()) {
    echo "Server is running\n";
}
```

## Настройка

### Порт и хост

**Сервер:**
```bash
php bin/dump-server.php --host=127.0.0.1 --port=9913
```

**Клиент:**
```php
DumpClient::configure('127.0.0.1', 9913);
```

### Timeout

```php
// Установить timeout для подключения (в секундах)
DumpClient::configure('127.0.0.1', 9912, 2);
```

### Включение/выключение

```php
// Глобально отключить отправку на сервер
DumpClient::enable(false);

// Включить обратно
DumpClient::enable(true);
```

## Команда dump-server

### Использование

```bash
php bin/dump-server.php [options]
```

### Опции

- `--host=HOST` - Хост сервера (по умолчанию: 127.0.0.1)
- `--port=PORT` - Порт сервера (по умолчанию: 9912)
- `--help, -h` - Показать справку

### Примеры

```bash
# Стандартный запуск
php bin/dump-server.php

# На другом порту
php bin/dump-server.php --port=9913

# На другом хосте
php bin/dump-server.php --host=0.0.0.0 --port=9914

# Справка
php bin/dump-server.php --help
```

### Остановка сервера

Нажмите `Ctrl+C` для остановки.

## Примеры использования

### Пример 1: Debug в development

```php
// В коде приложения
public function processData($data) 
{
    server_dump($data, 'Input Data');
    
    $processed = transform($data);
    server_dump($processed, 'Processed Data');
    
    return $processed;
}
```

**В консоли сервера:**
```
────────────────────────────────────────────────────────────
⏰ 14:30:12 📝 Input Data 📍 DataService.php:15
────────────────────────────────────────────────────────────
Array ( ... )

────────────────────────────────────────────────────────────
⏰ 14:30:12 📝 Processed Data 📍 DataService.php:18
────────────────────────────────────────────────────────────
Array ( ... )
```

### Пример 2: API Debugging

```php
class ApiController 
{
    public function handle($request) 
    {
        server_dump($request->all(), 'API Request');
        
        $response = $this->process($request);
        
        server_dump($response, 'API Response');
        
        return $response;
    }
}
```

### Пример 3: Условный вывод

```php
if (dump_server_available()) {
    // Сервер запущен - отправляем туда
    server_dump($complexData, 'Complex Data');
} else {
    // Сервер не запущен - обычный dump
    dump($complexData, 'Complex Data');
}
```

### Пример 4: Fallback стратегия

```php
function smart_dump($data, $label = null) 
{
    if (dump_server_available()) {
        return server_dump($data, $label);
    }
    
    dump($data, $label);
    return true;
}

// Использование
smart_dump($user, 'User Data');
```

### Пример 5: Debug и exit

```php
public function criticalOperation($data) 
{
    if (!$this->validate($data)) {
        dd_server($data, 'Invalid Data');
        // Скрипт завершится
    }
    
    // Продолжение только если валидно
    $this->process($data);
}
```

### Пример 6: Множественные серверы

```php
// Dev сервер
DumpClient::configure('127.0.0.1', 9912);
server_dump($devData, 'Dev Data');

// Test сервер
DumpClient::configure('127.0.0.1', 9913);
server_dump($testData, 'Test Data');
```

### Пример 7: Кастомный обработчик

```php
// Запуск с кастомным форматированием
DumpServer::start(function($data) {
    $label = $data['label'] ?? 'Dump';
    $time = date('H:i:s', (int)$data['timestamp']);
    
    echo "\n[{$time}] {$label}:\n";
    echo json_encode($data['content'], JSON_PRETTY_PRINT) . "\n";
});
```

## Интеграция

### С Docker

```yaml
# docker-compose.yml
services:
  app:
    # ...
    
  dump-server:
    build: .
    command: php bin/dump-server.php --host=0.0.0.0
    ports:
      - "9912:9912"
```

### С supervisor

```ini
; /etc/supervisor/conf.d/dump-server.conf
[program:dump-server]
command=php /path/to/project/bin/dump-server.php
autostart=true
autorestart=true
user=www-data
stdout_logfile=/var/log/dump-server.log
```

### С systemd

```ini
; /etc/systemd/system/dump-server.service
[Unit]
Description=Dump Server
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/project
ExecStart=/usr/bin/php bin/dump-server.php
Restart=always

[Install]
WantedBy=multi-user.target
```

## Советы и Best Practices

### 1. Запускайте сервер в отдельном терминале

```bash
# Terminal 1: Dump Server
php bin/dump-server.php

# Terminal 2: Ваше приложение
php artisan serve
```

### 2. Используйте для сложных объектов

```php
// Вместо var_dump в основном выводе
server_dump($complexObject, 'Complex Object');
```

### 3. Проверяйте доступность

```php
if (dump_server_available()) {
    server_dump($data);
}
```

### 4. Не используйте в production

```php
// Автоматически отключено в production
if (is_dev()) {
    server_dump($data);
}
```

### 5. Комбинируйте с контекстами

```php
context_run('api', function() use ($request) {
    server_dump($request, 'API Request');
    
    $response = processRequest($request);
    
    server_dump($response, 'API Response');
});
```

## Отличия от обычного dump()

| Функция | Обычный dump() | server_dump() |
|---------|----------------|---------------|
| **Вывод** | В основной поток | В отдельное окно |
| **Блокировка** | Может засорять вывод | Не мешает выводу |
| **Форматирование** | HTML в браузере | Текст в консоли |
| **Производительность** | Замедляет страницу | Минимальный оверхед |
| **Удобство** | Нужно искать в HTML | Сразу видно в консоли |

## Production Mode

В production Dump Server **отключен**:

```php
// В production
Environment::set(Environment::PRODUCTION);

server_dump($data); // вернет false, ничего не отправит
```

Это гарантирует:
- ⚡ Ноль оверхеда
- 🔒 Безопасность (нет отправки данных)
- 🚀 Не блокирует выполнение

## Troubleshooting

### Сервер не запускается

**Проблема:** `Failed to start server: Address already in use`

**Решение:**
```bash
# Порт уже занят, используйте другой
php bin/dump-server.php --port=9913
```

### Данные не отправляются

**Проблема:** `server_dump()` возвращает `false`

**Решение:**
```php
// 1. Проверьте что сервер запущен
dump_server_available(); // true?

// 2. Проверьте режим
var_dump(Environment::isDevelopment()); // true?

// 3. Проверьте что клиент включен
DumpClient::enable(true);

// 4. Проверьте порт
DumpClient::configure('127.0.0.1', 9912);
```

### Сервер не отображает данные

**Проблема:** Сервер работает, но ничего не показывает

**Решение:**
```php
// Убедитесь что отправляете данные ПОСЛЕ запуска сервера
// И что порты совпадают
```

### Timeout ошибки

**Проблема:** Долго ждет подключения

**Решение:**
```php
// Уменьшите timeout
DumpClient::configure('127.0.0.1', 9912, 0.5);
```

### Сервер падает при получении данных

**Проблема:** Fatal error в сервере

**Решение:**
```php
// Используйте кастомный обработчик с try-catch
DumpServer::start(function($data) {
    try {
        // Ваша обработка
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
});
```

## FAQ

**Q: Зачем нужен Dump Server?**

A: Чтобы не засорять основной вывод (HTML/JSON) debug информацией. Все выводится в отдельное окно.

**Q: Можно ли использовать удаленно?**

A: Да, укажите IP сервера:
```php
DumpClient::configure('192.168.1.100', 9912);
```

**Q: Безопасно ли в production?**

A: Dump Server автоматически отключен в production. Никакие данные не отправляются.

**Q: Как остановить сервер?**

A: Нажмите `Ctrl+C` в терминале сервера.

**Q: Сколько клиентов может подключиться?**

A: Неограниченно. Сервер обрабатывает подключения последовательно.

**Q: Можно ли запустить несколько серверов?**

A: Да, на разных портах:
```bash
php bin/dump-server.php --port=9912
php bin/dump-server.php --port=9913
```

**Q: Как интегрировать с IDE?**

A: Запустите сервер в терминале IDE. Многие IDE (PHPStorm, VSCode) поддерживают встроенные терминалы.

**Q: Влияет ли на производительность?**

A: Минимально. Отправка данных асинхронна и не блокирует выполнение.

## Сравнение с другими решениями

### vs. var_dump()
- ✅ Не засоряет вывод
- ✅ Удобнее для больших данных
- ✅ Форматированный вывод

### vs. Debug Toolbar
- ✅ Не встраивается в HTML
- ✅ Подходит для API/Console
- ❌ Требует запуска сервера

### vs. Logger
- ✅ Real-time отображение
- ✅ Для debug, не для production логов
- ❌ Не сохраняется в файлы

## Заключение

Dump Server - удобный инструмент для:

- ✅ Чистого debug без засорения вывода
- ✅ Отладки API endpoints
- ✅ Console приложений
- ✅ Real-time мониторинга
- ✅ Работы с большими данными

Используйте Dump Server для эффективной разработки! 🖥️🚀
