<?php declare(strict_types=1);

namespace Core\Facades;

use Core\Container;
use RuntimeException;

/**
 * Base Facade Class
 * 
 * Базовый класс для всех фасадов, обеспечивающий
 * статический доступ к instance-based сервисам через DI контейнер
 */
abstract class Facade
{
    /**
     * Экземпляры resolved сервисов
     */
    protected static array $resolvedInstances = [];

    /**
     * Получить имя класса/интерфейса в контейнере
     */
    abstract protected static function getFacadeAccessor(): string;

    /**
     * Получить instance из контейнера
     */
    protected static function resolveFacadeInstance(): mixed
    {
        $accessor = static::getFacadeAccessor();

        if (isset(static::$resolvedInstances[$accessor])) {
            return static::$resolvedInstances[$accessor];
        }

        $instance = Container::getInstance()->make($accessor);
        static::$resolvedInstances[$accessor] = $instance;

        return $instance;
    }

    /**
     * Установить custom instance (полезно для тестирования)
     */
    public static function setFacadeInstance(mixed $instance): void
    {
        $accessor = static::getFacadeAccessor();
        static::$resolvedInstances[$accessor] = $instance;
    }

    /**
     * Очистить resolved instance
     */
    public static function clearResolvedInstance(): void
    {
        $accessor = static::getFacadeAccessor();
        unset(static::$resolvedInstances[$accessor]);
    }

    /**
     * Очистить все resolved instances
     */
    public static function clearResolvedInstances(): void
    {
        static::$resolvedInstances = [];
    }

    /**
     * Магический метод для вызова методов через фасад
     */
    public static function __callStatic(string $method, array $args): mixed
    {
        $instance = static::resolveFacadeInstance();

        if ($instance === null) {
            throw new RuntimeException('A facade root has not been set.');
        }

        return $instance->$method(...$args);
    }
}

