<?php declare(strict_types=1);

namespace Core\Database;

use PDO;

interface DatabaseInterface
{
    public function connection(?string $name = null): PDO;

    public function select(string $query, array $bindings = []): array;

    public function selectOne(string $query, array $bindings = []): ?array;

    public function insert(string $query, array $bindings = []): bool;

    public function update(string $query, array $bindings = []): int;

    public function delete(string $query, array $bindings = []): int;

    public function statement(string $query, array $bindings = []): bool;

    public function transaction(callable $callback);

    public function beginTransaction(): bool;

    public function commit(): bool;

    public function rollback(): bool;

    public function lastInsertId(): string;
}

