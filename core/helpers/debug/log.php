<?php declare(strict_types=1);

/**
 * Debug Logging Functions
 */

use Core\Environment;

if (!function_exists('debug_log')) {
    /**
     * Log message only in debug mode
     * 
     * @param string $message Message to log
     * @return void
     */
    function debug_log(string $message): void
    {
        if (Environment::isDebug()) {
            \Core\Logger::debug($message);
        }
    }
}

