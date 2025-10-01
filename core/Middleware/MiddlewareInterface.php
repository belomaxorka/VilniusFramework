<?php declare(strict_types=1);

namespace Core\Middleware;

interface MiddlewareInterface
{
    /**
     * Обработать запрос через middleware
     *
     * @param callable $next Следующий middleware или финальный обработчик
     * @return mixed
     */
    public function handle(callable $next): mixed;
}

