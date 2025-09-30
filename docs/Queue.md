# Queue System

Полнофункциональная система очередей для асинхронной обработки задач.

## Содержание

- [Возможности](#возможности)
- [Установка](#установка)
- [Конфигурация](#конфигурация)
- [Драйверы](#драйверы)
- [Использование](#использование)
- [Создание задач](#создание-задач)
- [Worker](#worker)
- [Интеграция с Logger](#интеграция-с-logger)

## Возможности

- 🚀 **Асинхронная обработка** - задачи выполняются в фоне, не блокируя основной поток
- 🔄 **Автоматические повторы** - с exponential backoff при ошибках
- 🎯 **Множество драйверов** - Sync, Database, RabbitMQ, Redis
- 📊 **Мониторинг** - статистика выполнения задач
- ⚡ **Graceful shutdown** - корректное завершение работы worker'а
- 🔌 **Легкая интеграция** - готовая интеграция с Logger

## Установка

### Базовая установка (Sync драйвер)

Не требует дополнительных зависимостей - работает из коробки.

### Database драйвер

Автоматически создает таблицу `jobs` при первом использовании.

### RabbitMQ драйвер

```bash
composer require php-amqplib/php-amqplib
```

### Redis драйвер

```bash
# Расширение PHP Redis
pecl install redis

# Или через Composer (альтернатива)
composer require predis/predis
```

## Конфигурация

Создайте файл `config/queue.php`:

```php
return [
    'default' => env('QUEUE_CONNECTION', 'database'),
    
    'connections' => [
        'sync' => [
            'driver' => 'sync',
        ],
        
        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
        ],
        
        'rabbitmq' => [
            'driver' => 'rabbitmq',
            'host' => env('RABBITMQ_HOST', 'localhost'),
            'port' => env('RABBITMQ_PORT', 5672),
            'user' => env('RABBITMQ_USER', 'guest'),
            'password' => env('RABBITMQ_PASSWORD', 'guest'),
            'vhost' => env('RABBITMQ_VHOST', '/'),
        ],
        
        'redis' => [
            'driver' => 'redis',
            'host' => env('REDIS_HOST', 'localhost'),
            'port' => env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD', null),
            'database' => env('REDIS_QUEUE_DB', 0),
        ],
    ],
];
```

## Драйверы

### Sync Driver

Выполняет задачи сразу же (синхронно). Идеально для разработки и тестирования.

```php
'default' => 'sync'
```

### Database Driver

Хранит задачи в MySQL/PostgreSQL. Хорошо для небольших и средних нагрузок.

```php
'default' => 'database'
```

### RabbitMQ Driver

Профессиональная очередь сообщений. Лучший выбор для production с высокими нагрузками.

```php
'default' => 'rabbitmq'
```

### Redis Driver

Быстрая in-memory очередь. Отлично для real-time задач.

```php
'default' => 'redis'
```

## Использование

### Инициализация

```php
use Core\Queue\QueueManager;

// В bootstrap вашего приложения
QueueManager::init();
```

### Добавление задачи в очередь

```php
use Core\Queue\QueueManager;

$job = new SendEmailJob('user@example.com', 'Subject', 'Message');
QueueManager::push($job, 'emails');
```

## Создание задач

### Простая задача

```php
use Core\Queue\Job;

class SendEmailJob extends Job
{
    public function __construct(
        protected string $to,
        protected string $subject,
        protected string $message
    ) {
    }

    public function handle(): void
    {
        // Отправка email
        mail($this->to, $this->subject, $this->message);
    }

    // Для сериализации
    public static function fromData(array $data): self
    {
        return new self(
            $data['to'] ?? '',
            $data['subject'] ?? '',
            $data['message'] ?? ''
        );
    }

    public function serialize(): string
    {
        return json_encode([
            'class' => get_class($this),
            'id' => $this->id,
            'attempts' => $this->attempts,
            'maxAttempts' => $this->maxAttempts,
            'data' => [
                'to' => $this->to,
                'subject' => $this->subject,
                'message' => $this->message,
            ],
        ], JSON_THROW_ON_ERROR);
    }
}
```

### Настройка количества попыток

```php
class ImportDataJob extends Job
{
    protected int $maxAttempts = 5; // 5 попыток вместо 3

    public function handle(): void
    {
        // Импорт данных
    }
}
```

## Worker

Worker обрабатывает задачи из очереди в фоновом режиме.

### Запуск Worker

```bash
# Обработка default очереди
php bin/queue-work.php

# Обработка конкретной очереди
php bin/queue-work.php emails

# С параметрами
php bin/queue-work.php emails --max-jobs=100 --memory=256 --timeout=120 --sleep=5
```

### Параметры Worker

- `--max-jobs=N` - Максимальное количество задач (0 = бесконечно)
- `--memory=N` - Лимит памяти в MB (по умолчанию 128)
- `--timeout=N` - Таймаут выполнения задачи в секундах (по умолчанию 60)
- `--sleep=N` - Пауза между проверками очереди в секундах (по умолчанию 3)

### Запуск в production

#### Systemd (Linux)

Создайте файл `/etc/systemd/system/queue-worker.service`:

```ini
[Unit]
Description=Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/your-app
ExecStart=/usr/bin/php bin/queue-work.php logs --memory=256
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Запуск:

```bash
sudo systemctl enable queue-worker
sudo systemctl start queue-worker
sudo systemctl status queue-worker
```

#### Supervisor (альтернатива)

```ini
[program:queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/your-app/bin/queue-work.php logs
autostart=true
autorestart=true
user=www-data
numprocs=3
redirect_stderr=true
stdout_logfile=/var/www/your-app/storage/logs/worker.log
```

## Интеграция с Logger

Система очередей интегрирована с Logger для асинхронной отправки логов в Slack/Telegram.

### Включение асинхронной отправки

В `config/logging.php`:

```php
'slack' => [
    'driver' => 'slack',
    'webhook_url' => env('LOG_SLACK_WEBHOOK_URL', ''),
    'async' => true,  // ← Включить очередь
    'queue' => 'logs', // ← Имя очереди
    // ...
],

'telegram' => [
    'driver' => 'telegram',
    'bot_token' => env('LOG_TELEGRAM_BOT_TOKEN', ''),
    'chat_id' => env('LOG_TELEGRAM_CHAT_ID', ''),
    'async' => true,  // ← Включить очередь
    'queue' => 'logs', // ← Имя очереди
    // ...
],
```

### Запуск Worker для логов

```bash
php bin/queue-work.php logs
```

Теперь логи в Slack/Telegram отправляются асинхронно, не замедляя ваше приложение!

## Примеры

### Пример 1: Отправка email

```php
// Создаем задачу
class SendWelcomeEmailJob extends Job
{
    public function __construct(
        protected int $userId
    ) {
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        mail($user->email, 'Welcome!', 'Welcome to our app!');
    }

    public static function fromData(array $data): self
    {
        return new self($data['userId'] ?? 0);
    }

    public function serialize(): string
    {
        return json_encode([
            'class' => get_class($this),
            'id' => $this->id,
            'attempts' => $this->attempts,
            'maxAttempts' => $this->maxAttempts,
            'data' => ['userId' => $this->userId],
        ]);
    }
}

// Добавляем в очередь при регистрации
QueueManager::push(new SendWelcomeEmailJob($user->id), 'emails');
```

### Пример 2: Обработка изображений

```php
class ResizeImageJob extends Job
{
    public function __construct(
        protected string $imagePath,
        protected int $width,
        protected int $height
    ) {
    }

    public function handle(): void
    {
        $image = imagecreatefromjpeg($this->imagePath);
        $resized = imagescale($image, $this->width, $this->height);
        imagejpeg($resized, $this->imagePath);
        imagedestroy($image);
        imagedestroy($resized);
    }
}

// Добавляем в очередь
QueueManager::push(new ResizeImageJob('/path/to/image.jpg', 800, 600), 'images');
```

### Пример 3: Экспорт данных

```php
class ExportUsersJob extends Job
{
    protected int $maxAttempts = 1; // Не повторять при ошибке

    public function handle(): void
    {
        $users = User::all();
        $csv = fopen('/tmp/users.csv', 'w');
        
        foreach ($users as $user) {
            fputcsv($csv, [$user->id, $user->name, $user->email]);
        }
        
        fclose($csv);
    }
}
```

## Best Practices

1. **Используйте правильный драйвер**
   - Разработка: `sync` или `database`
   - Production (низкая нагрузка): `database`
   - Production (высокая нагрузка): `rabbitmq` или `redis`

2. **Разделяйте очереди**
   ```php
   QueueManager::push($emailJob, 'emails');
   QueueManager::push($imageJob, 'images');
   QueueManager::push($logJob, 'logs');
   ```

3. **Запускайте несколько worker'ов**
   ```bash
   # Worker для emails
   php bin/queue-work.php emails
   
   # Worker для images
   php bin/queue-work.php images
   
   # Worker для logs
   php bin/queue-work.php logs
   ```

4. **Мониторьте размер очереди**
   ```php
   $size = QueueManager::size('emails');
   if ($size > 1000) {
       // Отправить алерт
   }
   ```

5. **Используйте idempotent задачи**
   - Задачи должны безопасно выполняться повторно
   - Проверяйте состояние перед выполнением

## Устранение проблем

### Worker не обрабатывает задачи

1. Проверьте, что worker запущен:
   ```bash
   ps aux | grep queue-work
   ```

2. Проверьте логи:
   ```bash
   tail -f storage/logs/app.log
   ```

3. Проверьте размер очереди:
   ```php
   echo QueueManager::size('default');
   ```

### Задачи постоянно падают

1. Увеличьте таймаут:
   ```bash
   php bin/queue-work.php default --timeout=300
   ```

2. Увеличьте память:
   ```bash
   php bin/queue-work.php default --memory=512
   ```

3. Проверьте логи ошибок задачи

### RabbitMQ connection failed

1. Проверьте, что RabbitMQ запущен:
   ```bash
   sudo systemctl status rabbitmq-server
   ```

2. Проверьте credentials в `.env`:
   ```
   RABBITMQ_HOST=localhost
   RABBITMQ_PORT=5672
   RABBITMQ_USER=guest
   RABBITMQ_PASSWORD=guest
   ```

## Тестирование

Запуск тестов:

```bash
./vendor/bin/pest tests/Unit/Core/Queue
```

Примеры тестов в `tests/Unit/Core/Queue/`.
