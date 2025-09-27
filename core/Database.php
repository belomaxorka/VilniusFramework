<?php declare(strict_types=1);

namespace Core;

use Core\Database\DatabaseManager;
use Core\Database\QueryBuilder;

use RuntimeException;

final class Database
{
    private static ?DatabaseManager $instance = null;

    public static function init(): DatabaseManager
    {
        if (self::$instance === null) {
            $config = Config::get('database');

            if (!$config) {
                throw new RuntimeException('Database configuration not found');
            }

            self::$instance = new DatabaseManager($config);
        }

        return self::$instance;
    }

    public static function getInstance(): DatabaseManager
    {
        if (self::$instance === null) {
            throw new RuntimeException('Database not initialized. Call Database::init() first.');
        }

        return self::$instance;
    }

    /**
     * Получить Query Builder
     */
    public static function table(string $table): QueryBuilder
    {
        return (new QueryBuilder(self::getInstance()))->table($table);
    }

    /**
     * Shortcut методы для быстрого доступа
     */
    public static function select(string $query, array $bindings = []): array
    {
        return self::getInstance()->select($query, $bindings);
    }

    public static function selectOne(string $query, array $bindings = []): ?array
    {
        return self::getInstance()->selectOne($query, $bindings);
    }

    public static function insert(string $query, array $bindings = []): bool
    {
        return self::getInstance()->insert($query, $bindings);
    }

    public static function update(string $query, array $bindings = []): int
    {
        return self::getInstance()->update($query, $bindings);
    }

    public static function delete(string $query, array $bindings = []): int
    {
        return self::getInstance()->delete($query, $bindings);
    }

    public static function transaction(callable $callback)
    {
        return self::getInstance()->transaction($callback);
    }
}
