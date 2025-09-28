<?php declare(strict_types=1);

namespace Core;

use Core\Logger\LogHandlerInterface;

class Logger
{
    protected static array $handlers = [];
    protected static string $minLevel = 'debug';
    protected static array $levels = ['debug', 'info', 'warning', 'error'];

    public static function addHandler(LogHandlerInterface $handler): void
    {
        self::$handlers[] = $handler;
    }

    public static function log(string $level, string $message): void
    {
        $level = strtolower($level);
        if (!in_array($level, self::$levels)) {
            $level = 'info';
        }
        if (array_search($level, self::$levels) < array_search(self::$minLevel, self::$levels)) {
            return;
        }

        foreach (self::$handlers as $handler) {
            $handler->handle($level, $message);
        }
    }

    public static function debug(string $message)
    {
        self::log('debug', $message);
    }

    public static function info(string $message)
    {
        self::log('info', $message);
    }

    public static function warning(string $message)
    {
        self::log('warning', $message);
    }

    public static function error(string $message)
    {
        self::log('error', $message);
    }

    public static function setMinLevel(string $level): void
    {
        self::$minLevel = $level;
    }
}
