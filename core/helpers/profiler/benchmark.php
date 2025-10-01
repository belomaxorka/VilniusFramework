<?php declare(strict_types=1);

/**
 * Benchmark Functions
 */

use Core\Debug;
use Core\Environment;

if (!function_exists('benchmark')) {
    /**
     * Measure execution time of a function
     * 
     * @param callable $callback Function to measure
     * @param string|null $label Optional label
     * @return mixed Return value of the callback
     */
    function benchmark(callable $callback, ?string $label = null): mixed
    {
        $start = microtime(true);
        $result = $callback();
        $end = microtime(true);
        $time = ($end - $start) * 1000; // milliseconds

        $message = ($label ? "[{$label}] " : '') . "Execution time: " . number_format($time, 2) . "ms";

        if (Environment::isDebug()) {
            Debug::addOutput('<div style="background: #f3e5f5; border: 1px solid #9c27b0; margin: 10px; padding: 15px; border-radius: 5px; font-family: monospace;">' .
                '<h4 style="color: #7b1fa2; margin-top: 0;">Benchmark</h4>' .
                '<p style="margin: 0; color: #4a148c;">' . htmlspecialchars($message) . '</p>' .
                '</div>');
        } else {
            \Core\Logger::debug($message);
        }

        return $result;
    }
}

