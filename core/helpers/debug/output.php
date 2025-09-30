<?php declare(strict_types=1);

/**
 * Debug Output Functions
 */

use Core\Debug;

if (!function_exists('debug_flush')) {
    /**
     * Flush all accumulated debug data
     * 
     * @return void
     */
    function debug_flush(): void
    {
        Debug::flush();
    }
}

if (!function_exists('debug_output')) {
    /**
     * Get all accumulated debug data as string
     * 
     * @return string
     */
    function debug_output(): string
    {
        return Debug::getOutput();
    }
}

if (!function_exists('has_debug_output')) {
    /**
     * Check if there is accumulated debug data
     * 
     * @return bool
     */
    function has_debug_output(): bool
    {
        return Debug::hasOutput();
    }
}

if (!function_exists('debug_render_on_page')) {
    /**
     * Enable/disable rendering debug data on page
     * 
     * @param bool $enabled Whether to enable
     * @return void
     */
    function debug_render_on_page(bool $enabled = true): void
    {
        Debug::setRenderOnPage($enabled);
    }
}

if (!function_exists('render_debug')) {
    /**
     * Render debug output (for manual placement in templates)
     * 
     * @return string
     */
    function render_debug(): string
    {
        if (class_exists('\Core\Debug')) {
            return Debug::getOutput();
        }

        return '';
    }
}

if (!function_exists('render_debug_toolbar')) {
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
}

