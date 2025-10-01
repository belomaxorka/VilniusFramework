<?php declare(strict_types=1);

/**
 * Route Helper
 */

if (!function_exists('route')) {
    /**
     * Generate URL by route name
     *
     * @param string $name Route name
     * @param array<string, mixed> $params Route parameters
     * @return string
     * @throws \InvalidArgumentException
     */
    function route(string $name, array $params = []): string
    {
        // Получаем Router из DebugToolbar (где он хранится)
        $router = \Core\DebugToolbar::getRouter();
        
        if (!$router) {
            throw new \RuntimeException('Router is not initialized. Make sure to call DebugToolbar::setRouter() before using route() helper.');
        }

        return $router->route($name, $params);
    }
}

