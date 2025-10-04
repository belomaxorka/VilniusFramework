<?php declare(strict_types=1);

namespace Core;

use Core\Facades\Facade;
use Core\Contracts\DatabaseInterface;
use Core\Database\QueryBuilder;

/**
 * Database Facade
 * 
 * Статический фасад для DatabaseManager
 * Все методы делегируются к DatabaseInterface через DI контейнер
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
 * @method static bool statement(string $query)
 * @method static string lastInsertId()
 * 
 * @see \Core\Database\DatabaseManager
 */
class Database extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DatabaseInterface::class;
    }
}
