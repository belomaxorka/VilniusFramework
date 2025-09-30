# Queue System - Интеграция завершена ✅

## Что было реализовано

### 🎯 Основные компоненты

1. **Queue система** (`core/Queue/`)
   - ✅ `QueueInterface` - интерфейс для всех драйверов
   - ✅ `Job` - базовый класс для задач
   - ✅ `QueueManager` - фасад для работы с очередями
   - ✅ `Worker` - обработчик задач из очереди

2. **Драйверы** (`core/Queue/Drivers/`)
   - ✅ `SyncDriver` - синхронное выполнение (для разработки)
   - ✅ `DatabaseDriver` - очередь в БД
   - ✅ `RabbitMQDriver` - профессиональная очередь (требует php-amqplib)
   - ✅ `RedisDriver` - быстрая in-memory очередь (требует redis extension)

3. **Интеграция с Logger** (`core/Logger/`)
   - ✅ `AsyncSlackHandler` - асинхронная отправка в Slack
   - ✅ `AsyncTelegramHandler` - асинхронная отправка в Telegram
   - ✅ Готовые Job классы для отправки логов

4. **CLI Worker** (`bin/`)
   - ✅ `queue-work.php` - скрипт для обработки очередей
   - ✅ Поддержка graceful shutdown (SIGTERM/SIGINT)
   - ✅ Настраиваемые параметры (память, таймаут, sleep)
   - ✅ Exponential backoff при ошибках

5. **Конфигурация** (`config/`)
   - ✅ `queue.php` - настройки очередей
   - ✅ Обновлен `logging.php` с поддержкой async

6. **Тесты** (`tests/Unit/Core/Queue/`)
   - ✅ `JobTest.php` - тесты базового класса Job
   - ✅ `SyncDriverTest.php` - тесты синхронного драйвера
   - ✅ `QueueManagerTest.php` - тесты менеджера
   - ✅ `WorkerTest.php` - тесты worker'а

7. **Документация** (`docs/`)
   - ✅ `Queue.md` - полная документация
   - ✅ `QueueQuickStart.md` - быстрый старт
   - ✅ `QueueIntegrationREADME.md` - этот файл

## Архитектура решения

```
┌─────────────┐
│ Application │
└──────┬──────┘
       │
       ├─ Logger::error() ──────┐
       │                        │
       ├─ QueueManager::push()  │
       │                        ▼
       │              ┌──────────────────┐
       │              │ AsyncSlackHandler│
       │              │ AsyncTelegramHandler
       │              └────────┬─────────┘
       │                       │
       ▼                       ▼
┌─────────────────┐    ┌────────────┐
│  QueueManager   │───▶│   Driver   │
└─────────────────┘    └─────┬──────┘
                             │
                    ┌────────┴────────┐
                    │                 │
              ┌─────▼─────┐     ┌────▼────┐
              │  Database │     │RabbitMQ │
              └───────────┘     └─────────┘
                    ▲                 ▲
                    │                 │
              ┌─────┴─────────────────┘
              │
         ┌────▼─────┐
         │  Worker  │◀─── CLI: php bin/queue-work.php
         └──────────┘
```

## Преимущества внедрения

### ✅ Решенные проблемы

1. **Производительность**
   - ❌ Было: Отправка в Slack/Telegram блокирует запрос на 3-5 секунд
   - ✅ Стало: Запрос обрабатывается мгновенно, отправка в фоне

2. **Надежность**
   - ❌ Было: Если Slack/Telegram недоступны, логи теряются
   - ✅ Стало: Логи сохраняются в очереди и отправляются позже

3. **Масштабируемость**
   - ❌ Было: Каждый запрос = синхронный HTTP к внешнему API
   - ✅ Стало: Можно запустить N worker'ов для параллельной обработки

4. **Retry механизм**
   - ❌ Было: Нет повторных попыток при ошибках
   - ✅ Стало: Автоматический retry с exponential backoff (до 3 попыток)

## Использование

### Быстрый старт

1. **Включите асинхронную отправку в `config/logging.php`:**

```php
'slack' => [
    // ...
    'async' => true,  // ← Включить очередь
    'queue' => 'logs',
],

'telegram' => [
    // ...
    'async' => true,  // ← Включить очередь
    'queue' => 'logs',
],
```

2. **Запустите worker:**

```bash
php bin/queue-work.php logs
```

3. **Готово!** Теперь все логи в Slack/Telegram отправляются асинхронно.

### Production deployment

#### Linux (Systemd)

```bash
# Создайте service файл
sudo nano /etc/systemd/system/queue-logs.service

# Добавьте:
[Unit]
Description=Queue Worker - Logs
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/your-app
ExecStart=/usr/bin/php bin/queue-work.php logs --memory=256
Restart=always

[Install]
WantedBy=multi-user.target

# Запустите
sudo systemctl enable queue-logs
sudo systemctl start queue-logs
```

#### Windows (NSSM)

```powershell
nssm install QueueWorker "C:\php\php.exe" "bin\queue-work.php logs"
nssm start QueueWorker
```

## Выбор драйвера

### Когда использовать каждый драйвер:

| Драйвер | Когда использовать | Плюсы | Минусы |
|---------|-------------------|-------|--------|
| **Sync** | Локальная разработка, тестирование | Не требует настройки | Не асинхронный |
| **Database** | Малые/средние проекты, shared hosting | Простая настройка, не требует доп. сервисов | Медленнее Redis/RabbitMQ |
| **RabbitMQ** | Production с высокими нагрузками | Надежный, функциональный, масштабируемый | Требует установки RabbitMQ |
| **Redis** | Real-time задачи, высокая скорость | Очень быстрый | Данные в памяти (может потеряться) |

### Рекомендации:

- 🏠 **Разработка**: `sync` или `database`
- 🚀 **Production (низкая нагрузка)**: `database`
- 🔥 **Production (высокая нагрузка)**: `rabbitmq` или `redis`

## Примеры использования

### Создание собственной задачи

```php
use Core\Queue\Job;

class SendEmailJob extends Job
{
    public function __construct(
        protected string $to,
        protected string $subject,
        protected string $body
    ) {
    }

    public function handle(): void
    {
        mail($this->to, $this->subject, $this->body);
    }

    public static function fromData(array $data): self
    {
        return new self(
            $data['to'] ?? '',
            $data['subject'] ?? '',
            $data['body'] ?? ''
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
                'body' => $this->body,
            ],
        ]);
    }
}
```

### Добавление в очередь

```php
use Core\Queue\QueueManager;

// При регистрации пользователя
$job = new SendEmailJob(
    $user->email,
    'Welcome!',
    'Thanks for registering!'
);

QueueManager::push($job, 'emails');
```

### Запуск worker для emails

```bash
php bin/queue-work.php emails
```

## Мониторинг

### Проверка размера очереди

```php
use Core\Queue\QueueManager;

$logsQueueSize = QueueManager::size('logs');
$emailsQueueSize = QueueManager::size('emails');

if ($logsQueueSize > 1000) {
    // Отправить алерт - очередь переполнена
}
```

### Статистика Worker

Worker выводит статистику в консоль:

```
Worker started for queue: logs
Memory limit: 128MB, Timeout: 60s, Sleep: 3s
[2025-09-30 12:34:56] Processing job: SendLogToSlackJob (attempt 1/3)
[2025-09-30 12:34:57] Job completed: SendLogToSlackJob
Worker stopped. Processed 145 jobs.
```

## Требования к зависимостям

### Базовые (работает из коробки)
- PHP 8.1+
- База данных (MySQL/PostgreSQL)

### Опциональные

**Для RabbitMQ:**
```bash
composer require php-amqplib/php-amqplib
```

**Для Redis:**
```bash
# Вариант 1: PHP extension (быстрее)
pecl install redis

# Вариант 2: Predis (чистый PHP)
composer require predis/predis
```

## Тестирование

Запуск всех тестов:

```bash
./vendor/bin/pest tests/Unit/Core/Queue
```

Результат:
```
✓ job can be executed
✓ job can set and get id
✓ job tracks attempts
✓ job detects max attempts exceeded
✓ job can be serialized and unserialized
✓ sync driver executes job immediately
✓ queue manager can push jobs
✓ worker processes successful job
... и т.д.
```

## Дальнейшее развитие

### Возможные улучшения:

1. **Job приоритеты** - разные приоритеты для задач
2. **Delayed jobs** - отложенное выполнение
3. **Job chaining** - цепочки задач
4. **Failed jobs table** - таблица для неудачных задач
5. **Queue monitoring UI** - веб-интерфейс для мониторинга
6. **Job middleware** - промежуточные обработчики
7. **Rate limiting** - ограничение частоты выполнения

## FAQ

**Q: Можно ли использовать несколько драйверов одновременно?**  
A: Сейчас нет, но можно расширить `QueueManager` для поддержки множественных подключений.

**Q: Что делать если worker падает?**  
A: Используйте systemd (Linux) или NSSM (Windows) для автоматического перезапуска.

**Q: Сколько worker'ов нужно запускать?**  
A: Зависит от нагрузки. Начните с 1-2, мониторьте размер очереди, увеличивайте при необходимости.

**Q: Можно ли использовать для long-running задач?**  
A: Да, но увеличьте `--timeout` параметр worker'а.

**Q: Безопасно ли останавливать worker (Ctrl+C)?**  
A: Да, worker завершит текущую задачу перед остановкой (graceful shutdown).

## Заключение

Система очередей полностью интегрирована и протестирована. Теперь ваш фреймворк поддерживает:

- ✅ Асинхронную обработку задач
- ✅ Надежную доставку логов в Slack/Telegram
- ✅ Масштабируемость через множество worker'ов
- ✅ Retry механизм с exponential backoff
- ✅ Поддержку различных backend'ов (DB, RabbitMQ, Redis)

**Производительность улучшена!** 🚀

---

Документация:
- 📖 Полная документация: `docs/Queue.md`
- 🚀 Быстрый старт: `docs/QueueQuickStart.md`
- 📝 Этот файл: `docs/QueueIntegrationREADME.md`
