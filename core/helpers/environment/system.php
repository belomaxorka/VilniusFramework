<?php declare(strict_types=1);

/**
 * System Detection Helpers
 */

if (!function_exists('is_cli')) {
    /**
     * Check if running in CLI mode
     *
     * @return bool
     */
    function is_cli(): bool
    {
        return php_sapi_name() === 'cli';
    }
}

if (!function_exists('is_windows')) {
    /**
     * Check if running on Windows
     *
     * @return bool
     */
    function is_windows(): bool
    {
        return DIRECTORY_SEPARATOR === '\\';
    }
}

if (!function_exists('is_unix')) {
    /**
     * Check if running on Unix-like system
     *
     * @return bool
     */
    function is_unix(): bool
    {
        return DIRECTORY_SEPARATOR === '/';
    }
}

