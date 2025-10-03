<?php declare(strict_types=1);

namespace Core;

use Core\Emailer\EmailDriverInterface;
use Core\Emailer\EmailMessage;
use Core\Emailer\EmailException;
use Core\Emailer\Drivers\SmtpDriver;
use Core\Emailer\Drivers\SendGridDriver;
use Core\Emailer\Drivers\MailgunDriver;
use Core\Emailer\Drivers\LogDriver;

/**
 * Emailer
 * 
 * Менеджер для отправки email с поддержкой различных драйверов
 */
class Emailer
{
    protected static ?EmailDriverInterface $driver = null;
    protected static bool $initialized = false;
    protected static array $sentEmails = []; // История отправленных email для Debug Toolbar
    protected static array $config = [];

    /**
     * Initialize emailer from configuration
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        self::$config = Config::get('mail', []);

        if (empty(self::$config)) {
            // Fallback: use log driver
            self::setDriver(new LogDriver(['path' => LOG_DIR . '/emails.log']));
            self::$initialized = true;
            return;
        }

        // Get default driver name
        $driverName = self::$config['default'] ?? 'log';

        // Create driver instance
        $driver = self::createDriver($driverName);
        
        if ($driver !== null) {
            self::setDriver($driver);
        }

        self::$initialized = true;
    }

    /**
     * Create driver instance
     */
    protected static function createDriver(string $name): ?EmailDriverInterface
    {
        $drivers = self::$config['drivers'] ?? [];
        
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
            Logger::error("Failed to create email driver '{$name}': " . $e->getMessage());
            return null;
        }
    }

    /**
     * Set email driver
     */
    public static function setDriver(EmailDriverInterface $driver): void
    {
        self::$driver = $driver;
    }

    /**
     * Get current driver
     */
    public static function getDriver(): ?EmailDriverInterface
    {
        if (!self::$initialized) {
            self::init();
        }

        return self::$driver;
    }

    /**
     * Send an email message
     */
    public static function send(EmailMessage $message): bool
    {
        if (!self::$initialized) {
            self::init();
        }

        if (self::$driver === null) {
            throw new EmailException('No email driver configured');
        }

        $startTime = microtime(true);

        try {
            $result = self::$driver->send($message);
            
            // Track sent email
            self::$sentEmails[] = [
                'to' => array_column($message->getTo(), 'email'),
                'subject' => $message->getSubject(),
                'driver' => self::$driver->getName(),
                'time' => microtime(true) - $startTime,
                'timestamp' => date('Y-m-d H:i:s'),
                'success' => true,
            ];

            Logger::info('Email sent successfully', [
                'to' => implode(', ', array_column($message->getTo(), 'email')),
                'subject' => $message->getSubject(),
                'driver' => self::$driver->getName(),
            ]);

            return $result;
        } catch (\Exception $e) {
            // Track failed email
            self::$sentEmails[] = [
                'to' => array_column($message->getTo(), 'email'),
                'subject' => $message->getSubject(),
                'driver' => self::$driver ? self::$driver->getName() : 'unknown',
                'time' => microtime(true) - $startTime,
                'timestamp' => date('Y-m-d H:i:s'),
                'success' => false,
                'error' => $e->getMessage(),
            ];

            Logger::error('Failed to send email', [
                'to' => implode(', ', array_column($message->getTo(), 'email')),
                'subject' => $message->getSubject(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Create a new email message
     */
    public static function message(): EmailMessage
    {
        $message = new EmailMessage();

        // Set default from address from config
        $defaultFrom = self::$config['from']['address'] ?? null;
        $defaultFromName = self::$config['from']['name'] ?? '';

        if ($defaultFrom) {
            $message->from($defaultFrom, $defaultFromName);
        }

        return $message;
    }

    /**
     * Quick send helper
     */
    public static function sendTo(string $to, string $subject, string $body, bool $isHtml = true): bool
    {
        $message = self::message()
            ->to($to)
            ->subject($subject)
            ->body($body, $isHtml);

        return self::send($message);
    }

    /**
     * Send email using a view template
     */
    public static function sendView(string $to, string $subject, string $view, array $data = []): bool
    {
        $template = TemplateEngine::getInstance();
        $body = $template->render($view, $data);

        return self::sendTo($to, $subject, $body, true);
    }

    /**
     * Get sent emails history
     */
    public static function getSentEmails(): array
    {
        return self::$sentEmails;
    }

    /**
     * Get statistics
     */
    public static function getStats(): array
    {
        $total = count(self::$sentEmails);
        $successful = count(array_filter(self::$sentEmails, fn($e) => $e['success']));
        $failed = $total - $successful;

        $totalTime = array_sum(array_column(self::$sentEmails, 'time'));

        return [
            'total' => $total,
            'successful' => $successful,
            'failed' => $failed,
            'total_time' => $totalTime,
            'average_time' => $total > 0 ? $totalTime / $total : 0,
        ];
    }

    /**
     * Clear sent emails history (for testing)
     */
    public static function clearHistory(): void
    {
        self::$sentEmails = [];
    }

    /**
     * Reset emailer (for testing)
     */
    public static function reset(): void
    {
        self::$driver = null;
        self::$initialized = false;
        self::$sentEmails = [];
        self::$config = [];
    }
}

