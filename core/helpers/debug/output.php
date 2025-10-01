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
