<?php declare(strict_types=1);

/**
 * Configuration Helper
 */

if (!function_exists('config')) {
    /**
     * Get configuration value
     * 
     * @param string $key Configuration key
     * @param mixed $default Default value
     * @return mixed
     */
    function config(string $key, mixed $default = null): mixed
    {
        return \Core\Config::get($key, $default);
    }
}

