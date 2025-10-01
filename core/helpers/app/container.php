<?php declare(strict_types=1);

/**
 * Container Helper
 */

if (!function_exists('app')) {
    /**
     * Get the container instance or resolve a binding
     *
     * @param string|null $abstract
     * @param array $parameters
     * @return mixed|\Core\Container
     */
    function app(?string $abstract = null, array $parameters = []): mixed
    {
        $container = \Core\Container::getInstance();

        if ($abstract === null) {
            return $container;
        }

        return $container->make($abstract, $parameters);
    }
}

if (!function_exists('resolve')) {
    /**
     * Resolve a class from the container
     *
     * @param string $abstract
     * @param array $parameters
     * @return mixed
     */
    function resolve(string $abstract, array $parameters = []): mixed
    {
        return app($abstract, $parameters);
    }
}

if (!function_exists('singleton')) {
    /**
     * Register a singleton binding in the container
     *
     * @param string $abstract
     * @param \Closure|string|null $concrete
     * @return void
     */
    function singleton(string $abstract, \Closure|string|null $concrete = null): void
    {
        app()->singleton($abstract, $concrete);
    }
}

