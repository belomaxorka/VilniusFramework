<?php declare(strict_types=1);

/**
 * Database Query Debugging Functions
 */

if (!function_exists('query_log')) {
    /**
     * Log SQL query
     *
     * @param string $sql SQL query
     * @param array $bindings Query bindings
     * @param float $time Execution time in milliseconds
     * @param int $rows Number of affected/returned rows
     * @return void
     */
    function query_log(string $sql, array $bindings = [], float $time = 0.0, int $rows = 0): void
    {
        \Core\QueryDebugger::log($sql, $bindings, $time, $rows);
    }
}

if (!function_exists('query_dump')) {
    /**
     * Dump all SQL queries
     *
     * @return void
     */
    function query_dump(): void
    {
        \Core\QueryDebugger::dump();
    }
}

if (!function_exists('query_stats')) {
    /**
     * Get query statistics
     *
     * @return array Statistics (count, total_time, avg_time, etc.)
     */
    function query_stats(): array
    {
        return \Core\QueryDebugger::getStats();
    }
}

if (!function_exists('query_slow')) {
    /**
     * Get slow queries
     *
     * @return array Slow queries list
     */
    function query_slow(): array
    {
        return \Core\QueryDebugger::getSlowQueries();
    }
}

if (!function_exists('query_duplicates')) {
    /**
     * Get duplicate queries
     *
     * @return array Duplicate queries list
     */
    function query_duplicates(): array
    {
        return \Core\QueryDebugger::getDuplicates();
    }
}

if (!function_exists('query_clear')) {
    /**
     * Clear query logs
     *
     * @return void
     */
    function query_clear(): void
    {
        \Core\QueryDebugger::clear();
    }
}

if (!function_exists('query_measure')) {
    /**
     * Measure query execution
     *
     * @param callable $callback Query to execute
     * @param string|null $label Optional label
     * @return mixed Return value of the callback
     */
    function query_measure(callable $callback, ?string $label = null): mixed
    {
        return \Core\QueryDebugger::measure($callback, $label);
    }
}
