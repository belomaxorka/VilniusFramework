<?php declare(strict_types=1);

namespace Core\Services;

use Core\Contracts\EmailerInterface;
use Core\Contracts\ConfigInterface;
use Core\Contracts\LoggerInterface;
use Core\Emailer\EmailDriverInterface;
use Core\Emailer\EmailMessage;
use Core\Emailer\EmailException;
use Core\Emailer\Drivers\SmtpDriver;
use Core\Emailer\Drivers\SendGridDriver;
use Core\Emailer\Drivers\MailgunDriver;
use Core\Emailer\Drivers\LogDriver;
use Core\TemplateEngine;

/**
 * Emailer Service
 * 
 * Сервис для отправки email с поддержкой различных драйверов
 */
class EmailerService implements EmailerInterface
{
    protected ?EmailDriverInterface $driver = null;
    protected bool $initialized = false;
    protected array $sentEmails = []; // История отправленных email для Debug Toolbar
    protected array $config = [];

    public function __construct(
        protected ConfigInterface $configService,
        protected LoggerInterface $logger
    ) {}

    /**
     * Инициализировать emailer из конфигурации
     */
    public function init(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->config = $this->configService->get('mail', []);

        if (empty($this->config)) {
            // Fallback: use log driver
            $this->setDriver(new LogDriver(['path' => LOG_DIR . '/emails.log']));
            $this->initialized = true;
            return;
        }

        // Get default driver name
        $driverName = $this->config['default'] ?? 'log';

        // Create driver instance
        $driver = $this->createDriver($driverName);
        
        if ($driver !== null) {
            $this->setDriver($driver);
        }

        $this->initialized = true;
    }

    /**
     * Создать экземпляр драйвера
     */
    protected function createDriver(string $name): ?EmailDriverInterface
    {
        $drivers = $this->config['drivers'] ?? [];
        
        if (!isset($drivers[$name])) {
            throw new EmailException("Email driver '{$name}' is not configured");
        }

        $config = $drivers[$name];
        $driver = $config['driver'] ?? $name;

        try {
            return match ($driver) {
                'smtp' => new SmtpDriver($config),
                'sendgrid' => new SendGridDriver($config),
                'mailgun' => new MailgunDriver($config),
                'log' => new LogDriver($config),
                default => throw new EmailException("Unknown email driver: {$driver}"),
            };
        } catch (\Exception $e) {
            $this->logger->error("Failed to create email driver '{$name}': " . $e->getMessage());
            return null;
        }
    }

    /**
     * Установить драйвер email
     */
    public function setDriver(EmailDriverInterface $driver): void
    {
        $this->driver = $driver;
    }

    /**
     * Получить текущий драйвер
     */
    public function getDriver(): ?EmailDriverInterface
    {
        if (!$this->initialized) {
            $this->init();
        }

        return $this->driver;
    }

    /**
     * Отправить email сообщение
     */
    public function send(EmailMessage $message): bool
    {
        if (!$this->initialized) {
            $this->init();
        }

        if ($this->driver === null) {
            throw new EmailException('No email driver configured');
        }

        $startTime = microtime(true);

        try {
            $result = $this->driver->send($message);
            
            // Track sent email
            $this->sentEmails[] = [
                'to' => array_column($message->getTo(), 'email'),
                'subject' => $message->getSubject(),
                'driver' => $this->driver->getName(),
                'time' => microtime(true) - $startTime,
                'timestamp' => date('Y-m-d H:i:s'),
                'success' => true,
            ];

            $this->logger->info('Email sent successfully', [
                'to' => implode(', ', array_column($message->getTo(), 'email')),
                'subject' => $message->getSubject(),
                'driver' => $this->driver->getName(),
            ]);

            return $result;
        } catch (\Exception $e) {
            // Track failed email
            $this->sentEmails[] = [
                'to' => array_column($message->getTo(), 'email'),
                'subject' => $message->getSubject(),
                'driver' => $this->driver ? $this->driver->getName() : 'unknown',
                'time' => microtime(true) - $startTime,
                'timestamp' => date('Y-m-d H:i:s'),
                'success' => false,
                'error' => $e->getMessage(),
            ];

            $this->logger->error('Failed to send email', [
                'to' => implode(', ', array_column($message->getTo(), 'email')),
                'subject' => $message->getSubject(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Создать новое email сообщение
     */
    public function message(): EmailMessage
    {
        $message = new EmailMessage();

        // Set default from address from config
        $defaultFrom = $this->config['from']['address'] ?? null;
        $defaultFromName = $this->config['from']['name'] ?? '';

        if ($defaultFrom) {
            $message->from($defaultFrom, $defaultFromName);
        }

        return $message;
    }

    /**
     * Быстрая отправка email
     */
    public function sendTo(string $to, string $subject, string $body, bool $isHtml = true): bool
    {
        $message = $this->message()
            ->to($to)
            ->subject($subject)
            ->body($body, $isHtml);

        return $this->send($message);
    }

    /**
     * Отправить email используя view шаблон
     */
    public function sendView(string $to, string $subject, string $view, array $data = []): bool
    {
        $template = TemplateEngine::getInstance();
        $body = $template->render($view, $data);

        return $this->sendTo($to, $subject, $body, true);
    }

    /**
     * Получить историю отправленных email
     */
    public function getSentEmails(): array
    {
        return $this->sentEmails;
    }

    /**
     * Получить статистику
     */
    public function getStats(): array
    {
        $total = count($this->sentEmails);
        $successful = count(array_filter($this->sentEmails, fn($e) => $e['success']));
        $failed = $total - $successful;

        $totalTime = array_sum(array_column($this->sentEmails, 'time'));

        return [
            'total' => $total,
            'successful' => $successful,
            'failed' => $failed,
            'total_time' => $totalTime,
            'average_time' => $total > 0 ? $totalTime / $total : 0,
        ];
    }

    /**
     * Очистить историю отправленных email (для тестирования)
     */
    public function clearHistory(): void
    {
        $this->sentEmails = [];
    }

    /**
     * Сбросить emailer (для тестирования)
     */
    public function reset(): void
    {
        $this->driver = null;
        $this->initialized = false;
        $this->sentEmails = [];
        $this->config = [];
    }
}

