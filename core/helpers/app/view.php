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

// DEPRECATED: display() удален. 
// Используйте TemplateEngine::getInstance()->display() напрямую
// или верните view() из контроллера (рекомендуется)
