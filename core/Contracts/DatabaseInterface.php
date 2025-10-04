<?php declare(strict_types=1);

namespace Core\Contracts;

use Core\Database\QueryBuilder;

/**
 * Database Manager Interface
 * 
 * Определяет контракт для работы с базой данных
 */
interface DatabaseInterface
{
    /**
     * Получить Query Builder для таблицы
     */
    public function table(string $table): QueryBuilder;

    /**
     * Выполнить SELECT запрос
     */
    public function select(string $query, array $bindings = []): array;

    /**
     * Выполнить SELECT запрос и вернуть одну строку
     */
    public function selectOne(string $query, array $bindings = []): ?array;

    /**
     * Выполнить INSERT запрос
     */
    public function insert(string $query, array $bindings = []): bool;

    /**
     * Выполнить UPDATE запрос
     */
    public function update(string $query, array $bindings = []): int;

    /**
     * Выполнить DELETE запрос
     */
    public function delete(string $query, array $bindings = []): int;

    /**
     * Выполнить транзакцию
     */
    public function transaction(callable $callback): mixed;

    /**
     * Начать транзакцию
     */
    public function beginTransaction(): bool;

    /**
     * Зафиксировать транзакцию
     */
    public function commit(): bool;

    /**
     * Откатить транзакцию
     */
    public function rollBack(): bool;
}

