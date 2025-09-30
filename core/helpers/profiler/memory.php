<?php declare(strict_types=1);

/**
 * Memory Profiler Functions
 */

if (!function_exists('memory_start')) {
    /**
     * Start memory profiling
     * 
     * @return void
     */
    function memory_start(): void
    {
        \Core\MemoryProfiler::start();
    }
}

if (!function_exists('memory_snapshot')) {
    /**
     * Take a memory snapshot
     * 
     * @param string $name Snapshot name
     * @param string|null $label Optional label
     * @return array Snapshot data
     */
    function memory_snapshot(string $name, ?string $label = null): array
    {
        return \Core\MemoryProfiler::snapshot($name, $label);
    }
}

if (!function_exists('memory_current')) {
    /**
     * Get current memory usage
     * 
     * @return int Memory usage in bytes
     */
    function memory_current(): int
    {
        return \Core\MemoryProfiler::current();
    }
}

if (!function_exists('memory_peak')) {
    /**
     * Get peak memory usage
     * 
     * @return int Peak memory usage in bytes
     */
    function memory_peak(): int
    {
        return \Core\MemoryProfiler::peak();
    }
}

if (!function_exists('memory_dump')) {
    /**
     * Dump memory profile
     * 
     * @return void
     */
    function memory_dump(): void
    {
        \Core\MemoryProfiler::dump();
    }
}

if (!function_exists('memory_clear')) {
    /**
     * Clear memory snapshots
     * 
     * @return void
     */
    function memory_clear(): void
    {
        \Core\MemoryProfiler::clear();
    }
}

if (!function_exists('memory_measure')) {
    /**
     * Measure memory usage of a function
     * 
     * @param string $name Measurement name
     * @param callable $callback Function to measure
     * @return mixed Return value of the callback
     */
    function memory_measure(string $name, callable $callback): mixed
    {
        return \Core\MemoryProfiler::measure($name, $callback);
    }
}

if (!function_exists('memory_format')) {
    /**
     * Format bytes to human-readable string
     * 
     * @param int $bytes Bytes to format
     * @param int $precision Decimal precision
     * @return string Formatted string (e.g. "1.5 MB")
     */
    function memory_format(int $bytes, int $precision = 2): string
    {
        return \Core\MemoryProfiler::formatBytes($bytes, $precision);
    }
}

