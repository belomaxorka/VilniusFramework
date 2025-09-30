<?php declare(strict_types=1);

/**
 * View/Template Helpers
 */

if (!function_exists('view')) {
    /**
     * Render template and return as string
     *
     * @param string $template Template name
     * @param array $variables Template variables
     * @return string
     */
    function view(string $template, array $variables = []): string
    {
        return \Core\TemplateEngine::getInstance()->render($template, $variables);
    }
}

if (!function_exists('display')) {
    /**
     * Display template (outputs directly)
     *
     * @param string $template Template name
     * @param array $variables Template variables
     * @return void
     */
    function display(string $template, array $variables = []): void
    {
        \Core\TemplateEngine::getInstance()->display($template, $variables);
    }
}
