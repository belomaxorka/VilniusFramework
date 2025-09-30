<?php declare(strict_types=1);

/**
 * Debug Context Functions
 */

if (!function_exists('context_start')) {
    /**
     * Start a debug context
     *
     * @param string $name Context name
     * @param array|null $config Optional configuration
     * @return void
     */
    function context_start(string $name, ?array $config = null): void
    {
        \Core\DebugContext::start($name, $config);
    }
}

if (!function_exists('context_end')) {
    /**
     * End a debug context
     *
     * @param string|null $name Context name (null for current context)
     * @return void
     */
    function context_end(?string $name = null): void
    {
        \Core\DebugContext::end($name);
    }
}

if (!function_exists('context_run')) {
    /**
     * Execute code within a debug context
     *
     * @param string $name Context name
     * @param callable $callback Function to execute
     * @param array|null $config Optional configuration
     * @return mixed Return value of the callback
     */
    function context_run(string $name, callable $callback, ?array $config = null): mixed
    {
        return \Core\DebugContext::run($name, $callback, $config);
    }
}

if (!function_exists('context_dump')) {
    /**
     * Dump context(s) information
     *
     * @param array|null $contexts Specific contexts to dump (null for all)
     * @return void
     */
    function context_dump(?array $contexts = null): void
    {
        \Core\DebugContext::dump($contexts);
    }
}

if (!function_exists('context_clear')) {
    /**
     * Clear context(s)
     *
     * @param string|null $name Context name (null for all contexts)
     * @return void
     */
    function context_clear(?string $name = null): void
    {
        \Core\DebugContext::clear($name);
    }
}

if (!function_exists('context_current')) {
    /**
     * Get current context name
     *
     * @return string|null Current context name or null
     */
    function context_current(): ?string
    {
        return \Core\DebugContext::current();
    }
}

if (!function_exists('context_filter')) {
    /**
     * Enable context filtering (only show specified contexts)
     *
     * @param array $contexts Context names to show
     * @return void
     */
    function context_filter(array $contexts): void
    {
        \Core\DebugContext::enableFilter($contexts);
    }
}
