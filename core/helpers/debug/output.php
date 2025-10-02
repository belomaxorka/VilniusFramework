<?php declare(strict_types=1);

/**
 * Debug Output Functions
 * 
 * DEPRECATED: Эти хелперы устарели. Используйте напрямую методы классов:
 * - Debug::flush() вместо debug_flush()
 * - Debug::getOutput() вместо debug_output()
 * - Debug::hasOutput() вместо has_debug_output()
 * - Debug::setRenderOnPage() вместо debug_render_on_page()
 * 
 * Хелперы render_debug() и render_debug_toolbar() удалены,
 * так как Debug Toolbar теперь автоматически инъектируется через DebugToolbarMiddleware.
 */

if (!function_exists('debug_flush')) {
    /**
     * Вывести накопленный debug вывод и очистить буфер
     * @deprecated Используйте Debug::flush()
     */
    function debug_flush(): void
    {
        \Core\Debug::flush();
    }
}

if (!function_exists('debug_output')) {
    /**
     * Получить накопленный debug вывод как строку
     * @deprecated Используйте Debug::getOutput()
     */
    function debug_output(): string
    {
        return \Core\Debug::getOutput();
    }
}

if (!function_exists('has_debug_output')) {
    /**
     * Проверить наличие накопленного debug вывода
     * @deprecated Используйте Debug::hasOutput()
     */
    function has_debug_output(): bool
    {
        return \Core\Debug::hasOutput();
    }
}

if (!function_exists('debug_render_on_page')) {
    /**
     * Включить/выключить рендеринг debug информации на странице
     * @deprecated Используйте Debug::setRenderOnPage()
     */
    function debug_render_on_page(bool $enabled = true): void
    {
        \Core\Debug::setRenderOnPage($enabled);
    }
}

if (!function_exists('render_debug')) {
    /**
     * Отрендерить debug вывод
     * @deprecated Debug Toolbar теперь автоматически инъектируется через DebugToolbarMiddleware
     */
    function render_debug(): string
    {
        return \Core\Debug::getOutput();
    }
}
