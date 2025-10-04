<?php declare(strict_types=1);

namespace Core;

use Core\Facades\Facade;
use Core\Contracts\EmailerInterface;
use Core\Emailer\EmailMessage;
use Core\Emailer\EmailDriverInterface;

/**
 * Emailer Facade
 * 
 * Статический фасад для EmailerService
 * Все методы делегируются к EmailerInterface через DI контейнер
 * 
 * @method static void init()
 * @method static void setDriver(EmailDriverInterface $driver)
 * @method static EmailDriverInterface|null getDriver()
 * @method static bool send(EmailMessage $message)
 * @method static EmailMessage message()
 * @method static bool sendTo(string $to, string $subject, string $body, bool $isHtml = true)
 * @method static bool sendView(string $to, string $subject, string $view, array $data = [])
 * @method static array getSentEmails()
 * @method static array getStats()
 * @method static void clearHistory()
 * @method static void reset()
 * 
 * @see \Core\Services\EmailerService
 */
class Emailer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return EmailerInterface::class;
    }
}

