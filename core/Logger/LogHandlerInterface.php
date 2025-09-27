<?php declare(strict_types=1);

namespace Core\Logger;

interface LogHandlerInterface
{
    /**
     * Handle a log message
     *
     * @param string $level Log level (debug, info, warning, error)
     * @param string $message Log message
     */
    public function handle(string $level, string $message): void;
}
