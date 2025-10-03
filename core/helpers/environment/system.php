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

if (!function_exists('normalize_path')) {
    /**
     * Normalize path by converting all backslashes to forward slashes
     * Useful for cross-platform path handling and consistent output
     *
     * @param string $path Path to normalize
     * @return string Normalized path with forward slashes
     */
    function normalize_path(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}

if (!function_exists('normalize_paths')) {
    /**
     * Normalize multiple paths or all string values in an array recursively
     *
     * @param array $data Array containing paths or mixed data
     * @return array Array with normalized paths
     */
    function normalize_paths(array $data): array
    {
        return array_map(function ($value) {
            if (is_string($value)) {
                return normalize_path($value);
            }
            if (is_array($value)) {
                return normalize_paths($value);
            }
            return $value;
        }, $data);
    }
}

