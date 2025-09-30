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
 * Get environment variable (shortcut for Core\Env::get, getenv or $_SERVER)
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

/**
 * Render template (shortcut for Core\TemplateEngine::getInstance()->render)
 *
 * @param string $template Template name
 * @param array $variables Template variables
 * @return string
 */
function view(string $template, array $variables = []): string
{
    return \Core\TemplateEngine::getInstance()->render($template, $variables);
}

/**
 * Display template (shortcut for Core\TemplateEngine::getInstance()->display)
 *
 * @param string $template Template name
 * @param array $variables Template variables
 * @return void
 */
function display(string $template, array $variables = []): void
{
    \Core\TemplateEngine::getInstance()->display($template, $variables);
}

/**
 * Get template engine instance (shortcut for Core\TemplateEngine::getInstance)
 *
 * @return \Core\TemplateEngine
 */
function template(): \Core\TemplateEngine
{
    return \Core\TemplateEngine::getInstance();
}

/**
 * Render debug output (for manual placement in templates)
 *
 * @return string
 */
function render_debug(): string
{
    if (class_exists('\Core\Debug')) {
        return \Core\Debug::getOutput();
    }

    return '';
}

/**
 * Render debug toolbar (interactive debug panel)
 *
 * @return string
 */
function render_debug_toolbar(): string
{
    if (class_exists('\Core\DebugToolbar')) {
        return \Core\DebugToolbar::render();
    }

    return '';
}
