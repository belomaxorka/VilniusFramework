<?php declare(strict_types=1);

/**
 * Environment Variables Helper
 */

if (!function_exists('env')) {
    /**
     * Get environment variable
     *
     * @param string $key Variable name
     * @param mixed $default Default value
     * @return mixed
     */
    function env(string $key, mixed $default = null): mixed
    {
        if (class_exists('\Core\Env')) {
            return \Core\Env::get($key, $default);
        }

        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }

        return $_SERVER[$key] ?? $default;
    }
}
