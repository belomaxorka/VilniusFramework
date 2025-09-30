<?php declare(strict_types=1);

/**
 * Language Helper
 */

if (!function_exists('__')) {
    /**
     * Get translated string
     *
     * @param string $key Translation key
     * @param array $params Optional placeholders
     * @return string
     */
    function __(string $key, array $params = []): string
    {
        return \Core\Lang::get($key, $params);
    }
}
