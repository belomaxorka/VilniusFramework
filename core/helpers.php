<?php declare(strict_types=1);

function config(string $key, $default = null): mixed
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

function env(string $key, mixed $default = null): mixed
{
    if (class_exists('\Core\Env')) {
        return \Core\Env::get($key, $default);
    } else {
        return $_SERVER[$key] ?? $default;
    }
}
