<?php declare(strict_types=1);

namespace Core;

use Core\Facades\Facade;
use Core\Contracts\DatabaseInterface;
use Core\Database\QueryBuilder;

/**
 * Database Facade
 * 
 * Статический фасад для DatabaseManager
 * Обеспечивает обратную совместимость со старым API
 * 
 * @method static QueryBuilder table(string $table)
 * @method static array select(string $query, array $bindings = [])
 * @method static array|null selectOne(string $query, array $bindings = [])
 * @method static bool insert(string $query, array $bindings = [])
 * @method static int update(string $query, array $bindings = [])
 * @method static int delete(string $query, array $bindings = [])
 * @method static mixed transaction(callable $callback)
 * @method static bool beginTransaction()
 * @method static bool commit()
 * @method static bool rollBack()
 * 
 * @see \Core\Database\DatabaseManager
 */
class Database extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DatabaseInterface::class;
    }

    // Backward compatibility - статический метод init() больше не нужен
    // DatabaseManager автоматически создается через DI контейнер
    public static function init(): DatabaseInterface
    {
        return static::resolveFacadeInstance();
    }

    // Backward compatibility - getInstance()
    public static function getInstance(): DatabaseInterface
    {
        return static::resolveFacadeInstance();
    }
}
