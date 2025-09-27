<?php declare(strict_types=1);

/**
 * Get configuration value (shortcut for Core\Config::get)
 *
 * @param string $key Configuration key
 * @param mixed $default Default value if key does not exist
 * @return mixed
 */
function config(string $key, mixed $default = null): mixed
{
    return \Core\Config::get($key, $default);
}

/**
 * Get translated string (shortcut for Core\Lang::get)
 *
 * @param string $key Translation key
 * @param array $params Optional placeholders
 * @return string
 */
function __(string $key, array $params = []): string
{
    return \Core\Lang::get($key, $params);
}
