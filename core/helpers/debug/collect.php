<?php declare(strict_types=1);

/**
 * Debug Collection Functions
 */

use Core\Debug;

if (!function_exists('collect')) {
    /**
     * Collect debug data without output
     *
     * @param mixed $var Variable to collect
     * @param string|null $label Optional label
     * @return void
     */
    function collect(mixed $var, ?string $label = null): void
    {
        Debug::collect($var, $label);
    }
}

if (!function_exists('dump_all')) {
    /**
     * Dump all collected debug data
     *
     * @param bool $die Whether to stop execution after dump
     * @return void
     */
    function dump_all(bool $die = false): void
    {
        Debug::dumpAll($die);
    }
}

if (!function_exists('clear_debug')) {
    /**
     * Clear all collected debug data
     *
     * @return void
     */
    function clear_debug(): void
    {
        Debug::clear();
    }
}
