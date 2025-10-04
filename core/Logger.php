<?php declare(strict_types=1);

namespace Core;

use Core\Facades\Facade;
use Core\Contracts\LoggerInterface;

/**
 * Logger Facade
 * 
 * Статический фасад для LoggerService
 * Все методы делегируются к LoggerInterface через DI контейнер
 * 
 * @method static void init()
 * @method static void log(string $level, string $message, array $context = [])
 * @method static void debug(string $message, array $context = [])
 * @method static void info(string $message, array $context = [])
 * @method static void warning(string $message, array $context = [])
 * @method static void error(string $message, array $context = [])
 * @method static void critical(string $message, array $context = [])
 * @method static void setMinLevel(string $level)
 * @method static string getMinLevel()
 * @method static array getLogs()
 * @method static array getStats()
 * @method static void clearLogs()
 * 
 * @see \Core\Services\LoggerService
 */
class Logger extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LoggerInterface::class;
    }

    // Дополнительные методы для управления handlers
    public static function addHandler(\Core\Logger\LogHandlerInterface $handler): void
    {
        static::resolveFacadeInstance()->addHandler($handler);
    }

    public static function clearHandlers(): void
    {
        // Для тестирования - очистка и повторная инициализация
        static::clearResolvedInstance();
    }

    public static function getHandlers(): array
    {
        $instance = static::resolveFacadeInstance();
        if (method_exists($instance, 'getHandlers')) {
            return $instance->getHandlers();
        }
        return [];
    }
}
