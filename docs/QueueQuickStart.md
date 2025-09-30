# Queue Quick Start

Быстрый старт для системы очередей.

## Шаг 1: Настройка

### Вариант A: Database (рекомендуется для старта)

В `.env`:
```env
QUEUE_CONNECTION=database
```

Таблица `jobs` создастся автоматически при первом использовании.

### Вариант B: RabbitMQ (production)

1. Установите библиотеку:
```bash
composer require php-amqplib/php-amqplib
```

2. В `.env`:
```env
QUEUE_CONNECTION=rabbitmq
RABBITMQ_HOST=localhost
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest
RABBITMQ_VHOST=/
```

### Вариант C: Redis (быстрый)

1. Установите расширение:
```bash
pecl install redis
```

2. В `.env`:
```env
QUEUE_CONNECTION=redis
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_QUEUE_DB=0
```

## Шаг 2: Включение асинхронных логов

В `.env`:
```env
# Асинхронная отправка в Slack
LOG_SLACK_ASYNC=true
LOG_SLACK_QUEUE=logs

# Асинхронная отправка в Telegram  
LOG_TELEGRAM_ASYNC=true
LOG_TELEGRAM_QUEUE=logs
```

## Шаг 3: Запуск Worker

### Для логов (Slack/Telegram)

```bash
php bin/queue-work.php logs
```

### Для других задач

```bash
php bin/queue-work.php default
```

### В production (с параметрами)

```bash
php bin/queue-work.php logs --memory=256 --timeout=120
```

## Шаг 4: Проверка работы

Отправьте тестовый лог:

```php
use Core\Logger;

Logger::error('Test async logging!', ['test' => true]);
```

Если `async=true`, сообщение попадет в очередь, и worker отправит его в Slack/Telegram.

## Мониторинг очереди

Проверьте размер очереди:

```php
use Core\Queue\QueueManager;

echo "Logs queue size: " . QueueManager::size('logs') . "\n";
```

## Systemd Service (Linux production)

Создайте `/etc/systemd/system/queue-logs.service`:

```ini
[Unit]
Description=Queue Worker - Logs
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
sudo systemctl enable queue-logs
sudo systemctl start queue-logs
sudo systemctl status queue-logs
```

## Windows Service (PowerShell)

Используйте NSSM (Non-Sucking Service Manager):

```powershell
# Скачайте NSSM с https://nssm.cc/
nssm install QueueWorker "C:\OSPanel\modules\php\PHP_8.3\php.exe" "bin\queue-work.php logs --memory=256"
nssm set QueueWorker AppDirectory "C:\OSPanel\home\torrentpier\public"
nssm start QueueWorker
```

## Troubleshooting

### Worker не запускается

Проверьте PHP path:
```bash
which php
# или
where php
```

### Задачи не обрабатываются

1. Проверьте, что worker запущен
2. Проверьте размер очереди: `QueueManager::size('logs')`
3. Проверьте логи: `storage/logs/app.log`

### "Connection refused" (RabbitMQ/Redis)

Проверьте, что сервис запущен:
```bash
# RabbitMQ
sudo systemctl status rabbitmq-server

# Redis
sudo systemctl status redis
```

## Производительность

### Для высоких нагрузок

Запустите несколько worker'ов:

```bash
# Terminal 1
php bin/queue-work.php logs --memory=256

# Terminal 2
php bin/queue-work.php logs --memory=256

# Terminal 3
php bin/queue-work.php logs --memory=256
```

Или через supervisor (3 процесса):
```ini
[program:queue-logs]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/app/bin/queue-work.php logs
numprocs=3
autostart=true
autorestart=true
```

## Следующие шаги

1. Прочитайте полную документацию: `docs/Queue.md`
2. Посмотрите примеры задач в документации
3. Создайте свои собственные Job классы
4. Настройте мониторинг размера очереди

Готово! 🚀
