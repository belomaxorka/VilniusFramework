<?php declare(strict_types=1);

/**
 * Dump Functions
 */

use Core\Debug;

if (!function_exists('dd')) {
    /**
     * Dump and die - output variable and stop execution
     *
     * @param mixed $var Variable to dump
     * @param string|null $label Optional label
     * @return never
     */
    function dd(mixed $var, ?string $label = null): never
    {
        Debug::dump($var, $label, true);
    }
}

if (!function_exists('dump')) {
    /**
     * Dump variable without stopping execution
     *
     * @param mixed $var Variable to dump
     * @param string|null $label Optional label
     * @return void
     */
    function dump(mixed $var, ?string $label = null): void
    {
        Debug::dump($var, $label, false);
    }
}

if (!function_exists('dump_pretty')) {
    /**
     * Dump variable with pretty formatting
     *
     * @param mixed $var Variable to dump
     * @param string|null $label Optional label
     * @return void
     */
    function dump_pretty(mixed $var, ?string $label = null): void
    {
        Debug::dumpPretty($var, $label, false);
    }
}

if (!function_exists('dd_pretty')) {
    /**
     * Dump pretty and die
     *
     * @param mixed $var Variable to dump
     * @param string|null $label Optional label
     * @return never
     */
    function dd_pretty(mixed $var, ?string $label = null): never
    {
        Debug::dumpPretty($var, $label, true);
    }
}
