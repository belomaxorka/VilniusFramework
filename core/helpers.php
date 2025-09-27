<?php declare(strict_types=1);

function config(string $key, $default = null): mixed
{
    return \Core\Config::get($key, $default);
}

/**
 * Retrieves a translated string for the current locale.
 *
 * This helper is a shortcut for `Lang::get()`.
 * If the translation key is not found, it returns the default value or the key itself.
 *
 * Usage example:
 * echo __('welcome'); // Outputs: Welcome
 *
 * @param string $key The translation key (e.g., 'welcome', 'login')
 * @param string $default The default value to return if the key is not found (default is an empty string)
 * @return string The translated string or the default value
 */
function __(string $key, string $default = ''): string
{
    return Lang::get($key, $default);
}

