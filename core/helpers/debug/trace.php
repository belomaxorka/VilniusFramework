<?php declare(strict_types=1);

/**
 * Tracing Functions
 */

use Core\Debug;
use Core\Environment;

if (!function_exists('trace')) {
    /**
     * Output backtrace
     * 
     * @param string|null $label Optional label
     * @return void
     */
    function trace(?string $label = null): void
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $output = $label ? "[{$label}] " : '';
        $output .= "Backtrace:\n";

        foreach ($backtrace as $index => $trace) {
            $file = $trace['file'] ?? 'unknown';
            $line = $trace['line'] ?? 0;
            $function = $trace['function'] ?? 'unknown';
            $class = $trace['class'] ?? '';
            $type = $trace['type'] ?? '';

            $output .= "#{$index} {$file}({$line}): {$class}{$type}{$function}()\n";
        }

        if (Environment::isDebug()) {
            Debug::addOutput('<div style="background: #e3f2fd; border: 1px solid #2196f3; margin: 10px; padding: 15px; border-radius: 5px; font-family: monospace;">' .
                '<h4 style="color: #1976d2; margin-top: 0;">Backtrace</h4>' .
                '<pre style="background: white; padding: 10px; border-radius: 3px; overflow-x: auto;">' .
                htmlspecialchars($output) .
                '</pre></div>');
        } else {
            \Core\Logger::debug($output);
        }
    }
}

