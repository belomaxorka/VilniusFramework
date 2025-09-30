<?php declare(strict_types=1);

use Core\Debug;
use Core\Environment;

if (!function_exists('dd')) {
    /**
     * Dump and die - выводит переменную и останавливает выполнение
     */
    function dd(mixed $var, ?string $label = null): never
    {
        Debug::dump($var, $label, true);
    }
}

if (!function_exists('dump')) {
    /**
     * Dump - выводит переменную без остановки выполнения
     */
    function dump(mixed $var, ?string $label = null): void
    {
        Debug::dump($var, $label, false);
    }
}

if (!function_exists('dump_pretty')) {
    /**
     * Dump pretty - выводит переменную с красивым форматированием
     */
    function dump_pretty(mixed $var, ?string $label = null): void
    {
        Debug::dumpPretty($var, $label, false);
    }
}

if (!function_exists('dd_pretty')) {
    /**
     * Dump pretty and die - выводит переменную с красивым форматированием и останавливает выполнение
     */
    function dd_pretty(mixed $var, ?string $label = null): never
    {
        Debug::dumpPretty($var, $label, true);
    }
}

if (!function_exists('collect')) {
    /**
     * Collect - собирает данные для дебага без вывода
     */
    function collect(mixed $var, ?string $label = null): void
    {
        Debug::collect($var, $label);
    }
}

if (!function_exists('dump_all')) {
    /**
     * Dump all - выводит все собранные данные
     */
    function dump_all(bool $die = false): void
    {
        Debug::dumpAll($die);
    }
}

if (!function_exists('clear_debug')) {
    /**
     * Clear debug - очищает собранные данные
     */
    function clear_debug(): void
    {
        Debug::clear();
    }
}

if (!function_exists('is_debug')) {
    /**
     * Is debug - проверяет, включен ли режим отладки
     */
    function is_debug(): bool
    {
        return Environment::isDebug();
    }
}

if (!function_exists('is_dev')) {
    /**
     * Is development - проверяет, является ли окружение разработкой
     */
    function is_dev(): bool
    {
        return Environment::isDevelopment();
    }
}

if (!function_exists('is_prod')) {
    /**
     * Is production - проверяет, является ли окружение продакшеном
     */
    function is_prod(): bool
    {
        return Environment::isProduction();
    }
}

if (!function_exists('env')) {
    /**
     * Env - получает переменную окружения
     */
    function env(string $key, mixed $default = null): mixed
    {
        return \Core\Env::get($key, $default);
    }
}

if (!function_exists('debug_log')) {
    /**
     * Debug log - логирует сообщение только в режиме отладки
     */
    function debug_log(string $message): void
    {
        if (Environment::isDebug()) {
            \Core\Logger::debug($message);
        }
    }
}

if (!function_exists('trace')) {
    /**
     * Trace - выводит backtrace
     */
    function trace(?string $label = null): void
    {
        if (!Environment::isDebug()) {
            return;
        }

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

        if (Environment::isDevelopment()) {
            echo '<div style="background: #e3f2fd; border: 1px solid #2196f3; margin: 10px; padding: 15px; border-radius: 5px; font-family: monospace;">';
            echo '<h4 style="color: #1976d2; margin-top: 0;">Backtrace</h4>';
            echo '<pre style="background: white; padding: 10px; border-radius: 3px; overflow-x: auto;">';
            echo htmlspecialchars($output);
            echo '</pre></div>';
        } else {
            \Core\Logger::debug($output);
        }
    }
}

if (!function_exists('benchmark')) {
    /**
     * Benchmark - измеряет время выполнения функции
     */
    function benchmark(callable $callback, ?string $label = null): mixed
    {
        if (!Environment::isDebug()) {
            return $callback();
        }

        $start = microtime(true);
        $result = $callback();
        $end = microtime(true);
        $time = ($end - $start) * 1000; // в миллисекундах

        $message = ($label ? "[{$label}] " : '') . "Execution time: {$time}ms";

        if (Environment::isDevelopment()) {
            echo '<div style="background: #f3e5f5; border: 1px solid #9c27b0; margin: 10px; padding: 15px; border-radius: 5px; font-family: monospace;">';
            echo '<h4 style="color: #7b1fa2; margin-top: 0;">Benchmark</h4>';
            echo '<p style="margin: 0; color: #4a148c;">' . htmlspecialchars($message) . '</p>';
            echo '</div>';
        } else {
            \Core\Logger::debug($message);
        }

        return $result;
    }
}
