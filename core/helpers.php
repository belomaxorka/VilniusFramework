<?php declare(strict_types=1);

/**
 * Get configuration value (shortcut for Core\Config::get)
 *
 * @param string $key Configuration key
 * @param mixed $default Default value
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

/**
 * Get environment variable (shortcut for Core\Env::get or $_SERVER)
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
