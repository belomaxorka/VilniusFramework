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

        $message = ($label ? "[{$label}] " : '') . "Execution time: " . number_format($time, 2) . "ms";

        if (Environment::isDevelopment()) {
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

if (!function_exists('debug_flush')) {
    /**
     * Debug flush - выводит все накопленные debug данные
     */
    function debug_flush(): void
    {
        Debug::flush();
    }
}

if (!function_exists('debug_output')) {
    /**
     * Debug output - получает все накопленные debug данные как строку
     */
    function debug_output(): string
    {
        return Debug::getOutput();
    }
}

if (!function_exists('has_debug_output')) {
    /**
     * Has debug output - проверяет, есть ли накопленные debug данные
     */
    function has_debug_output(): bool
    {
        return Debug::hasOutput();
    }
}

if (!function_exists('debug_render_on_page')) {
    /**
     * Debug render on page - включить/выключить рендеринг на странице
     * По умолчанию false (данные только в toolbar)
     */
    function debug_render_on_page(bool $enabled = true): void
    {
        Debug::setRenderOnPage($enabled);
    }
}

// ============================================================================
// Debug Timer Functions
// ============================================================================

if (!function_exists('timer_start')) {
    /**
     * Timer start - запускает таймер
     */
    function timer_start(string $name = 'default'): void
    {
        \Core\DebugTimer::start($name);
    }
}

if (!function_exists('timer_stop')) {
    /**
     * Timer stop - останавливает таймер и возвращает время
     */
    function timer_stop(string $name = 'default'): float
    {
        return \Core\DebugTimer::stop($name);
    }
}

if (!function_exists('timer_lap')) {
    /**
     * Timer lap - промежуточный замер
     */
    function timer_lap(string $name = 'default', ?string $label = null): float
    {
        return \Core\DebugTimer::lap($name, $label);
    }
}

if (!function_exists('timer_elapsed')) {
    /**
     * Timer elapsed - получить прошедшее время
     */
    function timer_elapsed(string $name = 'default'): float
    {
        return \Core\DebugTimer::getElapsed($name);
    }
}

if (!function_exists('timer_dump')) {
    /**
     * Timer dump - вывести таймер(ы)
     */
    function timer_dump(?string $name = null): void
    {
        \Core\DebugTimer::dump($name);
    }
}

if (!function_exists('timer_clear')) {
    /**
     * Timer clear - очистить таймер(ы)
     */
    function timer_clear(?string $name = null): void
    {
        \Core\DebugTimer::clear($name);
    }
}

if (!function_exists('timer_measure')) {
    /**
     * Timer measure - измерить время выполнения функции
     */
    function timer_measure(string $name, callable $callback): mixed
    {
        return \Core\DebugTimer::measure($name, $callback);
    }
}

// ============================================================================
// Memory Profiler Functions
// ============================================================================

if (!function_exists('memory_start')) {
    /**
     * Memory start - начать профилирование памяти
     */
    function memory_start(): void
    {
        \Core\MemoryProfiler::start();
    }
}

if (!function_exists('memory_snapshot')) {
    /**
     * Memory snapshot - сделать снимок памяти
     */
    function memory_snapshot(string $name, ?string $label = null): array
    {
        return \Core\MemoryProfiler::snapshot($name, $label);
    }
}

if (!function_exists('memory_current')) {
    /**
     * Memory current - получить текущее использование памяти
     */
    function memory_current(): int
    {
        return \Core\MemoryProfiler::current();
    }
}

if (!function_exists('memory_peak')) {
    /**
     * Memory peak - получить пиковое использование памяти
     */
    function memory_peak(): int
    {
        return \Core\MemoryProfiler::peak();
    }
}

if (!function_exists('memory_dump')) {
    /**
     * Memory dump - вывести профиль памяти
     */
    function memory_dump(): void
    {
        \Core\MemoryProfiler::dump();
    }
}

if (!function_exists('memory_clear')) {
    /**
     * Memory clear - очистить снимки памяти
     */
    function memory_clear(): void
    {
        \Core\MemoryProfiler::clear();
    }
}

if (!function_exists('memory_measure')) {
    /**
     * Memory measure - измерить использование памяти функцией
     */
    function memory_measure(string $name, callable $callback): mixed
    {
        return \Core\MemoryProfiler::measure($name, $callback);
    }
}

if (!function_exists('memory_format')) {
    /**
     * Memory format - форматировать байты в читаемый вид
     */
    function memory_format(int $bytes, int $precision = 2): string
    {
        return \Core\MemoryProfiler::formatBytes($bytes, $precision);
    }
}

// ============================================================================
// Debug Context Functions
// ============================================================================

if (!function_exists('context_start')) {
    /**
     * Context start - начать debug контекст
     */
    function context_start(string $name, ?array $config = null): void
    {
        \Core\DebugContext::start($name, $config);
    }
}

if (!function_exists('context_end')) {
    /**
     * Context end - закончить debug контекст
     */
    function context_end(?string $name = null): void
    {
        \Core\DebugContext::end($name);
    }
}

if (!function_exists('context_run')) {
    /**
     * Context run - выполнить код в контексте
     */
    function context_run(string $name, callable $callback, ?array $config = null): mixed
    {
        return \Core\DebugContext::run($name, $callback, $config);
    }
}

if (!function_exists('context_dump')) {
    /**
     * Context dump - вывести контексты
     */
    function context_dump(?array $contexts = null): void
    {
        \Core\DebugContext::dump($contexts);
    }
}

if (!function_exists('context_clear')) {
    /**
     * Context clear - очистить контексты
     */
    function context_clear(?string $name = null): void
    {
        \Core\DebugContext::clear($name);
    }
}

if (!function_exists('context_current')) {
    /**
     * Context current - получить текущий контекст
     */
    function context_current(): ?string
    {
        return \Core\DebugContext::current();
    }
}

if (!function_exists('context_filter')) {
    /**
     * Context filter - включить фильтрацию по контекстам
     */
    function context_filter(array $contexts): void
    {
        \Core\DebugContext::enableFilter($contexts);
    }
}

// ============================================================================
// SQL Query Debugger Functions
// ============================================================================

if (!function_exists('query_log')) {
    /**
     * Query log - залогировать SQL запрос
     */
    function query_log(string $sql, array $bindings = [], float $time = 0.0, int $rows = 0): void
    {
        \Core\QueryDebugger::log($sql, $bindings, $time, $rows);
    }
}

if (!function_exists('query_dump')) {
    /**
     * Query dump - вывести все SQL запросы
     */
    function query_dump(): void
    {
        \Core\QueryDebugger::dump();
    }
}

if (!function_exists('query_stats')) {
    /**
     * Query stats - получить статистику запросов
     */
    function query_stats(): array
    {
        return \Core\QueryDebugger::getStats();
    }
}

if (!function_exists('query_slow')) {
    /**
     * Query slow - получить медленные запросы
     */
    function query_slow(): array
    {
        return \Core\QueryDebugger::getSlowQueries();
    }
}

if (!function_exists('query_duplicates')) {
    /**
     * Query duplicates - получить дублирующиеся запросы
     */
    function query_duplicates(): array
    {
        return \Core\QueryDebugger::getDuplicates();
    }
}

if (!function_exists('query_clear')) {
    /**
     * Query clear - очистить логи запросов
     */
    function query_clear(): void
    {
        \Core\QueryDebugger::clear();
    }
}

if (!function_exists('query_measure')) {
    /**
     * Query measure - измерить выполнение запроса
     */
    function query_measure(callable $callback, ?string $label = null): mixed
    {
        return \Core\QueryDebugger::measure($callback, $label);
    }
}

// ============================================================================
// Dump Server Functions
// ============================================================================

if (!function_exists('server_dump')) {
    /**
     * Server dump - отправить dump на dump server
     */
    function server_dump(mixed $data, ?string $label = null): bool
    {
        return \Core\DumpClient::dump($data, $label);
    }
}

if (!function_exists('dd_server')) {
    /**
     * DD Server - dump to server and die
     */
    function dd_server(mixed $data, ?string $label = null): never
    {
        \Core\DumpClient::dump($data, $label);
        exit(1);
    }
}

if (!function_exists('dump_server_available')) {
    /**
     * Check if dump server is available
     */
    function dump_server_available(): bool
    {
        return \Core\DumpClient::isServerAvailable();
    }
}
