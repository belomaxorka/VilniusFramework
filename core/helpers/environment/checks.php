<?php declare(strict_types=1);

/**
 * Environment Checks
 */

use Core\Environment;

if (!function_exists('is_debug')) {
    /**
     * Check if debug mode is enabled
     *
     * @return bool
     */
    function is_debug(): bool
    {
        return Environment::isDebug();
    }
}

if (!function_exists('is_dev')) {
    /**
     * Check if environment is development
     *
     * @return bool
     */
    function is_dev(): bool
    {
        return Environment::isDevelopment();
    }
}

if (!function_exists('is_prod')) {
    /**
     * Check if environment is production
     *
     * @return bool
     */
    function is_prod(): bool
    {
        return Environment::isProduction();
    }
}

if (!function_exists('is_testing')) {
    /**
     * Check if environment is testing
     *
     * @return bool
     */
    function is_testing(): bool
    {
        return Environment::isTesting();
    }
}

if (!function_exists('app_env')) {
    /**
     * Get current environment name
     *
     * @return string Environment name (e.g. 'development', 'production')
     */
    function app_env(): string
    {
        return Environment::get();
    }
}

