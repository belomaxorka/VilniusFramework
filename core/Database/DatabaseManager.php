<?php declare(strict_types=1);

namespace Core\Database;

use Core\Database\Drivers\MySqlDriver;
use Core\Database\Drivers\PostgreSqlDriver;
use Core\Database\Drivers\SqliteDriver;
use Core\Database\Exceptions\ConnectionException;
use Core\Database\Exceptions\DatabaseException;
use Core\Database\Exceptions\QueryException;

use Exception;
use PDO;
use PDOException;

class DatabaseManager implements DatabaseInterface
{
    protected array $config;
    protected array $connections = [];
    protected array $drivers = [
        'mysql' => MySqlDriver::class,
        'pgsql' => PostgreSqlDriver::class,
        'sqlite' => SqliteDriver::class,
    ];
    protected ?string $defaultConnection = null;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->defaultConnection = $config['default'] ?? null;
    }

    /**
     * Получить соединение с БД
     */
    public function connection(?string $name = null): PDO
    {
        $name = $name ?: $this->defaultConnection;

        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->createConnection($name);
        }

        return $this->connections[$name];
    }

    /**
     * Создать новое соединение
     */
    protected function createConnection(string $name): PDO
    {
        if (!isset($this->config['connections'][$name])) {
            throw new ConnectionException("Database connection [{$name}] not configured.");
        }

        $config = $this->config['connections'][$name];
        $driverName = $config['driver'];

        if (!isset($this->drivers[$driverName])) {
            throw new ConnectionException("Database driver [{$driverName}] not supported.");
        }

        $driverClass = $this->drivers[$driverName];
        $driver = new $driverClass();

        try {
            return $driver->connect($config);
        } catch (PDOException $e) {
            throw new ConnectionException("Could not connect to database: " . $e->getMessage());
        }
    }

    /**
     * Выполнить SELECT запрос
     */
    public function select(string $query, array $bindings = []): array
    {
        try {
            $statement = $this->connection()->prepare($query);
            $statement->execute($bindings);
            return $statement->fetchAll();
        } catch (PDOException $e) {
            throw new QueryException("Query failed: " . $e->getMessage());
        }
    }

    /**
     * Выполнить SELECT запрос и получить одну запись
     */
    public function selectOne(string $query, array $bindings = []): ?array
    {
        $results = $this->select($query, $bindings);
        return $results[0] ?? null;
    }

    /**
     * Выполнить INSERT запрос
     */
    public function insert(string $query, array $bindings = []): bool
    {
        try {
            $statement = $this->connection()->prepare($query);
            return $statement->execute($bindings);
        } catch (PDOException $e) {
            throw new QueryException("Insert failed: " . $e->getMessage());
        }
    }

    /**
     * Выполнить UPDATE запрос
     */
    public function update(string $query, array $bindings = []): int
    {
        try {
            $statement = $this->connection()->prepare($query);
            $statement->execute($bindings);
            return $statement->rowCount();
        } catch (PDOException $e) {
            throw new QueryException("Update failed: " . $e->getMessage());
        }
    }

    /**
     * Выполнить DELETE запрос
     */
    public function delete(string $query, array $bindings = []): int
    {
        try {
            $statement = $this->connection()->prepare($query);
            $statement->execute($bindings);
            return $statement->rowCount();
        } catch (PDOException $e) {
            throw new QueryException("Delete failed: " . $e->getMessage());
        }
    }

    /**
     * Выполнить произвольный запрос
     */
    public function statement(string $query, array $bindings = []): bool
    {
        try {
            $statement = $this->connection()->prepare($query);
            return $statement->execute($bindings);
        } catch (PDOException $e) {
            throw new QueryException("Statement failed: " . $e->getMessage());
        }
    }

    /**
     * Выполнить код в транзакции
     */
    public function transaction(callable $callback)
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Начать транзакцию
     */
    public function beginTransaction(): bool
    {
        return $this->connection()->beginTransaction();
    }

    /**
     * Подтвердить транзакцию
     */
    public function commit(): bool
    {
        try {
            return $this->connection()->commit();
        } catch (PDOException $e) {
            // Если нет активной транзакции, возвращаем false
            return false;
        }
    }

    /**
     * Отменить транзакцию
     */
    public function rollback(): bool
    {
        try {
            return $this->connection()->rollBack();
        } catch (PDOException $e) {
            // Если нет активной транзакции, возвращаем false
            return false;
        }
    }

    /**
     * Получить ID последней вставленной записи
     */
    public function lastInsertId(): string
    {
        return $this->connection()->lastInsertId();
    }

    /**
     * Добавить кастомный драйвер
     */
    public function addDriver(string $name, string $driverClass): void
    {
        if (!class_exists($driverClass)) {
            throw new DatabaseException("Driver class [{$driverClass}] does not exist.");
        }

        if (!in_array(DatabaseDriverInterface::class, class_implements($driverClass))) {
            throw new DatabaseException("Driver class must implement DatabaseDriverInterface.");
        }

        $this->drivers[$name] = $driverClass;
    }

    /**
     * Закрыть все соединения
     */
    public function disconnect(): void
    {
        $this->connections = [];
    }

    /**
     * Получить информацию о соединении
     */
    public function getConnectionInfo(?string $name = null): array
    {
        $name = $name ?: $this->defaultConnection;
        return $this->config['connections'][$name] ?? [];
    }
}
